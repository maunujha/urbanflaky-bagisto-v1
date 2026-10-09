<?php

namespace App\Support;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;

/**
 * Full-page-cache profile that never stores ?page=2+.
 *
 * ResponseCacheHasher gives later pages their own cache key (so they are not
 * served page 1), but Webkul's FPC listeners only ever forget the bare URL —
 * a stored /blog?page=2 would go stale. Later pages are rarely hit, so they
 * are simply rendered fresh.
 */
class ResponseCacheProfile extends CacheAllSuccessfulGetRequests
{
    public function shouldCacheRequest(Request $request): bool
    {
        $page = $request->query('page');

        if (is_string($page) && ctype_digit($page) && (int) $page > 1) {
            return false;
        }

        return parent::shouldCacheRequest($request);
    }
}
