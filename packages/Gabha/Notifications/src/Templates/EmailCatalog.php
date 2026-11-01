<?php

namespace Gabha\Notifications\Templates;

use Gabha\Notifications\Mail\AbandonedCart;
use Gabha\Notifications\Mail\BackInStock;
use Gabha\Notifications\Mail\FeedbackRequest;
use Gabha\Notifications\Mail\OrderConfirmation;
use Gabha\Notifications\Mail\ReviewRequest;
use Gabha\Notifications\Mail\ShipmentUpdate;
use Gabha\Notifications\Mail\SubscriptionConfirmation;
use Gabha\Notifications\Mail\WinBack;
use Gabha\Notifications\Models\StockAlert;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Arr;
use Webkul\Checkout\Models\Cart;
use Webkul\Core\Models\SubscribersList;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Models\CustomerNote;
use Webkul\Product\Models\Product;
use Webkul\RMA\Models\RMA;
use Webkul\RMA\Models\RMAMessage;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderComment;
use Webkul\Sales\Models\Refund;
use Webkul\Sales\Models\Shipment;
use Webkul\Shop\Mail;
use Webkul\Shop\Mail\Customer\ResetPasswordNotification;

/**
 * Every customer email the store sends, in one list: what it is, which class
 * sends it, which placeholders its subject may use, and how to build a sample
 * from the latest real record for previews and test sends. Never fixtures:
 * no matching record means no preview.
 */
class EmailCatalog
{
    public const PLACEHOLDERS = [
        '{order_id}'      => 'Order number',
        '{customer_name}' => 'Customer first name',
        '{status}'        => 'Return status',
        '{product}'       => 'Product name',
        '{store}'         => 'Store name',
    ];

    /**
     * @return array<string, array{label: string, group: string, class: string, stage?: string, sample: callable}>
     */
    public function all(): array
    {
        $order = fn () => Order::latest('id')->first();

        $entries = [
            'order_confirmation' => ['Orders', 'Order confirmed', OrderConfirmation::class, fn () => ($o = $order()) ? new OrderConfirmation($o) : null],
            'order_invoice'      => ['Orders', 'Invoice', Mail\Order\InvoicedNotification::class, fn () => ($i = Invoice::latest('id')->first()) ? new Mail\Order\InvoicedNotification($i) : null],
            'order_shipped'      => ['Orders', 'Shipped (with tracking)', Mail\Order\ShippedNotification::class, fn () => ($s = Shipment::latest('id')->first()) ? new Mail\Order\ShippedNotification($s) : null],
            'order_refunded'     => ['Orders', 'Refund issued', Mail\Order\RefundedNotification::class, fn () => ($r = Refund::latest('id')->first()) ? new Mail\Order\RefundedNotification($r) : null],
            'order_canceled'     => ['Orders', 'Order cancelled', Mail\Order\CanceledNotification::class, fn () => ($o = Order::where('status', 'canceled')->latest('id')->first() ?? $order()) ? new Mail\Order\CanceledNotification($o) : null],
            'order_comment'      => ['Orders', 'Order update from the team', Mail\Order\CommentedNotification::class, fn () => ($c = OrderComment::latest('id')->first()) ? new Mail\Order\CommentedNotification($c) : null],
            'invoice_reminder'   => ['Orders', 'Payment reminder', Mail\Customer\InvoiceOverdueReminder::class, fn () => ($i = Invoice::latest('id')->first()) ? new Mail\Customer\InvoiceOverdueReminder($i) : null],
        ];

        foreach (['processing' => 'Being packed', 'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered', 'delivery_failed' => 'Delivery attempt failed', 'rto' => 'Returning to us'] as $stage => $label) {
            $entries['shipment_'.$stage] = ['Delivery', $label, ShipmentUpdate::class, fn () => ($o = $order()) ? new ShipmentUpdate($o, $stage) : null, $stage];
        }

        $entries += [
            'rma_request' => ['Returns', 'Return request received', Mail\Customer\RMA\CustomerRMARequestNotification::class, fn () => ($r = RMA::latest('id')->first()) ? new Mail\Customer\RMA\CustomerRMARequestNotification($r) : null],
            'rma_status'  => ['Returns', 'Return status changed', Mail\Customer\RMA\CustomerRMAStatusNotification::class, fn () => ($r = RMA::latest('id')->first()) ? new Mail\Customer\RMA\CustomerRMAStatusNotification($r) : null],
            'rma_reply'   => ['Returns', 'Reply on a return', Mail\Customer\RMA\AdminToCustomerConversationNotification::class, fn () => ($m = RMAMessage::latest('id')->first()) ? new Mail\Customer\RMA\AdminToCustomerConversationNotification($m) : null],

            'welcome'            => ['Account', 'Welcome', Mail\Customer\RegistrationNotification::class, fn () => ($c = Customer::latest('id')->first()) ? new Mail\Customer\RegistrationNotification($c) : null],
            'verify_email'       => ['Account', 'Confirm email', Mail\Customer\EmailVerificationNotification::class, fn () => ($c = Customer::whereNotNull('token')->latest('id')->first()) ? new Mail\Customer\EmailVerificationNotification($c) : null],
            'password_reset'     => ['Account', 'Reset password', ResetPasswordNotification::class, null],
            'password_changed'   => ['Account', 'Password changed', Mail\Customer\UpdatePasswordNotification::class, fn () => ($c = Customer::latest('id')->first()) ? new Mail\Customer\UpdatePasswordNotification($c) : null],
            'customer_note'      => ['Account', 'Note from the team', Mail\Customer\NoteNotification::class, fn () => ($n = CustomerNote::latest('id')->first()) ? new Mail\Customer\NoteNotification($n) : null],
            'contact_ack'        => ['Account', 'Contact form received', Mail\ContactUsAcknowledgement::class, null],

            'newsletter_welcome' => ['Newsletter', 'Newsletter welcome', Mail\Customer\SubscriptionNotification::class, fn () => ($s = SubscribersList::latest('id')->first()) ? new Mail\Customer\SubscriptionNotification($s) : null],
            'newsletter_confirm' => ['Newsletter', 'Confirm subscription (double opt-in)', SubscriptionConfirmation::class, fn () => ($s = SubscribersList::latest('id')->first()) ? new SubscriptionConfirmation($s, route('shop.home.index')) : null],

            'review_request'   => ['Follow-ups', 'Review request', ReviewRequest::class, fn () => ($o = $order()) ? new ReviewRequest($o, route('shop.home.index')) : null],
            'feedback_request' => ['Follow-ups', 'Rating request', FeedbackRequest::class, fn () => ($o = $order()) ? new FeedbackRequest($o, route('shop.home.index')) : null],
            'abandoned_cart'   => ['Follow-ups', 'Bag reminder', AbandonedCart::class, fn () => ($c = Cart::where('items_count', '>', 0)->whereNotNull('customer_email')->latest('id')->first()) ? new AbandonedCart($c, 1, route('shop.checkout.cart.index'), route('shop.home.index')) : null],
            'win_back'         => ['Follow-ups', 'Win-back', WinBack::class, fn () => ($c = Customer::latest('id')->first()) ? new WinBack($c, route('shop.home.index')) : null],
            'back_in_stock'    => ['Follow-ups', 'Back in stock', BackInStock::class, fn () => ($p = Product::where('type', 'simple')->latest('id')->first()) ? new BackInStock((new StockAlert(['email' => 'preview@urbanflaky.in']))->setRelation('product', $p)) : null],
        ];

        return collect($entries)->map(fn ($e) => [
            'group'  => $e[0],
            'label'  => $e[1],
            'class'  => $e[2],
            'sample' => $e[3],
            'stage'  => $e[4] ?? null,
        ])->all();
    }

    public function get(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * The catalog key of an outgoing email, from its send-event data.
     */
    public function keyFor(array $data): ?string
    {
        $class = $data['__laravel_mailable'] ?? $data['__laravel_notification'] ?? null;

        if (! $class) {
            return null;
        }

        foreach ($this->all() as $key => $entry) {
            if ($entry['class'] === $class && (! $entry['stage'] || ($data['stage'] ?? null) === $entry['stage'])) {
                return $key;
            }
        }

        return null;
    }

    /**
     * A sample built from the latest real record, or null when there is none.
     */
    public function sample(string $key): ?Mailable
    {
        $factory = $this->get($key)['sample'] ?? null;

        return $factory ? $factory() : null;
    }

    /**
     * Placeholder values from an email's view data (whatever it carries).
     *
     * @return array<string, string>
     */
    public static function values(array $data): array
    {
        $order = $data['order']
            ?? Arr::get($data, 'invoice.order')
            ?? Arr::get($data, 'shipment.order')
            ?? Arr::get($data, 'refund.order')
            ?? Arr::get($data, 'comment.order')
            ?? Arr::get($data, 'rma.order')
            ?? Arr::get($data, 'rmaMessage.rma.order');

        $rma = $data['rma'] ?? Arr::get($data, 'rmaMessage.rma');

        $customerName = $order?->customer_first_name
            ?? Arr::get($data, 'customer.first_name')
            ?? Arr::get($data, 'cart.customer_first_name')
            ?? (is_string($data['userName'] ?? null) ? $data['userName'] : null);

        return [
            '{order_id}'      => (string) ($order?->increment_id ?? ''),
            '{customer_name}' => (string) ($customerName ?? ''),
            '{status}'        => (string) ($rma?->status?->title ?? ''),
            '{product}'       => (string) ($data['name'] ?? ''),
            '{store}'         => (string) config('app.name'),
        ];
    }

    public static function compile(string $subject, array $data): string
    {
        return trim(preg_replace('/\s{2,}/', ' ', strtr($subject, self::values($data))));
    }
}
