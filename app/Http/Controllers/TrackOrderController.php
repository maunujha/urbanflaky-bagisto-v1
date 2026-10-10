<?php

namespace App\Http\Controllers;

use App\Models\ShiprocketOrder;
use App\Services\Shiprocket\ShipmentStage;
use App\Services\Shiprocket\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Webkul\Sales\Models\Order;

/**
 * Public order tracking at /track-order.
 *
 * Two ways in: order number + the email or phone used on the order, or an
 * AWB this store issued. Unknown input and wrong contact details get the same
 * answer, so the page can't be used to discover orders. Answers come from our
 * own shipment history, refreshed from the courier at most every few minutes.
 */
class TrackOrderController extends Controller
{
    public function __construct(protected TrackingService $tracking) {}

    public function index(Request $request): View
    {
        return view('shop::track-order.index', [
            'prefillAwb'   => mb_substr((string) $request->query('awb', ''), 0, 40),
            'prefillOrder' => mb_substr((string) $request->query('order', ''), 0, 20),
        ]);
    }

    public function track(Request $request): JsonResponse
    {
        $data = $request->validate([
            'awb'      => 'nullable|required_without:order_id|string|max:40',
            'order_id' => 'nullable|required_without:awb|string|max:20',
            'contact'  => 'nullable|required_with:order_id|string|max:120',
        ]);

        $byAwb = filled($data['awb'] ?? null);

        [$order, $shipment] = $byAwb
            ? $this->byAwb(trim($data['awb']))
            : $this->byOrder(ltrim(trim($data['order_id']), '#'), trim($data['contact']));

        if (! $order) {
            return response()->json([
                'found'   => false,
                'message' => $byAwb
                    ? 'We couldn’t find that tracking number. Check the AWB in your shipping email or SMS, or track with your order number instead.'
                    : 'We couldn’t find an order with those details. Use the order number from your confirmation email and the email or phone number you ordered with.',
            ]);
        }

        if ($shipment) {
            $this->refreshIfStale($shipment);
        }

        /* An AWB alone proves nothing about who is asking: shipment progress
           only, never the order number, destination or order date. */
        return response()->json($this->present($order, $shipment?->refresh(), detailed: ! $byAwb));
    }

    /**
     * @return array{0: ?Order, 1: ?ShiprocketOrder}
     */
    protected function byAwb(string $awb): array
    {
        $shipment = ShiprocketOrder::with('order')->where('awb_code', $awb)->first();

        return [$shipment?->order, $shipment];
    }

    /**
     * @return array{0: ?Order, 1: ?ShiprocketOrder}
     */
    protected function byOrder(string $incrementId, string $contact): array
    {
        $order = Order::with(['addresses'])->where('increment_id', $incrementId)->first();

        if (! $order || ! $this->contactMatches($order, $contact)) {
            return [null, null];
        }

        return [$order, ShiprocketOrder::where('order_id', $order->id)->first()];
    }

    /** Email (any case) or the last 10 digits of a phone on the order. */
    protected function contactMatches(Order $order, string $contact): bool
    {
        if (str_contains($contact, '@')) {
            $emails = collect([$order->customer_email])
                ->merge($order->addresses->pluck('email'))
                ->filter()
                ->map(fn ($e) => mb_strtolower(trim($e)));

            return $emails->contains(mb_strtolower($contact));
        }

        $digits = substr(preg_replace('/\D/', '', $contact), -10);

        return strlen($digits) === 10 && $order->addresses
            ->pluck('phone')
            ->filter()
            ->contains(fn ($phone) => substr(preg_replace('/\D/', '', $phone), -10) === $digits);
    }

    /**
     * Pull the courier's latest scans when our record is older than the
     * live-refresh window; one refresh per AWB per window, whoever asks.
     */
    protected function refreshIfStale(ShiprocketOrder $shipment): void
    {
        if (! $shipment->hasAwb() || ! ShipmentStage::isActive($shipment->status)) {
            return;
        }

        $minutes = (int) config('shiprocket.sync.live_refresh_minutes', 15);
        $fresh   = now()->subMinutes($minutes);

        if (($shipment->last_synced_at && $shipment->last_synced_at->gt($fresh))
            || ! Cache::add('shiprocket:live:'.$shipment->awb_code, 1, $minutes * 60)) {
            return;
        }

        try {
            $this->tracking->refresh($shipment);
        } catch (\Throwable $e) {
            Log::warning('Live tracking refresh failed', ['awb' => $shipment->awb_code, 'error' => $e->getMessage()]);
        }
    }

    protected function present(Order $order, ?ShiprocketOrder $shipment, bool $detailed): array
    {
        $stage = $shipment?->status;

        if ($order->status === Order::STATUS_CANCELED) {
            $stage = ShipmentStage::CANCELED;
        }

        $address = $order->shipping_address;

        return [
            'found'          => true,
            'order_id'       => $detailed ? (string) $order->increment_id : null,
            'awb'            => $shipment?->awb_code,
            'courier'        => $shipment?->courier_name,
            'current_status' => ShipmentStage::label($stage),
            'courier_status' => $shipment?->current_status,
            'stage'          => ShipmentStage::progress($stage),
            'state'          => $stage ?? 'new',
            'destination'    => $detailed && $address ? trim($address->city.', '.$address->state, ', ') : null,
            'edd'            => ShipmentStage::isActive($stage) ? $shipment?->etd?->format('D, d M') : null,
            'delivered_date' => $shipment?->delivered_at?->format('D, d M Y'),
            'placed_on'      => $detailed ? $order->created_at->format('D, d M Y') : null,
            'note'           => $this->note($stage, $shipment),
            'activities'     => $shipment
                ? $shipment->events()->limit(40)->get()->map(fn ($e) => [
                    'date'     => $e->event_at?->toIso8601String(),
                    'activity' => $e->activity,
                    'location' => $e->location,
                ])->all()
                : [],
        ];
    }

    protected function note(?string $stage, ?ShiprocketOrder $shipment): ?string
    {
        return match (true) {
            $stage === ShipmentStage::CANCELED      => 'This order was cancelled.',
            $stage === ShipmentStage::UNDELIVERED   => 'The courier couldn’t complete delivery and will try again. Keep your phone reachable, or contact us to update the address.',
            in_array($stage, [ShipmentStage::RTO_INITIATED, ShipmentStage::RTO_DELIVERED], true) => 'This parcel is on its way back to us. Contact support and we’ll sort it out.',
            $stage === ShipmentStage::LOST          => 'This parcel is delayed with the courier. Our team is on it; contact support for an update.',
            ! $shipment?->hasAwb()                  => 'We’re preparing your order. Tracking appears here as soon as it ships.',
            default                                 => null,
        };
    }
}
