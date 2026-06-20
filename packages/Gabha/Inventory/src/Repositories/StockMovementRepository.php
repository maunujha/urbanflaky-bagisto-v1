<?php

declare(strict_types=1);

namespace Gabha\Inventory\Repositories;

use Gabha\Inventory\Models\StockMovement;
use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;

class StockMovementRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return StockMovement::class;
    }

    /**
     * Generate the next per-year, zero-padded movement number,
     * e.g. SM-2026-000001. Uniqueness is enforced by the table index.
     */
    public function generateMovementNumber(): string
    {
        $prefix = 'SM-'.now()->format('Y').'-';

        $last = $this->model
            ->newQuery()
            ->where('movement_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('movement_number');

        $sequence = $last
            ? ((int) Str::after($last, $prefix)) + 1
            : 1;

        return $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
