<?php

namespace App\Support;

use Illuminate\Http\Request;
use Webkul\FPC\Hasher\DefaultHasher;

/**
 * Full-page-cache key that keeps pagination.
 *
 * Webkul's hasher drops every query string (except ?query= on search), so
 * /blog?page=2 was served the cached /blog page and later pages were
 * unreachable. Only a positive whole-number `page` is added to the key, which
 * keeps tracking params (utm_*, gclid, …) collapsed onto one cache entry.
 */
class ResponseCacheHasher extends DefaultHasher
{
    protected function getNormalizedRequestUri(Request $request): string
    {
        $uri = parent::getNormalizedRequestUri($request);

        $page = $request->query('page');

        if (! is_string($page) || ! ctype_digit($page) || (int) $page <= 1) {
            return $uri;
        }

        return $uri.(str_contains($uri, '?') ? '&' : '?').'page='.(int) $page;
    }
}
