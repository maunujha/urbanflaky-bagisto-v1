<?php

namespace Webkul\Shop\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Checkout\Facades\Cart;
use Webkul\Core\Repositories\CountryRepository;
use Webkul\Core\Repositories\CountryStateRepository;
use Webkul\MagicAI\Facades\MagicAI;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Shop\Http\Resources\AddressResource;

class OnepageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(
        CountryRepository $countryRepository,
        CountryStateRepository $stateRepository
    ) {
        if (! core()->getConfigData('sales.checkout.shopping_cart.cart_page')) {
            abort(404);
        }

        Event::dispatch('checkout.load.index');

        if (
            ! auth()->guard('customer')->check()
            && ! core()->getConfigData('sales.checkout.shopping_cart.allow_guest_checkout')
        ) {
            return redirect()->route('shop.customer.session.index');
        }

        if (auth()->guard('customer')->user()?->is_suspended) {
            session()->flash('warning', trans('shop::app.checkout.cart.suspended-account-message'));

            return redirect()->route('shop.checkout.cart.index');
        }

        if (Cart::hasError()) {
            return redirect()->route('shop.checkout.cart.index');
        }

        $cart = Cart::getCart();

        if (
            ! auth()->guard('customer')->check()
            && (
                $cart->hasDownloadableItems()
                || ! $cart->hasGuestCheckoutItems()
            )
        ) {
            return redirect()->route('shop.customer.session.index');
        }

        $cartItems       = $cart->items;
        $countries       = $countryRepository->all();
        $states          = $stateRepository->findWhere(['country_code' => 'IN']);
        $shippingMethods = [];
        $paymentMethods  = [];

        /* Saved addresses for the address-book selection UI (logged-in customers only). */
        $addresses = auth()->guard('customer')->check()
            ? AddressResource::collection(auth()->guard('customer')->user()->addresses)->resolve()
            : [];

        return view('shop::checkout.onepage.index', compact(
            'cart',
            'cartItems',
            'countries',
            'states',
            'shippingMethods',
            'paymentMethods',
            'addresses'
        ));
    }

    /**
     * Order success page.
     *
     * @return View|RedirectResponse
     */
    public function success(OrderRepository $orderRepository)
    {
        if (! $order = $orderRepository->find(session('order_id'))) {
            return redirect()->route('shop.checkout.cart.index');
        }

        if (
            core()->getConfigData('magic_ai.general.settings.enabled')
            && core()->getConfigData('magic_ai.storefront_features.checkout_message.enabled')
        ) {
            try {
                $order->checkout_message = MagicAI::checkoutMessage($order);
            } catch (\Exception $e) {
            }
        }

        return view('shop::checkout.success', compact('order'));
    }
}
