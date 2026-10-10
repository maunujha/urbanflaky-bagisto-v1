<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locks out an IP after repeated failed webhook authentications.
 *
 * Only 401 responses count, so a carrier sending valid tokens is never
 * throttled however hard it bursts or retries; a source guessing tokens is
 * cut off before reaching the controller.
 *
 * Usage: ThrottleFailedWebhookAuth::class.':{maxFailures},{decayMinutes}'
 */
class ThrottleFailedWebhookAuth
{
    public function handle(Request $request, Closure $next, int $maxFailures = 10, int $decayMinutes = 10): Response
    {
        $key = 'webhook-auth-failures:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, $maxFailures)) {
            Log::warning('Webhook: IP locked out after failed authentications', [
                'ip'   => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json(['message' => 'Too Many Attempts.'], 429, [
                'Retry-After' => RateLimiter::availableIn($key),
            ]);
        }

        $response = $next($request);

        if ($response->getStatusCode() === Response::HTTP_UNAUTHORIZED) {
            RateLimiter::hit($key, $decayMinutes * 60);
        }

        return $response;
    }
}
