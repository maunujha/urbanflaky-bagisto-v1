<?php

use Gabha\RewardCoins\Checkout\CoinDiscount;
use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Http\Controllers\Shop\CoinController;
use Gabha\RewardCoins\Models\CoinTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Webkul\Checkout\Models\Cart;
use Webkul\Checkout\Models\CartItem;
use Webkul\Customer\Models\Customer;
use Webkul\User\Models\Admin;

/**
 * The whole programme, end to end, through the real routes, events, views and
 * scheduler commands: admin configures → order earns → carrier delivers →
 * window elapses → coins confirm → customer sees them → stages them at
 * checkout → spends them on an order → refund restores them → admin adjusts.
 */
function journeyCart(Customer $customer, int $qty = 10): Cart
{
    $product = (new \Webkul\Faker\Helpers\Product)->getSimpleProductFactory()->create();

    $cart = Cart::factory()->create([
        'customer_id'         => $customer->id,
        'customer_first_name' => $customer->first_name,
        'customer_last_name'  => $customer->last_name,
        'customer_email'      => $customer->email,
        'is_guest'            => 0,
        'is_active'           => 1,
    ]);

    CartItem::factory()->create([
        'cart_id'             => $cart->id,
        'product_id'          => $product->id,
        'sku'                 => $product->sku,
        'quantity'            => $qty,
        'name'                => $product->name,
        'price'               => $product->price,
        'price_incl_tax'      => $product->price,
        'base_price'          => $product->price,
        'base_price_incl_tax' => $product->price,
        'total'               => $product->price * $qty,
        'total_incl_tax'      => $product->price * $qty,
        'base_total'          => $product->price * $qty,
        'weight'              => 0,
        'total_weight'        => 0,
        'base_total_weight'   => 0,
        'type'                => $product->type,
        'additional'          => ['product_id' => $product->id, 'quantity' => $qty],
    ]);

    cart()->setCart($cart);

    return $cart;
}

/**
 * Placed orders carry a payment row (Bagisto's invoice listener reads it).
 */
function journeyPayment(\Webkul\Sales\Models\Order $order): \Webkul\Sales\Models\Order
{
    DB::table('order_payment')->insert([
        'order_id'     => $order->id,
        'method'       => 'cashondelivery',
        'method_title' => 'Cash On Delivery',
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    return $order->refresh();
}

it('runs the complete rewards journey without breaking any page or rule', function () {
    // Pages render with real Blade; compiled theme assets are not part of the test.
    $this->withoutVite();

    Carbon::setTestNow('2026-10-10 10:00:00');
    config([
        'shiprocket.webhook_token'           => 'journey-token',
        'reward_coins.min_order_for_redemption' => 0,
    ]);

    $admin = Admin::factory()->create();

    /* ── 1. Admin configures the programme ───────────────────────────── */
    $this->actingAs($admin, 'admin')->get(route('admin.reward_coins.settings'))->assertOk();

    $this->actingAs($admin, 'admin')->post(route('admin.reward_coins.settings.update'), [
        'earning_rate'              => 10,
        'coins_per_unit'            => 1,
        'min_order_amount'          => 0,
        'max_redemption_per_order'  => 200,
        'max_redemption_percent'    => 20,
        'expiry_days'               => 365,
        'pending_confirmation_days' => 7,
        'is_active'                 => 1,
    ])->assertRedirect(route('admin.reward_coins.settings'))->assertSessionHasNoErrors();

    $this->actingAs($admin, 'admin')->get(route('admin.reward_coins.index'))->assertOk();

    /* ── 2. A placed order earns pending coins ──────────────────────── */
    $customer   = Customer::factory()->create();
    $customerId = (int) $customer->id;
    $first      = journeyPayment($this->paidOrder($customerId, [['price' => 1000]]));

    Event::dispatch('checkout.order.save.after', $first);

    $lot = CoinTransaction::query()->where('order_id', $first->id)->where('type', TransactionType::Earned->value)->firstOrFail();

    expect($lot->amount)->toBe(100);
    expect($lot->status)->toBe(TransactionStatus::Pending);
    expect($this->walletRow($customerId)->pending_balance)->toBe(100);
    expect($this->walletRow($customerId)->balance)->toBe(0);

    // Pending coins are not spendable yet.
    journeyCart($customer);
    $this->actingAs($customer, 'customer')
        ->postJson(route('shop.checkout.coins.apply'), ['coins' => 1])
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'no_coins');

    /* ── 3. Shiprocket confirms delivery (header-authenticated) ─────── */
    DB::table('shiprocket_orders')->insert([
        'order_id' => $first->id, 'status' => 'shipped', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->withHeader('x-api-key', 'journey-token')->postJson('/webhooks/tracking', [
        'channel_order_id' => (string) $first->increment_id,
        'current_status'   => 'Delivered',
        'awb'              => '1234567890',
    ])->assertOk();

    expect($lot->refresh()->available_at->toDateTimeString())->toBe('2026-10-17 10:00:00');

    /* ── 4. Return window elapses; the daily job confirms the coins ─── */
    $this->artisan('reward-coins:confirm-available')->assertSuccessful();
    expect($lot->refresh()->status)->toBe(TransactionStatus::Pending); // still inside the window

    Carbon::setTestNow('2026-10-18 10:00:00');
    $this->artisan('reward-coins:confirm-available')->assertSuccessful();

    expect($lot->refresh()->status)->toBe(TransactionStatus::Confirmed);
    expect($this->walletRow($customerId)->balance)->toBe(100);
    expect($this->walletRow($customerId)->pending_balance)->toBe(0);

    /* ── 5. Customer sees the balance: My Coins page + header badge ─── */
    $this->actingAs($customer, 'customer')
        ->get(route('shop.customers.account.coins.index'))
        ->assertOk()
        ->assertSee('100');

    /* ── 6. Checkout renders the coins block, then apply / remove ───── */
    $cart = journeyCart($customer);

    $this->actingAs($customer, 'customer')
        ->get(route('shop.checkout.onepage.index'))
        ->assertOk()
        ->assertSee('balance:     100', false)
        ->assertSee(route('shop.checkout.coins.apply'), false);

    $probe = $this->actingAs($customer, 'customer')
        ->postJson(route('shop.checkout.coins.apply'), ['coins' => 1])
        ->assertOk();

    // max_coins is the order's coverage cap; the balance caps it further.
    $spend = min((int) $probe->json('max_coins'), 100);
    expect($spend)->toBeGreaterThan(0);

    $applied = $this->actingAs($customer, 'customer')
        ->postJson(route('shop.checkout.coins.apply'), ['coins' => $spend])
        ->assertOk()
        ->assertJsonPath('coins', $spend);

    expect((float) $applied->json('discount_value'))->toBe((float) $spend);
    expect((float) $cart->refresh()->base_discount_amount)->toBeGreaterThanOrEqual((float) $spend);

    // Removing drops the discount from the cart.
    $this->actingAs($customer, 'customer')->postJson(route('shop.checkout.coins.remove'))->assertOk();
    expect(session(CoinController::SESSION_KEY))->toBeNull();

    // Over-asking is refused with the structured error the UI shows.
    $this->actingAs($customer, 'customer')
        ->postJson(route('shop.checkout.coins.apply'), ['coins' => 101])
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'insufficient_coins');

    // Stage again for the real order.
    $this->actingAs($customer, 'customer')
        ->postJson(route('shop.checkout.coins.apply'), ['coins' => $spend])
        ->assertOk();

    expect((int) session(CoinDiscount::EFFECTIVE_KEY))->toBe($spend);

    /* ── 7. Placing the order debits exactly the staged coins ───────── */
    $second = journeyPayment($this->paidOrder($customerId, [['price' => 1000]], coins: $spend));
    $second->update(['coins_redeemed' => 0]);

    Event::dispatch('checkout.order.save.after', $second->refresh());

    expect($second->refresh()->coins_redeemed)->toBe($spend);
    expect($this->walletRow($customerId)->balance)->toBe(100 - $spend);
    expect(session(CoinController::SESSION_KEY))->toBeNull(); // staging cleared after placement

    // A re-fired order event never debits twice.
    Event::dispatch('checkout.order.save.after', $second->refresh());
    expect($this->walletRow($customerId)->balance)->toBe(100 - $spend);

    /* ── 8. A full refund restores the spent coins ───────────────────── */
    $item = $second->items()->firstOrFail();
    $this->refund($second, [$item->id => 1]);

    expect($this->walletRow($customerId)->balance)->toBe(100);
    expect($this->walletRow($customerId)->pending_balance)->toBe(0); // order-2 earnings revoked

    /* ── 9. Admin reviews and adjusts the wallet (idempotent grant) ─── */
    $this->actingAs($admin, 'admin')->get(route('admin.reward_coins.customers'))->assertOk()->assertSee($customer->email);
    $this->actingAs($admin, 'admin')->get(route('admin.reward_coins.customer', $customerId))->assertOk();

    $grant = ['amount' => 50, 'action' => 'add', 'note' => 'Journey goodwill', 'operation_key' => (string) Str::uuid()];

    $this->actingAs($admin, 'admin')->post(route('admin.reward_coins.customer.grant', $customerId), $grant)
        ->assertRedirect(route('admin.reward_coins.customer', $customerId));
    $this->actingAs($admin, 'admin')->post(route('admin.reward_coins.customer.grant', $customerId), $grant);

    expect($this->walletRow($customerId)->balance)->toBe(150);

    $this->actingAs($customer, 'customer')
        ->get(route('shop.customers.account.coins.index'))
        ->assertOk()
        ->assertSee('150')
        ->assertSee('Journey goodwill');

    /* ── 10. Ledger, lots and wallet agree at the end ────────────────── */
    $this->assertConsistent($customerId);
});
