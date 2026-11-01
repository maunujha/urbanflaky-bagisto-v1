<?php

namespace Gabha\Notifications\Http\Controllers\Shop;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Webkul\Checkout\Facades\Cart as CartFacade;
use Webkul\Checkout\Models\Cart;

/**
 * Signed link from an abandoned-bag email: puts that bag back in the
 * browser's session. A customer's bag needs them signed in instead.
 */
class CartRecoveryController extends Controller
{
    public function __invoke(int $cartId): RedirectResponse
    {
        $cart = Cart::find($cartId);

        $bag = redirect()->route('shop.checkout.cart.index');

        if (! $cart || ! $cart->is_active) {
            return $bag;
        }

        if ($cart->customer_id) {
            if (auth()->guard('customer')->id() === $cart->customer_id) {
                return $bag;
            }

            session(['url.intended' => route('shop.checkout.cart.index')]);

            return redirect()->route('shop.customer.session.index');
        }

        CartFacade::setCart($cart);

        return $bag;
    }
}
