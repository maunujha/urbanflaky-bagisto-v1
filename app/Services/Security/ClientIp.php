<?php

namespace App\Services\Security;

use Illuminate\Http\Request;

/**
 * Rate-limit bucket for the requesting client.
 *
 * request()->ip() is REMOTE_ADDR unless TRUSTED_PROXIES is set (it is empty in
 * production: nginx talks to php-fpm directly), so X-Forwarded-For cannot be
 * used to spoof a fresh bucket. IPv6 clients are grouped by /64 — a single
 * subscriber usually owns a whole /64 and could otherwise rotate addresses
 * for free. Missing/invalid addresses share one conservative "unknown" bucket.
 */
class ClientIp
{
    public static function bucket(Request $request): string
    {
        $ip = $request->ip();

        if (! is_string($ip) || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return 'unknown';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = inet_pton($ip);

            return 'v6:'.bin2hex(substr($packed, 0, 8));
        }

        return 'v4:'.$ip;
    }
}
