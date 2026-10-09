<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * 301s old Shopify URLs to their current equivalents (config/legacy-redirects.php),
 * in a single hop and before routing, so the catch-all catalog route never 404s them.
 */
class RedirectLegacyUrls
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            if ($target = $this->resolve($request->path())) {
                /* Relative Location: the browser keeps the request's scheme/host, so the
                   redirect stays one hop even before trusted-proxy headers are applied. */
                return new RedirectResponse($target, 301);
            }
        }

        return $next($request);
    }

    protected function resolve(string $path): ?string
    {
        $path = strtolower(trim(rawurldecode($path), '/'));

        /* Shopify served a Hindi copy of every page under /hi/. */
        if (str_starts_with($path, 'hi/')) {
            $path = substr($path, 3);
        }

        $map = config('legacy-redirects');

        if (preg_match('~^(products|collections|pages|policies)/([^/]+)$~', $path, $m)) {
            if (! $target = $map[$m[1]][$m[2]] ?? null) {
                return null;
            }

            /* A live product reusing the old Shopify slug wins over the mapped replacement. */
            return $m[1] === 'products' && $this->slugInUse($m[2]) ? '/'.$m[2] : $target;
        }

        if (preg_match('~^blogs/(([^/]+).*)$~', $path, $m)) {
            return $map['blogs'][$m[1]] ?? (str_contains($m[1], '/') ? $map['blogs'][$m[2].'/*'] ?? null : null);
        }

        if ($target = $map['paths'][$path] ?? null) {
            return $target;
        }

        /* Bare /{slug}: only while no live product or category owns that slug —
           a legacy key reused for a real page must keep serving that page. */
        if (($target = $map['products'][$path] ?? null) && ! $this->slugInUse($path)) {
            return $target;
        }

        return null;
    }

    protected function slugInUse(string $slug): bool
    {
        return DB::table('product_flat')->where('url_key', $slug)->where('status', 1)->exists()
            || DB::table('category_translations')->where('slug', $slug)->exists();
    }
}
