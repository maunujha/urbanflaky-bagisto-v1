<?php

namespace Webkul\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SecureHeaders
{
    /**
     * Unwanted header list.
     *
     * @var array
     */
    private $unwantedHeaderList = [];

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->removeUnwantedHeaders();

        $response = $next($request);

        $this->setHeaders($response);

        return $response;
    }

    /**
     * Set headers.
     *
     * @param  Response  $response
     * @return void
     */
    private function setHeaders($response)
    {
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        /*
         * frame-ancestors is the modern clickjacking control (superset of
         * X-Frame-Options). It only governs who may frame us, so it is safe to set
         * unconditionally — it does not restrict scripts/styles/images and cannot
         * break the Razorpay or reCAPTCHA integrations. A full resource-restricting
         * CSP (default-src/script-src) is intentionally left out here because it
         * needs per-integration allow-listing and browser verification first.
         */
        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'");
        }
    }

    /**
     * Remove unwanted headers.
     *
     * @return void
     */
    private function removeUnwantedHeaders()
    {
        if (headers_sent()) {
            return;
        }

        foreach ($this->unwantedHeaderList as $header) {
            header_remove($header);
        }
    }
}
