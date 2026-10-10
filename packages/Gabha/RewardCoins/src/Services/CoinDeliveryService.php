<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Services;

use DateTimeInterface;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Models\CoinTransaction;
use Gabha\RewardCoins\Repositories\Contracts\CoinTransactionRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Webkul\Sales\Models\OrderProxy;

/**
 * Decides when an order counts as delivered for rewards and opens the
 * post-delivery return window on its pending coins.
 *
 * Every delivery path ends here (see docs/REWARD-COINS-ACCOUNTING.md §Delivery):
 *  - Shiprocket webhooks fire the `reward_coins.delivery.event` event;
 *  - admin / Bagisto status changes to `completed` fire
 *    `sales.order.update-status.after`;
 *  - the daily `reward-coins:confirm-available` run reconciles anything a
 *    missed webhook or crashed worker left without a window.
 *
 * Delivery evidence: an order shipped through Shiprocket is delivered only
 * when its Shiprocket status says so. In this store Bagisto marks an order
 * `completed` the moment it is fully shipped (before `MarkOrderShipped` turns
 * it into `shipped`), so `completed` alone is trusted only for orders with no
 * Shiprocket record (manual fulfilment). Cancelled/closed orders never qualify.
 */
class CoinDeliveryService
{
    /**
     * Memoised: whether the carrier integration table exists.
     */
    private ?bool $hasShipmentTable = null;

    private ?bool $hasDeliveredAt = null;

    public function __construct(
        private readonly CoinTransactionRepositoryInterface $ledger,
    ) {
    }

    /**
     * Stamp `available_at` on the order's pending coins if it is delivered.
     * Idempotent: rows already carrying a window are never re-stamped.
     *
     * @param  int  $orderId
     * @param  DateTimeInterface  $availableAt
     * @return int  Rows stamped.
     */
    public function openReturnWindow(int $orderId, DateTimeInterface $availableAt): int
    {
        $order = OrderProxy::modelClass()::query()->find($orderId);

        if (! $order || ! $this->isDelivered($order)) {
            return 0;
        }

        return $this->ledger->stampAvailableAt($orderId, $availableAt);
    }

    /**
     * When coins delivered "now" become spendable.
     *
     * @param  DateTimeInterface|null  $deliveredAt
     * @return Carbon
     */
    public function availableAtFor(?DateTimeInterface $deliveredAt = null): Carbon
    {
        $deliveredAt = $deliveredAt ? Carbon::instance($deliveredAt) : Carbon::now();

        return $deliveredAt->copy()->addDays(max(0, (int) CoinSetting::active()->pending_confirmation_days));
    }

    /**
     * Whether the order has reached delivery for reward purposes.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @return bool
     */
    public function isDelivered($order): bool
    {
        if (in_array($order->status, (array) config('reward_coins.reverse_on_statuses', []), true)) {
            return false;
        }

        $shipment = $this->shiprocketRecord((int) $order->id);

        if ($shipment !== null) {
            return $this->isDeliveredStatus($shipment->status);
        }

        return in_array($order->status, (array) config('reward_coins.confirm_on_statuses', ['completed']), true);
    }

    /**
     * Open windows that a missed webhook, an event-less save or a crashed
     * worker never opened. Delivery time is the Shiprocket record's last update
     * (or the order's, for manual fulfilment) — the best evidence recorded.
     * Safe to run repeatedly.
     *
     * @param  int  $chunkSize
     * @return int  Orders stamped.
     */
    public function reconcileMissingWindows(int $chunkSize = 200): int
    {
        $stamped = 0;

        foreach ($this->ledger->lazyOrdersMissingAvailability($chunkSize) as $orderId) {
            try {
                $order = OrderProxy::modelClass()::query()->find($orderId);

                if (! $order || ! $this->isDelivered($order)) {
                    continue;
                }

                $deliveredAt = $this->shiprocketRecord($orderId)?->updated_at ?? $order->updated_at;

                $availableAt = $this->availableAtFor($deliveredAt ? Carbon::parse($deliveredAt) : null);

                if ($this->ledger->stampAvailableAt($orderId, $availableAt) > 0) {
                    $stamped++;
                }
            } catch (Throwable $e) {
                Log::error('RewardCoins: delivery reconciliation failed for order.', [
                    'order_id' => $orderId,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return $stamped;
    }

    /**
     * Read-only audit of return windows already opened, against the delivery
     * rule above. Reports each window that has no valid delivery basis or that
     * opened before the recorded delivery, with a proposed correction. Never
     * writes. Delivery time for Shiprocket orders is the Shiprocket row's last
     * update (the delivered webhook), which is the best evidence stored.
     *
     * @param  int  $chunkSize
     * @return array<int, array<string, mixed>>
     */
    public function auditWindows(int $chunkSize = 200): array
    {
        $window = max(0, (int) CoinSetting::active()->pending_confirmation_days);
        $rows   = [];

        $lots = CoinTransaction::query()
            ->where('type', TransactionType::Earned->value)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNotNull('available_at')
            ->whereNotNull('order_id')
            ->lazyById($chunkSize);

        foreach ($lots as $lot) {
            $order = OrderProxy::modelClass()::query()->find($lot->order_id);

            if (! $order) {
                continue;
            }

            $openedAt  = Carbon::parse($lot->available_at)->subDays($window);
            $shipment  = $this->shiprocketRecord((int) $order->id);
            $isPending = $lot->status->value === 'pending';
            $finding   = null;

            if ($shipment !== null) {
                if (! $this->isDeliveredStatus($shipment->status)) {
                    $finding = [
                        'basis'    => sprintf('Shiprocket status "%s" (not delivered)', $shipment->status),
                        'action'   => $isPending ? 'clear available_at; reopens on delivery' : 'already spendable: review manually',
                        'proposed' => null,
                    ];
                } elseif ($openedAt->lt(Carbon::parse($shipment->updated_at)->subHour())) {
                    $proposed = Carbon::parse($shipment->updated_at)->addDays($window);

                    $finding = [
                        'basis'    => sprintf('opened %s, delivered %s', $openedAt->toDateTimeString(), Carbon::parse($shipment->updated_at)->toDateTimeString()),
                        'action'   => $isPending ? 'move available_at to delivery + window' : 'already spendable: review manually',
                        'proposed' => $proposed->toDateTimeString(),
                    ];
                }
            } elseif (! in_array($order->status, (array) config('reward_coins.confirm_on_statuses', ['completed']), true)) {
                $finding = [
                    'basis'    => sprintf('manual order now "%s" (window opened by a transient status)', $order->status),
                    'action'   => $isPending ? 'clear available_at; reopens on completion' : 'already spendable: review manually',
                    'proposed' => null,
                ];
            }

            if ($finding === null) {
                continue;
            }

            $rows[] = [
                'order'             => (string) $order->increment_id,
                'lot'               => (int) $lot->id,
                'coins'             => (int) $lot->amount,
                'lot_status'        => $lot->status->value,
                'current_available' => Carbon::parse($lot->available_at)->toDateTimeString(),
                'basis'             => $finding['basis'],
                'proposed_action'   => $finding['action'],
                'proposed_available' => $finding['proposed'] ?? '-',
            ];
        }

        return $rows;
    }

    /**
     * Whether a carrier status string means delivered (exact match, so
     * "RTO Delivered" never counts).
     *
     * @param  string|null  $status
     * @return bool
     */
    public function isDeliveredStatus(?string $status): bool
    {
        $delivered = array_map('strtolower', (array) config('reward_coins.delivery.delivered_statuses', ['delivered']));

        return $status !== null && in_array(strtolower(trim($status)), $delivered, true);
    }

    /**
     * The order's Shiprocket row (status, updated_at), if the integration
     * table exists and holds one. `updated_at` is the recorded delivery time
     * when the table has one, so later edits to the row never move it.
     *
     * @param  int  $orderId
     * @return object|null
     */
    private function shiprocketRecord(int $orderId): ?object
    {
        $table = config('reward_coins.delivery.shipment_table');

        $this->hasShipmentTable ??= $table && Schema::hasTable($table);

        if (! $this->hasShipmentTable) {
            return null;
        }

        $this->hasDeliveredAt ??= Schema::hasColumn($table, 'delivered_at');

        return DB::table($table)
            ->where('order_id', $orderId)
            ->first(['status', $this->hasDeliveredAt ? DB::raw('COALESCE(delivered_at, updated_at) as updated_at') : 'updated_at']);
    }
}
