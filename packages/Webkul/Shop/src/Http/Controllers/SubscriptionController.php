<?php

namespace Webkul\Shop\Http\Controllers;

use App\Services\Security\AtomicLimiter;
use App\Services\Security\ClientIp;
use App\Services\Security\RecaptchaVerifier;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Webkul\Core\Repositories\SubscribersListRepository;

class SubscriptionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected SubscribersListRepository $subscriptionRepository,
        protected AtomicLimiter $limiter,
        protected RecaptchaVerifier $recaptcha,
    ) {}

    /**
     * Subscribes email to the email subscription list.
     *
     * @return RedirectResponse
     */
    public function store()
    {
        /*
         * Honeypot: humans never see this field. Answer exactly like a real
         * signup so the bot learns nothing, but store and send nothing.
         */
        if (filled(request()->input('website'))) {
            return $this->subscribed();
        }

        $this->validate(request(), [
            'email' => ['required', 'string', 'email:rfc,strict', 'max:160'],
        ]);

        $email = Str::lower(trim(request()->input('email')));
        $emailTag = 'em_'.substr(hash_hmac('sha256', $email, (string) config('app.key')), 0, 12);
        $ip = ClientIp::bucket(request());
        $limits = config('otp_security.newsletter');

        try {
            /* Counted before reCAPTCHA so a flood never reaches Google or the DB. */
            $limit = $this->limiter->reserve([
                'ip_minute' => ['key' => "nl:ip:{$ip}:m", 'limit' => $limits['per_ip_minute_max'], 'ttl' => 60],
                'ip_daily'  => ['key' => "nl:ip:{$ip}:d", 'limit' => $limits['per_ip_daily_max'], 'ttl' => 86400],
            ]);

            if (! $limit['allowed']) {
                Log::notice('newsletter.limited', ['bucket' => $limit['bucket'], 'ip' => $ip]);

                return $this->failed('Too many attempts. Please try again a little later.', 429);
            }

            if (! $this->recaptcha->passes(request()->input('recaptcha_token'), 'newsletter')) {
                return $this->failed('We couldn’t verify your request. Please refresh the page and try again.');
            }

            /* Serialises concurrent signups for one address (no DB unique index exists). */
            return Cache::lock('nl:lock:'.$emailTag, 10)->block(5, function () use ($email, $emailTag, $ip, $limits) {
                Event::dispatch('customer.subscription.before');

                /* Tells the visitor plainly (owner's choice). Bulk lookups are held
                   back by reCAPTCHA + the IP limits above. Nothing written, no mail. */
                if ($this->isAlreadySubscribed($email)) {
                    return $this->alreadySubscribed();
                }

                /* Caps welcome mails to one inbox, whoever triggers them. */
                $limit = $this->limiter->reserve([
                    'email_daily' => ['key' => "nl:email:{$emailTag}:d", 'limit' => $limits['per_email_daily_max'], 'ttl' => 86400],
                ]);

                if (! $limit['allowed']) {
                    Log::notice('newsletter.limited', ['bucket' => 'email_daily', 'email' => $emailTag, 'ip' => $ip]);

                    return $this->failed('Too many attempts. Please try again a little later.', 429);
                }

                $subscription = $this->upsertSubscription($email);

                $this->syncCustomerNewsletterFlag();

                Event::dispatch('customer.subscription.after', $subscription);

                Log::info('newsletter.subscribed', ['email' => $emailTag, 'ip' => $ip]);

                return $this->subscribed();
            });
        } catch (LockTimeoutException) {
            return $this->failed('Please wait a moment and try again.', 429);
        } catch (\Throwable $e) {
            Log::error('newsletter.failed', ['email' => $emailTag, 'error' => $e->getMessage()]);

            return $this->failed('Something went wrong. Please try again shortly.', 503);
        }
    }

    /**
     * Not an error for the visitor — a calm confirmation, flagged so the footer
     * doesn't count it as a new lead.
     */
    private function alreadySubscribed(): JsonResponse|RedirectResponse
    {
        $msg = trans('shop::app.subscription.already');

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'already' => true, 'message' => $msg]);
        }

        session()->flash('success', $msg);

        return redirect()->back();
    }

    /**
     * Error response in the shape the footer form (JSON) or a plain POST expects.
     */
    private function failed(string $msg, int $status = 422): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json(['success' => false, 'message' => $msg], $status);
        }

        session()->flash('error', $msg);

        return redirect()->back();
    }

    /**
     * Success response, shared by real signups and the honeypot.
     */
    private function subscribed(): JsonResponse|RedirectResponse
    {
        $msg = trans('shop::app.subscription.subscribe-success');

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        session()->flash('success', $msg);

        return redirect()->back();
    }

    /**
     * To unsubscribe from a the subscription list.
     *
     * @param  string  $token
     * @return Response
     */
    public function destroy($token)
    {
        $this->subscriptionRepository->deleteWhere(['token' => $token]);

        session()->flash('success', trans('shop::app.subscription.unsubscribe-success'));

        return redirect()->route('shop.home.index');
    }

    /**
     * Check if the given email is already subscribed to the newsletter. This method checks the subscription repository
     * for an existing record with the given email and subscription status.
     */
    private function isAlreadySubscribed(string $email): bool
    {
        return (bool) $this->subscriptionRepository->findOneWhere([
            'email' => $email,
            'is_subscribed' => 1,
        ]);
    }

    /**
     * Upsert the subscription record for the given email. If a record already exists for the email, it will be updated
     * with the new subscription details. Otherwise, a new record will be created.
     */
    private function upsertSubscription(string $email): mixed
    {
        $customer = auth()->user();

        $payload = [
            'is_subscribed' => 1,
            /* Unsubscribe links are unauthenticated GETs — the token must be unguessable. */
            'token' => Str::random(40),
            'customer_id' => $customer?->id,
        ];

        $existing = $this->subscriptionRepository->findOneByField('email', $email);

        if ($existing) {
            $existing->update($payload);

            return $existing;
        }

        return $this->subscriptionRepository->create(array_merge($payload, [
            'email' => $email,
            'channel_id' => core()->getCurrentChannel()->id,
        ]));
    }

    /**
     * Sync the newsletter subscription flag for the customer if they are logged in and subscribing to the newsletter.
     */
    private function syncCustomerNewsletterFlag(): void
    {
        $customer = auth()->user();

        if (! $customer) {
            return;
        }

        $customer->update(['subscribed_to_news_letter' => 1]);
    }
}
