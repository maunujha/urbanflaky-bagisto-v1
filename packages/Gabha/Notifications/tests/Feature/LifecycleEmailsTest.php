<?php

use Gabha\Notifications\Mail\AbandonedCart;
use Gabha\Notifications\Mail\ReviewRequest;
use Gabha\Notifications\Mail\WinBack;
use Gabha\Notifications\Models\EmailLog;
use Gabha\Notifications\Models\EmailSuppression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Webkul\Checkout\Models\Cart;
use Webkul\Checkout\Models\CartItem;
use Webkul\Core\Models\SubscribersList;
use Webkul\Customer\Models\Customer;

require_once __DIR__.'/Helpers.php';

beforeEach(function () {
    Mail::fake();
});

function subscribe(string $email): void
{
    SubscribersList::create([
        'email'         => $email,
        'is_subscribed' => 1,
        'token'         => uniqid(),
        'channel_id'    => core()->getDefaultChannel()->id,
    ]);
}

function abandonedCart(string $email, int $idleHours, array $attributes = []): Cart
{
    $cart = Cart::factory()->create(array_merge([
        'customer_email'      => $email,
        'customer_first_name' => 'Isha',
        'is_active'           => 1,
        'items_count'         => 1,
    ], $attributes));

    $product = notificationsProduct();

    CartItem::factory()->create([
        'cart_id'    => $cart->id,
        'product_id' => $product->id,
        'sku'        => $product->sku,
        'name'       => $product->name,
        'type'       => 'simple',
    ]);

    DB::table('cart')->where('id', $cart->id)->update([
        'created_at' => now()->subHours($idleHours + 1),
        'updated_at' => now()->subHours($idleHours),
    ]);

    return $cart->refresh();
}

/* ---- Abandoned bag ---- */

it('reminds a subscriber about an abandoned bag, once per step', function () {
    subscribe('isha@example.com');
    $cart = abandonedCart('isha@example.com', 2);

    $this->artisan('notifications:abandoned-carts')->assertSuccessful();
    $this->artisan('notifications:abandoned-carts')->assertSuccessful();

    Mail::assertQueued(AbandonedCart::class, fn ($mail) => $mail->cart->id === $cart->id && $mail->step === 1 && $mail->unsubscribeUrl);
    Mail::assertQueuedCount(1);
});

it('never emails a bag owner who has not subscribed', function () {
    abandonedCart('stranger@example.com', 2);

    $this->artisan('notifications:abandoned-carts')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('sends nothing on a dry run', function () {
    subscribe('isha@example.com');
    abandonedCart('isha@example.com', 2);

    $this->artisan('notifications:abandoned-carts --dry-run')
        ->expectsOutputToContain('would send step 1 to isha@example.com')
        ->assertSuccessful();

    Mail::assertNothingQueued();
    expect(EmailLog::count())->toBe(0);
});

it('leaves bags alone once the customer has ordered', function () {
    subscribe('isha@example.com');
    $cart = abandonedCart('isha@example.com', 2);
    notificationsOrder(['customer_email' => 'isha@example.com', 'cart_id' => $cart->id]);

    $this->artisan('notifications:abandoned-carts')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('stops at the daily marketing cap', function () {
    config(['notifications.limits.marketing_daily_cap' => 2]);

    foreach (['a', 'b', 'c'] as $name) {
        subscribe("{$name}@example.com");
        abandonedCart("{$name}@example.com", 2);
    }

    $this->artisan('notifications:abandoned-carts')->assertSuccessful();

    Mail::assertQueued(AbandonedCart::class, 2);
});

it('waits the marketing gap before a second reminder', function () {
    subscribe('isha@example.com');
    $cart = abandonedCart('isha@example.com', 50);

    EmailLog::create([
        'type' => 'marketing.abandoned_cart', 'category' => 'marketing', 'mailable' => AbandonedCart::class,
        'recipient' => 'isha@example.com', 'status' => 'sent', 'dedupe_key' => 'cart:'.$cart->id.':step1',
    ]);

    $this->artisan('notifications:abandoned-carts')->assertSuccessful();
    Mail::assertNothingQueued();

    EmailLog::query()->update(['created_at' => now()->subHours(49)]);

    $this->artisan('notifications:abandoned-carts')->assertSuccessful();
    Mail::assertQueued(AbandonedCart::class, fn ($mail) => $mail->step === 2);
});

it('speaks to a failed payment when an online method was chosen', function () {
    $cart = abandonedCart('isha@example.com', 2);
    \Webkul\Checkout\Models\CartPayment::factory()->create(['cart_id' => $cart->id, 'method' => 'razorpay']);

    $mail = new AbandonedCart($cart->refresh(), 1, 'https://urbanflaky.in/email/bag/1');

    expect($mail->envelope()->subject)->toBe(trans('notifications::app.emails.cart.payment.subject'));
    expect($mail->render())->toContain('https://urbanflaky.in/email/bag/1');
});

/* ---- Review requests ---- */

it('asks for a review a few days after delivery, once', function () {
    $order = notificationsOrder(['status' => 'completed']);
    DB::table('shiprocket_orders')->insert([
        'order_id' => $order->id, 'status' => 'delivered', 'delivered_at' => now()->subDays(6),
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->artisan('notifications:review-requests')->assertSuccessful();
    $this->artisan('notifications:review-requests')->assertSuccessful();

    Mail::assertQueued(ReviewRequest::class, 1);
});

it('does not ask customers who opted out of review requests', function () {
    $order = notificationsOrder(['status' => 'completed']);
    DB::table('shiprocket_orders')->insert([
        'order_id' => $order->id, 'status' => 'delivered', 'delivered_at' => now()->subDays(6),
        'created_at' => now(), 'updated_at' => now(),
    ]);
    EmailSuppression::suppress('buyer@example.com', EmailSuppression::SCOPE_REVIEWS);

    $this->artisan('notifications:review-requests')->assertSuccessful();

    Mail::assertNothingQueued();
});

/* ---- Win-back ---- */

it('sends one win-back to a lapsed subscriber', function () {
    $customer = Customer::factory()->create(['email' => 'lapsed@example.com']);
    subscribe('lapsed@example.com');
    notificationsOrder(['customer_id' => $customer->id, 'customer_email' => 'lapsed@example.com', 'created_at' => now()->subDays(120)]);

    $this->artisan('notifications:win-back')->assertSuccessful();
    $this->artisan('notifications:win-back')->assertSuccessful();

    Mail::assertQueued(WinBack::class, 1);
});

it('does not win back a recent customer', function () {
    $customer = Customer::factory()->create(['email' => 'recent@example.com']);
    subscribe('recent@example.com');
    notificationsOrder(['customer_id' => $customer->id, 'customer_email' => 'recent@example.com', 'created_at' => now()->subDays(10)]);

    $this->artisan('notifications:win-back')->assertSuccessful();

    Mail::assertNothingQueued();
});

/* ---- Unsubscribe ---- */

it('shows a confirmation for a signed link without unsubscribing yet', function () {
    $url = URL::signedRoute('shop.notifications.unsubscribe', ['email' => 'isha@example.com', 'scope' => 'marketing']);

    $this->get($url)->assertOk()->assertSee('isha@example.com');

    expect(EmailSuppression::isSuppressed('isha@example.com', 'marketing'))->toBeFalse();
});

it('unsubscribes on POST, including the newsletter, and works as one-click', function () {
    subscribe('isha@example.com');
    $url = URL::signedRoute('shop.notifications.unsubscribe', ['email' => 'isha@example.com', 'scope' => 'marketing']);

    $this->post($url, ['List-Unsubscribe' => 'One-Click'])->assertOk();

    expect(EmailSuppression::isSuppressed('isha@example.com', 'marketing'))->toBeTrue();
    expect((int) SubscribersList::where('email', 'isha@example.com')->value('is_subscribed'))->toBe(0);
});

it('rejects a tampered unsubscribe link', function () {
    $url = URL::signedRoute('shop.notifications.unsubscribe', ['email' => 'isha@example.com', 'scope' => 'marketing']);

    $this->post(str_replace('isha%40example.com', 'someone%40example.com', $url))->assertForbidden();

    expect(EmailSuppression::count())->toBe(0);
});
