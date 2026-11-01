<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\Customer\Facades\Captcha;

/**
 * Strict reCAPTCHA v3 check for actions that cost money or send mail
 * (SMS OTP send, newsletter signup).
 *
 * Unlike the stock `captcha` rule it also binds the token to the expected
 * action and hostname, and refuses replays locally (in addition to Google's
 * own timeout-or-duplicate). The secret key is read from admin config server
 * side and never rendered.
 */
class RecaptchaVerifier
{
    public const OK = 'ok';

    public const DISABLED = 'disabled';

    public const MISSING = 'missing';

    public const REUSED = 'reused';

    public const INVALID = 'invalid';

    public const ACTION_MISMATCH = 'action_mismatch';

    public const HOSTNAME_MISMATCH = 'hostname_mismatch';

    public const LOW_SCORE = 'low_score';

    public const TRANSPORT_ERROR = 'transport_error';

    public function __construct(protected AtomicLimiter $limiter) {}

    /**
     * True when the request should be allowed through.
     */
    public function passes(?string $token, string $action): bool
    {
        $verdict = $this->verify($token, $action);

        return in_array($verdict, [self::OK, self::DISABLED], true)
            || ($verdict === self::TRANSPORT_ERROR && config('otp_security.recaptcha.fail_open_on_transport_error'));
    }

    public function verify(?string $token, string $action): string
    {
        if (! Captcha::isActive()) {
            return self::DISABLED;
        }

        $token = is_string($token) ? trim($token) : '';

        /* Real v3 tokens are long; this also bounds what we send upstream. */
        if ($token === '' || strlen($token) > 4096) {
            return $this->reject(self::MISSING, $action);
        }

        if (! $this->limiter->claimOnce('rc:used:'.hash('sha256', $token), 300)) {
            return $this->reject(self::REUSED, $action);
        }

        $secret = Captcha::getApiKey();

        if (empty($secret)) {
            Log::error('reCAPTCHA: secret key not configured.');

            return self::TRANSPORT_ERROR;
        }

        try {
            $response = Http::asForm()
                ->timeout(config('otp_security.recaptcha.timeout_seconds'))
                ->post(Captcha::getSiteVerifyEndpoint(), [
                    'secret'   => $secret,
                    'response' => $token,
                ]);
        } catch (\Throwable $e) {
            Log::warning('reCAPTCHA: siteverify unreachable.', ['action' => $action, 'error' => $e->getMessage()]);

            return self::TRANSPORT_ERROR;
        }

        if ($response->serverError() || ! is_array($result = $response->json())) {
            Log::warning('reCAPTCHA: siteverify unusable response.', ['action' => $action, 'status' => $response->status()]);

            return self::TRANSPORT_ERROR;
        }

        /* Covers forged, expired and Google-detected duplicate tokens. */
        if (empty($result['success'])) {
            return $this->reject(self::INVALID, $action, ['codes' => $result['error-codes'] ?? []]);
        }

        if (($result['action'] ?? null) !== $action) {
            return $this->reject(self::ACTION_MISMATCH, $action, ['got' => $result['action'] ?? null]);
        }

        if (! in_array(strtolower((string) ($result['hostname'] ?? '')), $this->allowedHostnames(), true)) {
            return $this->reject(self::HOSTNAME_MISMATCH, $action, ['got' => $result['hostname'] ?? null]);
        }

        $score = (float) ($result['score'] ?? 0);

        if ($score < $this->minScore()) {
            return $this->reject(self::LOW_SCORE, $action, ['score' => $score]);
        }

        return self::OK;
    }

    public function minScore(): float
    {
        $configured = config('otp_security.recaptcha.min_score');

        return is_numeric($configured) ? (float) $configured : Captcha::getScoreThreshold();
    }

    /**
     * @return list<string>
     */
    public function allowedHostnames(): array
    {
        $configured = config('otp_security.recaptcha.allowed_hostnames');

        if (filled($configured)) {
            return array_values(array_filter(array_map(
                fn ($h) => strtolower(trim($h)),
                explode(',', $configured)
            )));
        }

        $host = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);

        return [$host, 'www.'.$host];
    }

    private function reject(string $verdict, string $action, array $context = []): string
    {
        Log::notice('reCAPTCHA: rejected.', ['action' => $action, 'verdict' => $verdict, 'ip' => ClientIp::bucket(request())] + $context);

        return $verdict;
    }
}
