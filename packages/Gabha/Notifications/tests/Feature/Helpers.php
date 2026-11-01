<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\Sales\Models\Order;

/*
 * Shared fixtures. Nothing in this suite can reach a real inbox or phone:
 * the mailer is faked or the in-memory "array" transport (phpunit.xml forces
 * MAIL_MAILER=array), and SMS goes to a mock, because order events also run
 * the SMS listeners, which would otherwise call SMS Alert for real (the SMS
 * mock is set up for this whole suite in tests/Pest.php).
 */

function notificationsConfig(array $values): void
{
    foreach ($values as $code => $value) {
        CoreConfig::where('code', $code)->delete();

        CoreConfig::factory()->create(['code' => $code, 'value' => $value]);
    }

    /* CoreConfigRepository lookups are cached; admin saves clear it, direct model writes do not. */
    cache()->flush();
}

function bagistoMailSwitch(string $name, bool $on = true): void
{
    notificationsConfig(['emails.general.notifications.emails.general.notifications.'.$name => $on ? '1' : '0']);
}

function notificationsProduct(): \Webkul\Product\Models\Product
{
    return (new \Webkul\Faker\Helpers\Product)->getSimpleProductFactory()->create();
}

/**
 * What the Razorpay controller records only after it verified the payment
 * with Razorpay (signature + captured amount, or a signed webhook).
 */
function capturedPayment(Order $order, string $reference = 'pay_TEST123'): Order
{
    \Webkul\Sales\Models\OrderTransaction::factory()->create([
        'order_id'       => $order->id,
        'transaction_id' => $reference,
        'status'         => 'captured',
        'type'           => 'razorpay',
        'payment_method' => 'razorpay',
        'amount'         => $order->grand_total,
    ]);

    return $order->refresh();
}

function notificationsOrder(array $attributes = [], string $paymentMethod = 'razorpay'): Order
{
    $order = Order::factory()->create(array_merge([
        'customer_email'      => 'buyer@example.com',
        'customer_first_name' => 'Aarav',
        'customer_last_name'  => 'Sharma',
        'status'              => 'processing',
    ], $attributes));

    $product = notificationsProduct();

    \Webkul\Sales\Models\OrderItem::factory()->create([
        'order_id'   => $order->id,
        'product_id' => $product->id,
        'sku'        => $product->sku,
        'name'       => $product->name,
        'type'       => 'simple',
    ]);
    \Webkul\Sales\Models\OrderPayment::factory()->create([
        'order_id'     => $order->id,
        'method'       => $paymentMethod,
        'method_title' => $paymentMethod === 'cashondelivery' ? 'Cash On Delivery' : 'Razorpay',
    ]);

    return $order->refresh();
}
