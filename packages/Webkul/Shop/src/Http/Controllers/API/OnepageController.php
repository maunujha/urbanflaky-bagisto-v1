<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Webkul\CartRule\Exceptions\CouponUsageLimitExceededException;
use Webkul\Checkout\Facades\Cart;
use Webkul\Customer\Facades\Captcha;
use Webkul\Customer\Models\CustomerAddress;
use Webkul\Customer\Repositories\CustomerAddressRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Payment\Facades\Payment;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;
use Webkul\Shipping\Facades\Shipping;
use Webkul\Shop\Http\Requests\CartAddressRequest;
use Webkul\Shop\Http\Resources\CartResource;

class OnepageController extends APIController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected CustomerRepository $customerRepository,
        protected CustomerAddressRepository $customerAddressRepository
    ) {}

    /**
     * Return cart summary.
     */
    public function summary(): JsonResource
    {
        $cart = Cart::getCart();

        return new CartResource($cart);
    }

    /**
     * Store address.
     */
    public function storeAddress(CartAddressRequest $cartAddressRequest): JsonResource
    {
        $params = $cartAddressRequest->all();

        if (
            ! auth()->guard('customer')->check()
            && ! Cart::getCart()->hasGuestCheckoutItems()
        ) {
            return new JsonResource([
                'redirect' => true,
                'data' => route('shop.customer.session.index'),
            ]);
        }

        if (Cart::hasError()) {
            return new JsonResource([
                'redirect' => true,
                'redirect_url' => route('shop.checkout.cart.index'),
            ]);
        }

        Cart::saveAddresses($params);

        $cart = Cart::getCart();

        Cart::collectTotals();

        if ($cart->haveStockableItems()) {
            if (! $rates = Shipping::collectRates()) {
                return new JsonResource([
                    'redirect' => true,
                    'redirect_url' => route('shop.checkout.cart.index'),
                ]);
            }

            return new JsonResource([
                'redirect' => false,
                'data' => $rates,
            ]);
        }

        return new JsonResource([
            'redirect' => false,
            'data' => Payment::getSupportedPaymentMethods(),
        ]);
    }

    /**
     * Store shipping method.
     *
     * @return Response
     */
    public function storeShippingMethod()
    {
        $validatedData = $this->validate(request(), [
            'shipping_method' => 'required',
        ]);

        if (
            Cart::hasError()
            || ! $validatedData['shipping_method']
            || ! Cart::saveShippingMethod($validatedData['shipping_method'])
        ) {
            return response()->json([
                'redirect_url' => route('shop.checkout.cart.index'),
            ], Response::HTTP_FORBIDDEN);
        }

        Cart::collectTotals();

        return response()->json(Payment::getSupportedPaymentMethods());
    }

    /**
     * Store payment method.
     *
     * @return array
     */
    public function storePaymentMethod()
    {
        $validatedData = $this->validate(request(), [
            'payment' => 'required',
        ]);

        if (
            Cart::hasError()
            || ! $validatedData['payment']
            || ! Cart::savePaymentMethod($validatedData['payment'])
        ) {
            return response()->json([
                'redirect_url' => route('shop.checkout.cart.index'),
            ], Response::HTTP_FORBIDDEN);
        }

        Cart::collectTotals();

        $cart = Cart::getCart();

        return [
            'cart' => new CartResource($cart),
        ];
    }

    /**
     * Store order
     */
    public function storeOrder()
    {
        /*
         * Bot protection (fail-open): when reCAPTCHA is enabled, block the order
         * only if Google actively confirms a low (bot) score. Any inability to
         * evaluate the token lets the order through so a reCAPTCHA outage can
         * never stop legitimate checkouts — SMS OTP remains the primary gate.
         */
        if (
            Captcha::isActive()
            && ! Captcha::isLikelyHuman(request('recaptcha_token'))
        ) {
            return response()->json([
                'message' => trans('shop::app.checkout.cart.bot-detected'),
            ], 422);
        }

        /*
         * SMS OTP is the primary checkout gate — enforce it server-side so a
         * direct API call cannot place an order with an unverified phone.
         */
        $isPhoneVerified = session('checkout_phone_verified')
            || (
                auth()->guard('customer')->check()
                && auth()->guard('customer')->user()->phone_verified_at
            );

        if (! $isPhoneVerified) {
            return response()->json([
                'message' => trans('shop::app.checkout.cart.phone-not-verified'),
            ], 403);
        }

        if (Cart::hasError()) {
            return new JsonResource([
                'redirect' => true,
                'redirect_url' => route('shop.checkout.cart.index'),
            ]);
        }

        Cart::collectTotals();

        try {
            $this->validateOrder();
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }

        $cart = Cart::getCart();

        if ($redirectUrl = Payment::getRedirectUrl($cart)) {
            return new JsonResource([
                'redirect' => true,
                'redirect_url' => $redirectUrl,
            ]);
        }

        $data = (new OrderResource($cart))->jsonSerialize();

        try {
            $order = $this->orderRepository->create($data);
        } catch (CouponUsageLimitExceededException $e) {
            cart()->removeCouponCode();

            Cart::collectTotals();

            return new JsonResource([
                'redirect' => false,
                'message' => trans('shop::app.checkout.coupon.usage-limit-exceeded'),
            ]);
        }

        /* Save the order's address to the customer's address book (never break checkout). */
        try {
            $this->saveOrderAddressToBook($order);
        } catch (\Throwable $e) {
            report($e);
        }

        Cart::deActivateCart();

        session()->flash('order_id', $order->id);

        return new JsonResource([
            'redirect' => true,
            'redirect_url' => route('shop.checkout.onepage.success'),
        ]);
    }

    /**
     * Persist the order's billing address into the logged-in customer's address book,
     * so addresses used at checkout show up under My Account → Addresses.
     * Guests are skipped, and a duplicate of an already-saved address is not re-added.
     */
    protected function saveOrderAddressToBook($order): void
    {
        $customer = auth()->guard('customer')->user();

        if (! $customer || ! ($address = $order->billing_address)) {
            return;
        }

        $alreadySaved = $this->customerAddressRepository->findWhere([
            'customer_id'  => $customer->id,
            'address_type' => CustomerAddress::ADDRESS_TYPE,
            'address'      => $address->address,
            'city'         => $address->city,
            'postcode'     => $address->postcode,
        ])->isNotEmpty();

        if ($alreadySaved) {
            return;
        }

        $isFirstAddress = $this->customerAddressRepository->findWhere([
            'customer_id'  => $customer->id,
            'address_type' => CustomerAddress::ADDRESS_TYPE,
        ])->isEmpty();

        $this->customerAddressRepository->create([
            'customer_id'     => $customer->id,
            'address_type'    => CustomerAddress::ADDRESS_TYPE,
            'first_name'      => $address->first_name,
            'last_name'       => $address->last_name,
            'company_name'    => $address->company_name,
            'address'         => $address->address,
            'city'            => $address->city,
            'state'           => $address->state,
            'country'         => $address->country,
            'postcode'        => $address->postcode,
            'phone'           => $address->phone,
            'email'           => $address->email,
            'default_address' => $isFirstAddress,
        ]);
    }

    /**
     * Validate order before creation.
     *
     * @return void|\Exception
     */
    public function validateOrder()
    {
        $cart = Cart::getCart();

        $minimumOrderAmount = core()->getConfigData('sales.order_settings.minimum_order.minimum_order_amount') ?: 0;

        if (
            auth()->guard('customer')->check()
            && auth()->guard('customer')->user()->is_suspended
        ) {
            throw new \Exception(trans('shop::app.checkout.cart.suspended-account-message'));
        }

        if (
            auth()->guard('customer')->user()
            && ! auth()->guard('customer')->user()->status
        ) {
            throw new \Exception(trans('shop::app.checkout.cart.inactive-account-message'));
        }

        if (! Cart::haveMinimumOrderAmount()) {
            throw new \Exception(trans('shop::app.checkout.cart.minimum-order-message', ['amount' => core()->currency($minimumOrderAmount)]));
        }

        if ($cart->haveStockableItems() && ! $cart->shipping_address) {
            throw new \Exception(trans('shop::app.checkout.onepage.address.check-shipping-address'));
        }

        if (! $cart->billing_address) {
            throw new \Exception(trans('shop::app.checkout.onepage.address.check-billing-address'));
        }

        if (
            $cart->haveStockableItems()
            && ! $cart->selected_shipping_rate
        ) {
            throw new \Exception(trans('shop::app.checkout.cart.specify-shipping-method'));
        }

        if (! $cart->payment) {
            throw new \Exception(trans('shop::app.checkout.cart.specify-payment-method'));
        }
    }
}
