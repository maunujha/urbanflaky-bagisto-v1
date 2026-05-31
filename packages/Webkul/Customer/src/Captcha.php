<?php

namespace Webkul\Customer;

use Illuminate\Support\Facades\Http;
use Webkul\Customer\Contracts\Captcha as CaptchaContract;

class Captcha implements CaptchaContract
{
    /**
     * Client endpoint.
     */
    const string CLIENT_ENDPOINT = 'https://www.google.com/recaptcha/api.js';

    /**
     * Site verify endpoint (standard reCAPTCHA v3).
     */
    const string SITE_VERIFY_ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Project Id.
     */
    protected ?string $projectId;

    /**
     * API Key.
     */
    protected ?string $apiKey;

    /**
     * Site key.
     */
    protected ?string $siteKey;

    /**
     * Score threshold.
     */
    protected ?float $scoreThreshold;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Check whether captcha is active or not.
     */
    public function isActive(): bool
    {
        return (bool) core()->getConfigData('customer.captcha.credentials.status');
    }

    /**
     * Get project id from the core config.
     */
    public function getProjectId(): ?string
    {
        return core()->getConfigData('customer.captcha.credentials.project_id');
    }

    /**
     * Get api key from the core config.
     */
    public function getApiKey(): ?string
    {
        return core()->getConfigData('customer.captcha.credentials.api_key');
    }

    /**
     * Get site key from the core config.
     */
    public function getSiteKey(): ?string
    {
        return core()->getConfigData('customer.captcha.credentials.site_key');
    }

    /**
     * Get score threshold.
     */
    public function getScoreThreshold(): float
    {
        return (float) core()->getConfigData('customer.captcha.credentials.score_threshold');
    }

    /**
     * Get client endpoint.
     */
    public function getClientEndpoint(): string
    {
        return static::CLIENT_ENDPOINT;
    }

    /**
     * Get site verify endpoint.
     */
    public function getSiteVerifyEndpoint(): string
    {
        return static::SITE_VERIFY_ENDPOINT;
    }

    /**
     * Render JS.
     */
    public function renderJS(): string
    {
        return $this->isActive()
            ? $this->getCaptchaJSView()
            : '';
    }

    /**
     * Render Captcha.
     */
    public function render(): string
    {
        return $this->isActive()
            ? $this->getCaptchaView()
            : '';
    }

    /**
     * Validate response using standard reCAPTCHA v3 siteverify API.
     * Admin "API Key" field = reCAPTCHA v3 Secret Key.
     * Admin "Site Key" field = reCAPTCHA v3 Site Key.
     */
    public function validateResponse($response): bool
    {
        if (empty($response)) {
            logger()->error('reCAPTCHA: Validation failed - empty response token.');

            return false;
        }

        if (empty($this->apiKey) || empty($this->siteKey)) {
            logger()->error('reCAPTCHA: Validation failed - Secret Key or Site Key is not configured.');

            return false;
        }

        try {
            logger()->info('reCAPTCHA: Sending siteverify request.');

            $apiResponse = Http::asForm()->post($this->getSiteVerifyEndpoint(), [
                'secret'   => $this->apiKey,
                'response' => $response,
            ]);

            $result = $apiResponse->json();

            if (! $result || $apiResponse->failed()) {
                logger()->error('reCAPTCHA: Failed to get valid response from Google.', ['response' => $result]);

                return false;
            }

            logger()->info('reCAPTCHA: Siteverify response received.', ['response' => $result]);

            if (isset($result['success']) && $result['success'] && isset($result['score'])) {
                $score = (float) $result['score'];

                $isValid = $score >= $this->scoreThreshold;

                logger()->info('reCAPTCHA: Validation result.', [
                    'score'     => $score,
                    'threshold' => $this->scoreThreshold,
                    'success'   => $isValid,
                ]);

                return $isValid;
            }

            logger()->error('reCAPTCHA: Invalid response or token rejected.', [
                'response' => $result,
            ]);

            return false;
        } catch (\Exception $e) {
            logger()->error('reCAPTCHA: Exception during validation request.', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Fail-open captcha check for high-value actions (e.g. order placement).
     *
     * Unlike validateResponse(), this NEVER blocks when reCAPTCHA cannot be
     * evaluated. A missing/blocked token, unconfigured keys, a network error,
     * or a token Google rejects all return true. It returns false ONLY when
     * Google successfully scores the request below the configured threshold —
     * i.e. a confirmed bot. This guarantees a reCAPTCHA outage or
     * misconfiguration can never stop legitimate checkouts.
     */
    public function isLikelyHuman($response): bool
    {
        /* Nothing to evaluate — let it through (fail open). */
        if (empty($response) || empty($this->apiKey) || empty($this->siteKey)) {
            logger()->warning('reCAPTCHA (checkout): no token or keys to evaluate, allowing order.');

            return true;
        }

        try {
            $apiResponse = Http::asForm()->post($this->getSiteVerifyEndpoint(), [
                'secret'   => $this->apiKey,
                'response' => $response,
            ]);

            $result = $apiResponse->json();

            /* Could not get a usable response from Google — fail open. */
            if (! $result || $apiResponse->failed()) {
                logger()->warning('reCAPTCHA (checkout): could not reach Google, allowing order.', ['response' => $result]);

                return true;
            }

            /* Google evaluated and returned a score — the only path that can block. */
            if (! empty($result['success']) && isset($result['score'])) {
                $score = (float) $result['score'];

                $isHuman = $score >= $this->scoreThreshold;

                logger()->info('reCAPTCHA (checkout): scored request.', [
                    'score'     => $score,
                    'threshold' => $this->scoreThreshold,
                    'is_human'  => $isHuman,
                ]);

                return $isHuman;
            }

            /* success=false or no score (expired/forged/duplicate token) — ambiguous, fail open. */
            logger()->warning('reCAPTCHA (checkout): unscored response, allowing order.', ['response' => $result]);

            return true;
        } catch (\Exception $e) {
            logger()->warning('reCAPTCHA (checkout): exception during validation, allowing order.', [
                'message' => $e->getMessage(),
            ]);

            return true;
        }
    }

    /**
     * Get or merge existing validations with your captcha validations.
     */
    public function getValidations($rules = []): array
    {
        return $this->isActive()
            ? array_merge($rules, ['recaptcha_token' => 'required|captcha'])
            : $rules;
    }

    /**
     * Get or merge existing validation messages with your captcha validation messages.
     */
    public function getValidationMessages($messages = []): array
    {
        return $this->isActive()
            ? array_merge($messages, [
                'recaptcha_token.required' => trans('customer::app.validations.captcha.required'),
                'recaptcha_token.captcha' => trans('customer::app.validations.captcha.captcha'),
            ])
            : $messages;
    }

    /**
     * Initialize.
     */
    protected function initialize(): void
    {
        $this->projectId = $this->getProjectId();

        $this->apiKey = $this->getApiKey();

        $this->siteKey = $this->getSiteKey();

        $this->scoreThreshold = $this->getScoreThreshold();
    }

    /**
     * Get attributes.
     */
    protected function getAttributes(): array
    {
        return [
            'id' => 'recaptcha-token',
            'name' => 'recaptcha_token',
            'type' => 'hidden',
        ];
    }

    /**
     * Build attributes.
     */
    protected function buildHTMLAttributes(array $attributes): string
    {
        $htmlAttributes = [];

        foreach ($attributes as $key => $value) {
            $htmlAttributes[] = "{$key}=\"{$value}\"";
        }

        return count($htmlAttributes)
            ? implode(' ', $htmlAttributes)
            : '';
    }

    /**
     * Get captcha view.
     *
     * @return string
     */
    protected function getCaptchaView()
    {
        $htmlAttributes = $this->buildHTMLAttributes($this->getAttributes());

        return view('customer::captcha.view', [
            'htmlAttributes' => $htmlAttributes,
        ])->render();
    }

    /**
     * Get captcha script view.
     *
     * @return string
     */
    protected function getCaptchaJSView()
    {
        return view('customer::captcha.scripts', [
            'clientEndPoint' => $this->getClientEndpoint(),
            'siteKey' => $this->siteKey,
        ])->render();
    }
}
