<?php

declare(strict_types=1);

namespace Gabha\Inventory\Repositories;

use Gabha\Inventory\Models\PurchaseItem;
use Webkul\Core\Eloquent\Repository;

class PurchaseItemRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return PurchaseItem::class;
    }
}
