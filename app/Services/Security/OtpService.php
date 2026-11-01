<?php

namespace App\Services\Security;

use App\Mail\OtpCeilingAlert;
use App\Support\IndianMobile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Webkul\Shop\Services\SmsAlertService;

/**
 * SMS OTP issue + verification shared by the contact and checkout flows.
 *
 * The code itself lives in Redis (HMAC only, never plaintext), keyed by a
 * random challenge id that is the only thing stored in the session. Redis —
 * not the session — owns the attempt counter, because concurrent requests on
 * one session would each read a stale session copy and could reset it.
 *
 * Session keys per flow ({flow} = contact|checkout) are kept compatible with
 * the existing checks: {flow}_otp_phone and {flow}_phone_verified.
 */
class OtpService
{
    public const FLOWS = ['contact', 'checkout', 'login', 'register'];

    /*
     * KEYS[1] = challenge hash; ARGV[1] = submitted HMAC, ARGV[2] = phone,
     * ARGV[3] = max attempts. Returns {code, remaining}.
     *   0 missing/expired · 1 phone mismatch · 2 already locked
     *   3 success (challenge consumed) · 4 wrong + now locked · 5 wrong
     */
    private const VERIFY = <<<'LUA'
local c = redis.call('HMGET', KEYS[1], 'h', 'p', 'a')
if not c[1] then return {0, 0} end
if c[2] ~= ARGV[2] then return {1, 0} end
local max = tonumber(ARGV[3])
if tonumber(c[3] or '0') >= max then
    redis.call('DEL', KEYS[1])
    return {2, 0}
end
if c[1] == ARGV[1] then
    redis.call('DEL', KEYS[1])
    return {3, 0}
end
local a = redis.call('HINCRBY', KEYS[1], 'a', 1)
if a >= max then
    redis.call('DEL', KEYS[1])
    return {4, 0}
end
return {5, max - a}
LUA;

    public function __construct(
        protected AtomicLimiter $limiter,
        protected SmsAlertService $sms,
    ) {}

    /**
     * Reserve send capacity, create a fresh challenge and send the SMS.
     *
     * @return array{ok: bool, status: int, message: string}
     */
    public function send(string $flow, string $phone, Request $request): array
    {
        $this->assertFlow($flow);

        $limit = $this->limiter->reserve($this->sendBuckets($flow, $phone, $request));

        if (! $limit['allowed']) {
            $this->logEvent('otp.send.limited', $flow, $phone, $request, ['bucket' => $limit['bucket']]);

            if (str_starts_with((string) $limit['bucket'], 'site')) {
                $this->alertCeiling($flow, $limit['bucket']);
            }

            return ['ok' => false, 'code' => 'limited', 'status' => 429, 'message' => $this->limitMessage($limit)];
        }

        $otp = (string) random_int(100000, 999999);
        $id = Str::random(40);
        $ttl = max(60, (int) config('otp_security.expiry_minutes') * 60);
        $redis = $this->limiter->redis();

        /* A new send supersedes (and kills) the previous code for this session. */
        if ($previous = session($flow.'_otp_challenge')) {
            $redis->eval("return redis.call('DEL', KEYS[1])", 1, $this->challengeKey($flow, $previous));
        }

        $redis->eval(
            "redis.call('HSET', KEYS[1], 'h', ARGV[1], 'p', ARGV[2], 'a', 0) return redis.call('EXPIRE', KEYS[1], tonumber(ARGV[3]))",
            1,
            $this->challengeKey($flow, $id),
            $this->hash($flow, $id, $otp),
            $phone,
            $ttl
        );

        session([
            $flow.'_otp_challenge'  => $id,
            $flow.'_otp_phone'      => $phone,
            $flow.'_phone_verified' => false,
        ]);

        if (! $this->sms->sendOtp($phone, $otp)) {
            $this->logEvent('otp.send.provider_failed', $flow, $phone, $request);

            return ['ok' => false, 'code' => 'provider_failed', 'status' => 502, 'message' => 'We couldn’t send the OTP right now. Please try again in a moment.'];
        }

        $this->logEvent('otp.send.ok', $flow, $phone, $request);

        return ['ok' => true, 'code' => 'sent', 'status' => 200, 'message' => 'OTP sent to +91 ••••••'.substr($phone, -4).'.'];
    }

    /**
     * @return array{ok: bool, status: int, message: string}
     */
    public function verify(string $flow, string $phone, string $otp, Request $request): array
    {
        $this->assertFlow($flow);

        $limit = $this->limiter->reserve([
            'verify_ip' => [
                'key'   => 'otp:verify:ip:'.ClientIp::bucket($request),
                'limit' => config('otp_security.verify.per_ip_window_max'),
                'ttl'   => config('otp_security.verify.per_ip_window_seconds'),
            ],
        ]);

        if (! $limit['allowed']) {
            return ['ok' => false, 'code' => 'limited', 'status' => 429, 'message' => $this->limitMessage($limit)];
        }

        $id = session($flow.'_otp_challenge');

        if (! $id) {
            return ['ok' => false, 'code' => 'expired', 'status' => 422, 'message' => 'Your OTP has expired. Please request a new one.'];
        }

        [$code, $remaining] = array_map('intval', (array) $this->limiter->redis()->eval(
            self::VERIFY,
            1,
            $this->challengeKey($flow, $id),
            $this->hash($flow, $id, $otp),
            $phone,
            (int) config('otp_security.verify.max_attempts_per_challenge')
        )) + [0, 0];

        switch ($code) {
            case 3:
                session()->forget($flow.'_otp_challenge');
                session([$flow.'_otp_phone' => $phone, $flow.'_phone_verified' => true]);
                $this->logEvent('otp.verify.ok', $flow, $phone, $request);

                return ['ok' => true, 'code' => 'verified', 'status' => 200, 'message' => 'Mobile number verified successfully.'];

            case 1:
                return ['ok' => false, 'code' => 'mismatch', 'status' => 422, 'message' => 'This number doesn’t match the one we sent the OTP to. Request a new OTP.'];

            case 5:
                return ['ok' => false, 'code' => 'wrong', 'status' => 422, 'message' => 'Incorrect OTP. '.$remaining.' '.Str::plural('attempt', $remaining).' left.'];

            case 2:
            case 4:
                session()->forget($flow.'_otp_challenge');
                $this->logEvent('otp.verify.locked', $flow, $phone, $request);

                return ['ok' => false, 'code' => 'locked', 'status' => 422, 'message' => 'Too many incorrect attempts. Please request a new OTP.'];

            default:
                session()->forget($flow.'_otp_challenge');

                return ['ok' => false, 'code' => 'expired', 'status' => 422, 'message' => 'Your OTP has expired. Please request a new one.'];
        }
    }

    /**
     * @return array<string, array{key: string, limit: int, ttl: int}>
     */
    protected function sendBuckets(string $flow, string $phone, Request $request): array
    {
        $c = config('otp_security.send');
        $day = now()->format('Ymd');

        $buckets = [
            /* Per phone, per flow — contact abuse never throttles a checkout. */
            'phone_window' => ['key' => "otp:send:{$flow}:phone:{$phone}:w", 'limit' => $c['per_phone_window_max'], 'ttl' => $c['per_phone_window_seconds']],
            'phone_daily'  => ['key' => "otp:send:{$flow}:phone:{$phone}:d", 'limit' => $c['per_phone_daily_max'], 'ttl' => 86400],
            /* Shared across flows. */
            'ip_daily'     => ['key' => 'otp:send:ip:'.ClientIp::bucket($request).':d', 'limit' => $c['per_ip_daily_max'], 'ttl' => 86400],
            'site_daily'   => ['key' => "otp:send:site:{$day}", 'limit' => $c['site_daily_max'], 'ttl' => 172800],
        ];

        /*
         * Every flow except checkout draws from a shared sub-ceiling, and the
         * contact form from its own smaller one too — so abuse of contact,
         * login or signup can never consume the checkout share of the budget.
         */
        if ($flow !== 'checkout') {
            $buckets['site_daily_non_checkout'] = ['key' => "otp:send:site:non_checkout:{$day}", 'limit' => $c['site_daily_max_non_checkout'], 'ttl' => 172800];
        }

        if ($flow === 'contact') {
            $buckets['site_daily_contact'] = ['key' => "otp:send:site:contact:{$day}", 'limit' => $c['site_daily_max_contact'], 'ttl' => 172800];
        }

        return $buckets;
    }

    /**
     * Log, and email the store at most once per ceiling per day. Never lets an
     * alerting failure affect the request being refused.
     */
    protected function alertCeiling(string $flow, string $bucket): void
    {
        Log::critical('OTP site-wide daily SMS ceiling reached.', ['flow' => $flow, 'bucket' => $bucket]);

        try {
            if ($this->limiter->claimOnce('otp:alert:'.now()->format('Ymd').':'.$bucket, 172800)) {
                Mail::queue(new OtpCeilingAlert($bucket, $flow));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function limitMessage(array $limit): string
    {
        return match (true) {
            str_starts_with((string) $limit['bucket'], 'site') => 'OTP service is busy right now. Please try again later or contact us on WhatsApp.',
            $limit['bucket'] === 'phone_window' => 'Too many OTP requests for this number. Please try again in '.$this->humanWait($limit['retry_after']).'.',
            default => 'Too many attempts. Please try again in '.$this->humanWait($limit['retry_after']).'.',
        };
    }

    protected function humanWait(int $seconds): string
    {
        if ($seconds >= 3600) {
            $h = (int) ceil($seconds / 3600);

            return $h.' '.Str::plural('hour', $h);
        }

        $m = max(1, (int) ceil($seconds / 60));

        return $m.' '.Str::plural('minute', $m);
    }

    protected function challengeKey(string $flow, string $id): string
    {
        return $this->limiter->key("otp:challenge:{$flow}:{$id}");
    }

    /* Bound to flow + challenge so a hash can't be replayed across challenges. */
    protected function hash(string $flow, string $id, string $otp): string
    {
        return hash_hmac('sha256', "{$flow}|{$id}|{$otp}", (string) config('app.key'));
    }

    protected function assertFlow(string $flow): void
    {
        if (! in_array($flow, self::FLOWS, true)) {
            throw new \InvalidArgumentException("Unknown OTP flow [{$flow}].");
        }
    }

    protected function logEvent(string $event, string $flow, string $phone, Request $request, array $context = []): void
    {
        Log::info($event, ['flow' => $flow, 'phone' => IndianMobile::logTag($phone), 'ip' => ClientIp::bucket($request)] + $context);
    }
}
