<?php

namespace Webkul\Razorpay\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\Cart as CartModel;
use Webkul\Razorpay\Payment\RazorpayPayment;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderTransaction;
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

        if (! $cart) {
            session()->flash('error', trans('razorpay::app.response.something-went-wrong'));

            return redirect()->route('shop.checkout.cart.index');
        }

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

    /**
     * Razorpay server-to-server webhook.
     *
     * Recovers an order when a payment was captured but the customer's browser
     * never completed the redirect back to paymentSuccess(). Verifies the
     * Razorpay webhook signature, then creates the order from the cart id we
     * stored in the Razorpay order notes — idempotently, so duplicate webhook
     * deliveries never create duplicate orders.
     */
    public function webhook(Request $request): JsonResponse
    {
        $secret = $this->razorpayPayment->getConfigData('webhook_secret');

        if (! $secret) {
            Log::warning('Razorpay webhook: no webhook_secret configured; ignoring.');

            /* Return 200 so Razorpay does not disable the webhook while we fix config. */
            return response()->json(['message' => 'ok']);
        }

        $body      = $request->getContent();
        $signature = (string) $request->header('x-razorpay-signature', '');

        try {
            $this->razorpayPayment->getApi()->utility->verifyWebhookSignature($body, $signature, $secret);
        } catch (\Throwable $e) {
            Log::warning('Razorpay webhook: invalid signature.');

            return response()->json(['message' => 'invalid signature'], 400);
        }

        $event = $request->input('event');

        /* Only act once money is actually captured. */
        if (! in_array($event, ['payment.captured', 'order.paid'], true)) {
            return response()->json(['message' => 'ignored']);
        }

        $paymentEntity = $request->input('payload.payment.entity', []);
        $rpOrderId     = $paymentEntity['order_id'] ?? $request->input('payload.order.entity.id');
        $rpPaymentId   = $paymentEntity['id'] ?? null;
        $cartId        = $paymentEntity['notes']['cart_id'] ?? null;

        if (! $rpPaymentId || ! $cartId) {
            return response()->json(['message' => 'ok']);
        }

        /* Idempotency: this payment was already recorded. */
        if (OrderTransaction::where('transaction_id', $rpPaymentId)->exists()) {
            return response()->json(['message' => 'already processed']);
        }

        $cart = CartModel::find($cartId);

        /* Cart gone, or an order was already created for it (e.g. the redirect won the race). */
        if (! $cart || Order::where('cart_id', $cartId)->exists()) {
            return response()->json(['message' => 'ok']);
        }

        try {
            $this->createPaidOrder($cart, (string) $rpOrderId, (string) $rpPaymentId);
        } catch (\Throwable $e) {
            report($e);

            /* 500 so Razorpay retries later rather than dropping the recovery. */
            return response()->json(['message' => 'error'], 500);
        }

        return response()->json(['message' => 'order created']);
    }

    /**
     * Create a paid order, invoice and transaction from a specific cart.
     *
     * Shared by the webhook recovery path. Mirrors handlePaymentSuccess() but
     * takes an explicitly-loaded cart and performs no HTTP redirect.
     */
    protected function createPaidOrder(CartModel $cart, string $rpOrderId, string $rpPaymentId): Order
    {
        $orderData = (new OrderResource($cart))->jsonSerialize();

        $order = $this->orderRepository->create($orderData);

        if ($order->payment) {
            $order->payment->update([
                'additional' => [
                    'status'              => Invoice::STATUS_PAID,
                    'razorpay_order_id'   => $rpOrderId,
                    'razorpay_payment_id' => $rpPaymentId,
                ],
            ]);
        }

        $this->orderRepository->update(['status' => Order::STATUS_PROCESSING], $order->id);

        $invoice = $this->invoiceRepository->create($this->prepareInvoiceData($order->id));

        $this->orderTransactionRepository->create([
            'transaction_id' => $rpPaymentId,
            'status'         => self::PAYMENT_CAPTURED,
            'type'           => $order->payment->method,
            'payment_method' => $order->payment->method,
            'order_id'       => $order->id,
            'invoice_id'     => $invoice->id,
            'amount'         => $orderData['base_grand_total'] ?? 0,
            'data'           => json_encode([
                'razorpay_order_id'   => $rpOrderId,
                'razorpay_payment_id' => $rpPaymentId,
                'source'              => 'webhook',
            ]),
        ]);

        $cart->update(['is_active' => false]);

        Log::info('Razorpay webhook: recovered order', [
            'order_id'   => $order->id,
            'cart_id'    => $cart->id,
            'payment_id' => $rpPaymentId,
        ]);

        return $order;
    }
}
