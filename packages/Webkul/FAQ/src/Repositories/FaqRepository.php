<?php

namespace Webkul\FAQ\Repositories;

use Webkul\Core\Eloquent\Repository;

class FaqRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return \Webkul\FAQ\Models\Faq::class;
    }
}
