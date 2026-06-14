<?php

namespace Webkul\Shop\Http\Controllers\Customer;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Webkul\Customer\Repositories\CustomerGroupRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Shop\Http\Controllers\Controller;

class GoogleController extends Controller
{
    public function __construct(
        protected CustomerRepository $customerRepository,
        protected CustomerGroupRepository $customerGroupRepository
    ) {}

    /**
     * Google OAuth provider built straight from config/services.php.
     *
     * We deliberately do NOT use Socialite::driver('google'): the bundled
     * Webkul\SocialLogin package overrides the Socialite Factory and its
     * createGoogleDriver() always reads admin (core_config) creds — never
     * services.google — so the shared driver ignores GOOGLE_CLIENT_ID/.env.
     * buildProvider() is the same call that manager uses internally; it just
     * lets us feed our own config and keep .env as the single source of truth.
     */
    protected function googleProvider(): GoogleProvider
    {
        return Socialite::buildProvider(GoogleProvider::class, config('services.google'));
    }

    /**
     * Redirect to Google OAuth page.
     */
    public function redirect(): RedirectResponse
    {
        return $this->googleProvider()->redirect();
    }

    /**
     * Handle Google callback — log in or create account.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = $this->googleProvider()->user();
        } catch (\Exception $e) {
            session()->flash('error', 'Google login failed. Please try again.');

            return redirect()->route('shop.customer.session.index');
        }

        $channelId = core()->getCurrentChannel()->id;

        // Try to find existing customer by google_id first, then by email
        $customer = $this->customerRepository->findOneWhere([
            'google_id'  => $googleUser->getId(),
            'channel_id' => $channelId,
        ]);

        if (! $customer && $googleUser->getEmail()) {
            $customer = $this->customerRepository->findOneWhere([
                'email'      => $googleUser->getEmail(),
                'channel_id' => $channelId,
            ]);

            // Link google_id to existing account
            if ($customer) {
                $customer->update(['google_id' => $googleUser->getId()]);
            }
        }

        // Create new account if no customer found
        if (! $customer) {
            $customerGroup = core()->getConfigData('customer.settings.create_new_account_options.default_group');

            $nameParts = explode(' ', $googleUser->getName(), 2);
            $firstName = $nameParts[0] ?? 'Google';
            $lastName  = $nameParts[1] ?? 'User';

            Event::dispatch('customer.registration.before');

            $customer = $this->customerRepository->create([
                'first_name'               => $firstName,
                'last_name'                => $lastName,
                'email'                    => $googleUser->getEmail(),
                'google_id'                => $googleUser->getId(),
                'password'                 => bcrypt(Str::random(32)),
                'api_token'                => Str::random(80),
                'is_verified'              => 1,
                'status'                   => 1,
                'customer_group_id'        => $this->customerGroupRepository->findOneWhere(['code' => $customerGroup])->id,
                'channel_id'               => $channelId,
                'subscribed_to_news_letter' => false,
            ]);

            Event::dispatch('customer.create.after', $customer);
            Event::dispatch('customer.registration.after', $customer);
        }

        if (! $customer->status) {
            session()->flash('warning', trans('shop::app.customers.login-form.not-activated'));

            return redirect()->route('shop.customer.session.index');
        }

        auth()->guard('customer')->login($customer);

        Event::dispatch('customer.after.login', $customer);

        $intended = session()->pull('url.intended', null);

        if ($intended) {
            return redirect($intended);
        }

        if (core()->getConfigData('customer.settings.login_options.redirected_to_page') == 'account') {
            return redirect()->route('shop.customers.account.profile.index');
        }

        return redirect()->route('shop.home.index');
    }
}
