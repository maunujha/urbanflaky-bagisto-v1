<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetTrendingSearches extends Command
{
    protected $signature   = 'search:reset-trending';
    protected $description = 'Reset search term use counts (run weekly to keep trending fresh)';

    public function handle(): void
    {
        DB::table('search_terms')->update(['uses' => 0]);
        $this->info('Search term counts reset successfully.');
    }
}
