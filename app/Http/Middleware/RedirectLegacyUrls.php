<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
                return redirect()->to($target, 301);
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
            return $map[$m[1]][$m[2]] ?? null;
        }

        if (preg_match('~^blogs/(([^/]+).*)$~', $path, $m)) {
            return $map['blogs'][$m[1]] ?? (str_contains($m[1], '/') ? $map['blogs'][$m[2].'/*'] ?? null : null);
        }

        return $map['products'][$path] ?? $map['paths'][$path] ?? null;
    }
}
