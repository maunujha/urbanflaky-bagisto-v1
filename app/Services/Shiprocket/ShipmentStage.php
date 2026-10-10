<?php

namespace App\Services\Shiprocket;

/**
 * Normalised shipment stages. Shiprocket and its couriers report dozens of
 * status strings; everything the store reacts to is keyed off these codes,
 * which are what shiprocket_orders.status holds.
 */
final class ShipmentStage
{
    public const PUSH_FAILED = 'push_failed';
    public const NEW = 'new';
    public const AWB_ASSIGNED = 'awb_assigned';
    public const PICKUP_SCHEDULED = 'pickup_scheduled';
    public const PICKED_UP = 'picked_up';
    public const IN_TRANSIT = 'in_transit';
    public const UNDELIVERED = 'undelivered';
    public const OUT_FOR_DELIVERY = 'out_for_delivery';
    public const DELIVERED = 'delivered';
    public const RTO_INITIATED = 'rto_initiated';
    public const RTO_DELIVERED = 'rto_delivered';
    public const CANCELED = 'canceled';
    public const LOST = 'lost';

    /** Forward order. Equal ranks may replace each other (NDR ↔ out for delivery). */
    private const RANK = [
        self::PUSH_FAILED      => 0,
        self::NEW              => 1,
        self::AWB_ASSIGNED     => 2,
        self::PICKUP_SCHEDULED => 3,
        self::PICKED_UP        => 4,
        self::IN_TRANSIT       => 5,
        self::UNDELIVERED      => 6,
        self::OUT_FOR_DELIVERY => 6,
        self::DELIVERED        => 7,
        self::RTO_INITIATED    => 8,
        self::RTO_DELIVERED    => 9,
        self::CANCELED         => 10,
        self::LOST             => 10,
    ];

    /** No further courier update changes these. */
    public const TERMINAL = [self::DELIVERED, self::RTO_DELIVERED, self::CANCELED, self::LOST];

    /** Moving towards the customer: these (and only these) mean "shipped" to them. */
    public const FORWARD = [
        self::PICKED_UP, self::IN_TRANSIT, self::UNDELIVERED, self::OUT_FOR_DELIVERY, self::DELIVERED,
    ];

    /** The parcel has physically left the warehouse. */
    public const IN_CARRIER_HANDS = [
        self::PICKED_UP, self::IN_TRANSIT, self::UNDELIVERED, self::OUT_FOR_DELIVERY,
        self::DELIVERED, self::RTO_INITIATED, self::RTO_DELIVERED, self::LOST,
    ];

    /**
     * Most specific first: "RTO DELIVERED" must not read as "DELIVERED",
     * nor "OUT FOR PICKUP" as "PICKED UP".
     */
    private const PATTERNS = [
        self::RTO_DELIVERED    => ['RTO DELIVERED', 'RTO_DELIVERED', 'RETURN DELIVERED'],
        self::RTO_INITIATED    => ['RTO', 'RETURN TO ORIGIN'],
        self::CANCELED         => ['CANCEL'],
        self::LOST             => ['LOST', 'DAMAGED', 'DESTROYED', 'DISPOSED'],
        self::UNDELIVERED      => ['UNDELIVERED', 'NDR', 'DELIVERY FAILED', 'NOT DELIVERED'],
        self::OUT_FOR_DELIVERY => ['OUT FOR DELIVERY', 'OUT_FOR_DELIVERY', 'OFD'],
        self::DELIVERED        => ['DELIVERED'],
        self::PICKUP_SCHEDULED => ['OUT FOR PICKUP', 'PICKUP SCHEDULED', 'PICKUP GENERATED', 'PICKUP QUEUED', 'PICKUP RESCHEDULED', 'PICKUP EXCEPTION', 'PICKUP ERROR', 'MANIFEST', 'PICKUP BOOKED'],
        self::PICKED_UP        => ['PICKED UP', 'PICKED_UP', 'SHIPPED'],
        self::IN_TRANSIT       => ['IN TRANSIT', 'IN_TRANSIT', 'REACHED', 'DESTINATION HUB', 'MISROUTED', 'DELAYED', 'DISPATCHED', 'CONNECTED'],
        self::AWB_ASSIGNED     => ['AWB ASSIGNED', 'LABEL GENERATED', 'INVOICED', 'READY TO SHIP'],
        self::NEW              => ['NEW'],
    ];

    /** Customer-facing wording. */
    private const LABELS = [
        self::PUSH_FAILED      => 'Order confirmed',
        self::NEW              => 'Order confirmed',
        self::AWB_ASSIGNED     => 'Packed',
        self::PICKUP_SCHEDULED => 'Ready for pickup',
        self::PICKED_UP        => 'Shipped',
        self::IN_TRANSIT       => 'In transit',
        self::UNDELIVERED      => 'Delivery attempted',
        self::OUT_FOR_DELIVERY => 'Out for delivery',
        self::DELIVERED        => 'Delivered',
        self::RTO_INITIATED    => 'Returning to us',
        self::RTO_DELIVERED    => 'Returned to us',
        self::CANCELED         => 'Cancelled',
        self::LOST             => 'Delayed, contact support',
    ];

    public static function fromStatus(?string $status): ?string
    {
        $status = strtoupper(trim((string) $status));

        /* A cancellation request can still fail; wait for the outcome. */
        if ($status === '' || str_contains($status, 'CANCELLATION REQUESTED')) {
            return null;
        }

        foreach (self::PATTERNS as $stage => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($status, $needle)) {
                    return $stage;
                }
            }
        }

        return null;
    }

    /**
     * Whether a shipment at $current may move to $next. Out-of-order or
     * replayed courier updates never move a shipment backwards, except that a
     * failed delivery attempt can go back into transit for a reattempt.
     */
    public static function canAdvance(?string $current, string $next): bool
    {
        if ($current === null || ! isset(self::RANK[$current])) {
            return true;
        }

        if ($current === $next || in_array($current, self::TERMINAL, true)) {
            return false;
        }

        if ($current === self::UNDELIVERED && $next === self::IN_TRANSIT) {
            return true;
        }

        return self::RANK[$next] >= self::RANK[$current];
    }

    public static function label(?string $stage): string
    {
        return self::LABELS[$stage] ?? 'Order confirmed';
    }

    public static function isActive(?string $stage): bool
    {
        return ! in_array($stage, self::TERMINAL, true);
    }

    /**
     * Position on the five-step customer progress bar
     * (Confirmed, Shipped, In transit, Out for delivery, Delivered).
     */
    public static function progress(?string $stage): int
    {
        return match ($stage) {
            self::PICKED_UP                         => 1,
            self::IN_TRANSIT, self::UNDELIVERED,
            self::RTO_INITIATED, self::LOST         => 2,
            self::OUT_FOR_DELIVERY                  => 3,
            self::DELIVERED                         => 4,
            default                                 => 0,
        };
    }
}
