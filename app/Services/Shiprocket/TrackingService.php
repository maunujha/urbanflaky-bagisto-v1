<?php

namespace App\Services\Shiprocket;

use App\Jobs\Shiprocket\HandleShipmentMilestone;
use App\Models\ShiprocketOrder;
use App\Models\ShiprocketTrackingEvent;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Turns courier updates (webhook, poller, admin actions) into one consistent
 * shipment record: a deduplicated status history, a stage that only moves
 * forward, and milestone side effects queued exactly once per transition.
 */
class TrackingService
{
    public function __construct(protected ShiprocketClient $client) {}

    /**
     * @param  array{status?: ?string, awb?: ?string, courier?: ?string, etd?: ?string, scans?: array}  $update
     */
    public function applyCourierUpdate(ShiprocketOrder $shipment, array $update, string $source): void
    {
        $previous = $shipment->status;

        foreach ($update['scans'] ?? [] as $scan) {
            $this->recordScan($shipment, $scan, $source);
        }

        $raw   = trim((string) ($update['status'] ?? ''));
        $stage = ShipmentStage::fromStatus($raw);

        $attributes = array_filter([
            'current_status' => $raw ?: null,
            'awb_code'       => $shipment->awb_code ?: ($update['awb'] ?? null),
            'courier_name'   => $shipment->courier_name ?: ($update['courier'] ?? null),
            'etd'            => $this->date($update['etd'] ?? null)?->toDateString(),
        ]);

        $attributes['last_event_at'] = now();

        if ($source === 'poll') {
            $attributes['last_synced_at'] = now();
        }

        $shipment->fill($attributes);

        if ($stage && ShipmentStage::canAdvance($previous, $stage)) {
            $this->moveTo($shipment, $stage, $raw ?: ShipmentStage::label($stage), $source);
        } else {
            $shipment->save();
        }
    }

    /**
     * Move a shipment to a stage our own action produced (pushed, AWB
     * assigned, pickup booked, cancelled).
     */
    public function transition(ShiprocketOrder $shipment, string $stage, string $activity, array $attributes = []): void
    {
        $shipment->fill($attributes);

        if (! ShipmentStage::canAdvance($shipment->status, $stage)) {
            $shipment->save();

            return;
        }

        $this->moveTo($shipment, $stage, $activity, 'system');
    }

    /**
     * Fetch the live courier timeline for one shipment and apply it.
     */
    public function refresh(ShiprocketOrder $shipment): void
    {
        if (! $shipment->hasAwb()) {
            return;
        }

        $data  = $this->client->trackAwb($shipment->awb_code)['tracking_data'] ?? [];
        $track = $data['shipment_track'][0] ?? [];

        $scans = array_map(fn ($a) => [
            'date'     => $a['date'] ?? null,
            'status'   => $a['sr-status-label'] ?? ($a['status'] ?? null),
            'activity' => $a['activity'] ?? null,
            'location' => $a['location'] ?? null,
        ], $data['shipment_track_activities'] ?? []);

        $this->applyCourierUpdate($shipment, [
            'status'  => $track['current_status'] ?? null,
            'courier' => $track['courier_name'] ?? null,
            'etd'     => $data['etd'] ?? ($track['edd'] ?? null),
            'scans'   => $scans,
        ], 'poll');

        $shipment->forceFill(['last_synced_at' => now()])->save();
    }

    protected function moveTo(ShiprocketOrder $shipment, string $stage, string $activity, string $source): void
    {
        $previous = $shipment->status;

        $shipment->status = $stage;

        $milestone = match ($stage) {
            ShipmentStage::NEW              => 'pushed_at',
            ShipmentStage::AWB_ASSIGNED     => 'awb_assigned_at',
            ShipmentStage::PICKUP_SCHEDULED => 'pickup_scheduled_at',
            ShipmentStage::DELIVERED        => 'delivered_at',
            default                         => null,
        };

        if ($milestone && ! $shipment->{$milestone}) {
            $shipment->{$milestone} = now();
        }

        if (in_array($stage, ShipmentStage::IN_CARRIER_HANDS, true) && ! $shipment->shipped_at) {
            $shipment->shipped_at = now();
        }

        DB::transaction(function () use ($shipment, $stage, $activity, $source) {
            $shipment->save();

            $this->record($shipment->order_id, [
                'stage'    => $stage,
                'status'   => $shipment->current_status,
                'activity' => $activity,
                'location' => null,
                'event_at' => now(),
            ], $source, 'stage:'.$stage);
        });

        Log::info('Shiprocket shipment stage changed', [
            'order_id' => $shipment->order_id,
            'from'     => $previous,
            'to'       => $stage,
            'source'   => $source,
        ]);

        HandleShipmentMilestone::dispatch($shipment->order_id, $previous, $stage)->afterCommit();
    }

    protected function recordScan(ShiprocketOrder $shipment, array $scan, string $source): void
    {
        $activity = trim((string) ($scan['activity'] ?? ''));
        $status   = trim((string) ($scan['status'] ?? ''));

        if ($activity === '' && $status === '') {
            return;
        }

        $eventAt = $this->date($scan['date'] ?? null);

        $this->record($shipment->order_id, [
            'stage'    => ShipmentStage::fromStatus($status) ?? ShipmentStage::fromStatus($activity),
            'status'   => $status ?: null,
            'activity' => mb_substr($activity ?: $status, 0, 500),
            'location' => mb_substr(trim((string) ($scan['location'] ?? '')), 0, 255) ?: null,
            'event_at' => $eventAt,
        ], $source, implode('|', [$eventAt?->toIso8601String(), $activity, $status, $scan['location'] ?? '']));
    }

    /**
     * One row per distinct event; the same scan from the webhook and the
     * poller (or a replayed webhook) hits the unique key and is skipped.
     */
    protected function record(int $orderId, array $event, string $source, string $identity): void
    {
        try {
            ShiprocketTrackingEvent::create($event + [
                'order_id'    => $orderId,
                'source'      => $source,
                'fingerprint' => sha1($identity),
            ]);
        } catch (UniqueConstraintViolationException) {
            /* Already recorded. */
        }
    }

    /**
     * Shiprocket mixes "2026-10-12 14:03:00", "12 10 2026 14:03:00" and ISO
     * strings; all are IST.
     */
    protected function date(?string $value): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            if (preg_match('/^\d{2} \d{2} \d{4}/', $value)) {
                return Carbon::createFromFormat('d m Y H:i:s', $value, 'Asia/Kolkata');
            }

            return Carbon::parse($value, 'Asia/Kolkata');
        } catch (\Throwable) {
            return null;
        }
    }
}
