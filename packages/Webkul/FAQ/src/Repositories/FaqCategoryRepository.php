<?php

namespace Webkul\FAQ\Repositories;

use Webkul\Core\Eloquent\Repository;

class FaqCategoryRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return \Webkul\FAQ\Models\FaqCategory::class;
    }
}
