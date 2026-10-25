<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * State the 7-day return window on /return-policy so the page matches the
 * merchantReturnDays the Product schema declares. Only the original
 * open-ended sentence is replaced; a page already edited in the admin is
 * left untouched.
 */
return new class extends Migration
{
    const OLD = 'Customers must initiate a return request within the return period specified on the product page or order details. Requests submitted after the eligible return period may not be accepted.';

    const NEW = 'Returns are accepted within 7 days of delivery, except Made-on-Demand and customised items. Requests submitted after this window may not be accepted.';

    public function up(): void
    {
        $this->swap(self::OLD, self::NEW);
    }

    public function down(): void
    {
        $this->swap(self::NEW, self::OLD);
    }

    protected function swap(string $from, string $to): void
    {
        DB::table('cms_page_translations')
            ->where('url_key', 'return-policy')
            ->where('html_content', 'like', '%'.$from.'%')
            ->update(['html_content' => DB::raw('REPLACE(html_content, '.DB::getPdo()->quote($from).', '.DB::getPdo()->quote($to).')')]);
    }
};
