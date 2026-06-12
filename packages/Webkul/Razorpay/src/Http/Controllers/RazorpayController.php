<?php

namespace Webkul\Razorpay\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Checkout\Facades\Cart;
use Webkul\Razorpay\Payment\RazorpayPayment;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\OrderTransactionRepository;
use Webkul\Sales\Transformers\OrderResource;

class RazorpayController extends Controller
{
    /**
     * Payment captured constant.
     */
    public const PAYMENT_CAPTURED = 'captured';

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected RazorpayPayment $razorpayPayment,
        protected OrderRepository $orderRepository,
        protected OrderTransactionRepository $orderTransactionRepository,
        protected InvoiceRepository $invoiceRepository,
    ) {}

    /**
     * Redirects to checkout.
     */
    public function redirect(): RedirectResponse|View
    {
        if (! $this->razorpayPayment->hasValidCredentials()) {
            session()->flash('error', trans('razorpay::app.response.something-went-wrong'));

            return redirect()->back();
        }

        try {
            $cart = Cart::getCart();

            $currency = strtoupper($cart->base_currency_code ?? core()->getBaseCurrencyCode());

            if (! $this->razorpayPayment->isCurrencySupported($currency)) {
                session()->flash('error', trans('razorpay::app.response.supported-currency-error', [
                    'currency' => $currency,
                    'supportedCurrencies' => implode(', ', $this->razorpayPayment->getSupportedCurrencies()),
                ]));

                return redirect()->back();
            }

            $razorpayOrder = $this->razorpayPayment->createOrder($cart);

            /*
             * Bind this Razorpay order to the cart and amount so the success
             * callback can reject replayed or amount-mismatched payments.
             */
            session([
                'razorpay_order_id' => $razorpayOrder['id'],
                'razorpay_cart_id'  => $cart->id,
                'razorpay_amount'   => (int) round($cart->base_grand_total * 100),
            ]);

            $payment = $this->razorpayPayment->preparePaymentData($cart, $razorpayOrder);

            return view('razorpay::drop-in-ui', compact('payment'));
        } catch (\Throwable $e) {
            report($e);

            session()->flash('error', trans('razorpay::app.response.something-went-wrong'));

            return redirect()->back();
        }
    }

    /**
     * Payment success.
     */
    public function paymentSuccess(Request $request): RedirectResponse
    {
        if (! $this->razorpayPayment->hasValidCredentials()) {
            session()->flash('error', trans('razorpay::app.response.something-went-wrong'));

            return redirect()->route('shop.checkout.cart.index');
        }

        $razorpayOrderId = $request->input('razorpay_order_id');

        if (! $razorpayOrderId) {
            session()->flash('error', trans('razorpay::app.response.something-went-wrong'));

            return redirect()->route('shop.checkout.cart.index');
        }

        if ($request->has('error')) {
            return $this->handlePaymentError($request);
        }

        $isValidSignature = $this->razorpayPayment->verifySignature(
            $request->input('razorpay_order_id'),
            $request->input('razorpay_payment_id'),
            $request->input('razorpay_signature')
        );

        if (! $isValidSignature) {
            session()->flash('error', trans('razorpay::app.response.something-went-wrong'));

            return redirect()->route('shop.checkout.cart.index');
        }

        $cart = Cart::getCart();

        /*
         * The signature only proves the payment belongs to a Razorpay order on
         * this merchant account — it does not tie it to this cart. Reject the
         * callback unless it matches the order id, cart and amount staged at
         * redirect, otherwise a captured payment for a cheap cart could be
         * replayed against a bigger one.
         */
        if (
            ! $cart
            || $razorpayOrderId !== session('razorpay_order_id')
            || $cart->id !== session('razorpay_cart_id')
            || (int) round($cart->base_grand_total * 100) !== session('razorpay_amount')
        ) {
            session()->flash('error', trans('razorpay::app.response.something-went-wrong'));

            return redirect()->route('shop.checkout.cart.index');
        }

        session()->forget(['razorpay_order_id', 'razorpay_cart_id', 'razorpay_amount']);

        return $this->handlePaymentSuccess($request, $cart);
    }

    /**
     * Payment fail.
     */
    public function paymentFail(): RedirectResponse
    {
        session()->flash('error', trans('razorpay::app.response.payment.cancelled'));

        return redirect()->route('shop.checkout.cart.index');
    }

    /**
     * Handle payment error.
     */
    protected function handlePaymentError(Request $request): RedirectResponse
    {
        $errorDescription = $request->input('error.description', trans('razorpay::app.response.something-went-wrong'));

        session()->flash('error', $errorDescription);

        return redirect()->route('shop.checkout.cart.index');
    }

    /**
     * Handle successful payment.
     */
    protected function handlePaymentSuccess(Request $request, $cart): RedirectResponse
    {
        try {
            $orderData = (new OrderResource($cart))->jsonSerialize();

            $order = $this->orderRepository->create($orderData);

            if ($order->payment) {
                $order->payment->update([
                    'additional' => [
                        'status' => Invoice::STATUS_PAID,
                        'razorpay_order_id' => $request->input('razorpay_order_id'),
                        'razorpay_payment_id' => $request->input('razorpay_payment_id'),
                    ],
                ]);
            }

            $this->orderRepository->update(['status' => Order::STATUS_PROCESSING], $order->id);

            $invoice = $this->invoiceRepository->create($this->prepareInvoiceData($order->id));

            $this->orderTransactionRepository->create([
                'transaction_id' => $request->input('razorpay_payment_id'),
                'status' => self::PAYMENT_CAPTURED,
                'type' => $order->payment->method,
                'payment_method' => $order->payment->method,
                'order_id' => $order->id,
                'invoice_id' => $invoice->id,
                'amount' => $orderData['base_grand_total'] ?? 0,
                'data' => json_encode([
                    'razorpay_order_id' => $request->input('razorpay_order_id'),
                    'razorpay_payment_id' => $request->input('razorpay_payment_id'),
                    'razorpay_signature' => $request->input('razorpay_signature'),
                ]),
            ]);

            Cart::deActivateCart();

            session()->flash('order_id', $order->id);

            return redirect()->route('shop.checkout.onepage.success');
        } catch (\Throwable $e) {
            report($e);

            session()->flash('error', trans('razorpay::app.response.something-went-wrong'));

            return redirect()->route('shop.checkout.cart.index');
        }
    }

    /**
     * Prepares invoice data.
     */
    protected function prepareInvoiceData(?int $orderId = null): array
    {
        try {
            $order = $orderId
                ? $this->orderRepository->findOrFail($orderId)
                : $this->orderRepository->orderBy('created_at', 'desc')->first();

            if (! $order) {
                return [];
            }

            $invoiceItems = [];

            foreach ($order->items as $item) {
                if ($item->qty_to_invoice > 0) {
                    $invoiceItems[$item->id] = $item->qty_to_invoice;
                }
            }

            if (empty($invoiceItems)) {
                return [];
            }

            return [
                'order_id' => $order->id,
                'invoice' => [
                    'items' => $invoiceItems,
                ],
            ];
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }
}
