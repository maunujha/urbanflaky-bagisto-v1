<?php

declare(strict_types=1);

namespace Gabha\Inventory\Enums;

/**
 * The kinds of stock movement and the direction each applies to on-hand stock.
 *
 * Inbound (PURCHASE, RETURN) add stock; everything else removes it. PURCHASE is
 * created only through the Purchase module — the manual movement screen offers
 * the remaining types via {@see self::manualCases()}.
 */
enum MovementType: string
{
    case PURCHASE = 'purchase';
    case AMAZON_SALE = 'amazon_sale';
    case FLIPKART_SALE = 'flipkart_sale';
    case OFFLINE_SALE = 'offline_sale';
    case RETURN = 'return';
    case DAMAGE = 'damage';

    /**
     * Whether this movement increases stock.
     */
    public function isInbound(): bool
    {
        return in_array($this, [self::PURCHASE, self::RETURN], true);
    }

    /**
     * Direction applied to stock: +1 inbound, -1 outbound.
     */
    public function sign(): int
    {
        return $this->isInbound() ? 1 : -1;
    }

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PURCHASE      => 'Purchase',
            self::AMAZON_SALE   => 'Amazon Sale',
            self::FLIPKART_SALE => 'Flipkart Sale',
            self::OFFLINE_SALE  => 'Offline Sale',
            self::RETURN        => 'Return',
            self::DAMAGE        => 'Damage',
        };
    }

    /**
     * String values of inbound (stock-in) types.
     *
     * @return array<int, string>
     */
    public static function inboundValues(): array
    {
        return [self::PURCHASE->value, self::RETURN->value];
    }

    /**
     * String values of outbound (stock-out) types.
     *
     * @return array<int, string>
     */
    public static function outboundValues(): array
    {
        return [
            self::AMAZON_SALE->value,
            self::FLIPKART_SALE->value,
            self::OFFLINE_SALE->value,
            self::DAMAGE->value,
        ];
    }

    /**
     * Types selectable on the manual movement screen (PURCHASE is excluded —
     * purchases are created through the Purchase module).
     *
     * @return array<int, self>
     */
    public static function manualCases(): array
    {
        return [
            self::AMAZON_SALE,
            self::FLIPKART_SALE,
            self::OFFLINE_SALE,
            self::RETURN,
            self::DAMAGE,
        ];
    }
}
