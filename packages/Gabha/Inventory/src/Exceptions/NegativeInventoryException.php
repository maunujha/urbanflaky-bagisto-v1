<?php

declare(strict_types=1);

namespace Gabha\Inventory\Exceptions;

use Exception;

/**
 * Thrown when a stock movement would drive on-hand inventory below zero.
 *
 * Lets {@see \Gabha\Inventory\Services\StockMovementService} reject an oversell
 * before any record is written, and lets the controller surface a friendly
 * validation error.
 */
class NegativeInventoryException extends Exception
{
    public function __construct(
        public readonly int $available,
        public readonly int $requested,
    ) {
        parent::__construct(
            trans('inventory::app.admin.movements.negative-stock', [
                'available' => $available,
                'requested' => $requested,
            ])
        );
    }
}
