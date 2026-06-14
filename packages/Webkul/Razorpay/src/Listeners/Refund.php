<?php

namespace Webkul\Razorpay\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Razorpay\Payment\RazorpayPayment;
use Webkul\Sales\Models\OrderTransaction;

class Refund
{
    /**
     * Create a new listener instance.
     */
    public function __construct(protected RazorpayPayment $razorpayPayment) {}

    /**
     * Push an admin-created refund through to Razorpay.
     *
     * Bagisto's admin "Refund" only writes an internal credit-memo and fires
     * `sales.refund.save.after`; out of the box nothing refunds the money at the
     * gateway. This listener does. It runs synchronously and BEFORE the refund
     * transaction commits (the event is dispatched inside RefundRepository's DB
     * transaction), so throwing on a gateway failure rolls the credit-memo back —
     * Bagisto never records a refund that did not actually happen at Razorpay.
     */
    public function refundPayment($refund): void
    {
        $order = $refund->order;

        /* Only act on Razorpay-paid orders. */
        if (! $order || optional($order->payment)->method !== 'razorpay') {
            return;
        }

        $amount = (int) round($refund->base_grand_total * 100);

        if ($amount <= 0) {
            return;
        }

        $paymentId = data_get($order->payment->additional, 'razorpay_payment_id')
            ?: OrderTransaction::where('order_id', $order->id)->value('transaction_id');

        if (! $paymentId) {
            Log::error('Razorpay refund: no razorpay_payment_id found; refund manually in the dashboard.', [
                'order_id'  => $order->id,
                'refund_id' => $refund->id,
            ]);

            throw new \Exception(trans('razorpay::app.response.refund.missing-payment'));
        }

        try {
            $payment = $this->razorpayPayment->getApi()->payment->fetch($paymentId);
        } catch (\Throwable $e) {
            Log::error('Razorpay refund: could not fetch payment from gateway.', [
                'order_id'   => $order->id,
                'payment_id' => $paymentId,
                'error'      => $e->getMessage(),
            ]);

            throw new \Exception(trans('razorpay::app.response.refund.failed'));
        }

        $captured        = (int) ($payment['amount'] ?? 0);
        $alreadyRefunded = (int) ($payment['amount_refunded'] ?? 0);

        /*
         * Already refunded at the gateway (e.g. manually in the Razorpay
         * dashboard). Record the credit-memo but never refund the same money
         * twice — this also makes the listener safe to re-run.
         */
        if ($alreadyRefunded + $amount > $captured) {
            Log::warning('Razorpay refund: gateway already refunded; recording credit-memo only.', [
                'order_id'         => $order->id,
                'payment_id'       => $paymentId,
                'captured'         => $captured,
                'already_refunded' => $alreadyRefunded,
                'requested'        => $amount,
            ]);

            return;
        }

        try {
            $rpRefund = $payment->refund([
                'amount' => $amount,
                'notes'  => [
                    'order_id'  => (string) $order->id,
                    'refund_id' => (string) $refund->id,
                ],
            ]);

            Log::info('Razorpay refund: processed at gateway.', [
                'order_id'           => $order->id,
                'refund_id'          => $refund->id,
                'razorpay_refund_id' => $rpRefund['id'] ?? null,
                'amount'             => $amount,
            ]);
        } catch (\Throwable $e) {
            Log::error('Razorpay refund FAILED at gateway; credit-memo rolled back.', [
                'order_id'   => $order->id,
                'refund_id'  => $refund->id,
                'payment_id' => $paymentId,
                'amount'     => $amount,
                'error'      => $e->getMessage(),
            ]);

            throw new \Exception(trans('razorpay::app.response.refund.failed'));
        }
    }
}
