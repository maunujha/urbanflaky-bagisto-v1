<?php

namespace Gabha\Notifications\Http\Controllers\Shop;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Models\SubscribersList;
use Webkul\Customer\Models\Customer;

/**
 * Double opt-in link: subscribes the address, then fires the usual
 * subscription event, which sends the existing welcome email.
 */
class SubscriptionConfirmController extends Controller
{
    public function __invoke(Request $request, int $subscriber): RedirectResponse
    {
        $home = redirect()->route('shop.home.index');

        $subscription = SubscribersList::whereKey($subscriber)
            ->where('token', (string) $request->query('token'))
            ->first();

        if (! $subscription) {
            session()->flash('warning', trans('notifications::app.preferences.invalid'));

            return $home;
        }

        if ($subscription->is_subscribed) {
            session()->flash('success', trans('shop::app.subscription.already'));

            return $home;
        }

        $subscription->update(['is_subscribed' => 1]);

        if ($subscription->customer_id) {
            Customer::whereKey($subscription->customer_id)->update(['subscribed_to_news_letter' => 1]);
        }

        Event::dispatch('customer.subscription.after', $subscription);

        session()->flash('success', trans('shop::app.subscription.subscribe-success'));

        return $home;
    }
}
