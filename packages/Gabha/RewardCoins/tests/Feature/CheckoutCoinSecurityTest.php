<?php

use Gabha\RewardCoins\Http\Controllers\Shop\CoinController;
use Gabha\RewardCoins\Models\CoinTransaction;
use Webkul\Checkout\Models\Cart;
use Webkul\Checkout\Models\CartItem;
use Webkul\Customer\Models\Customer;

beforeEach(function () {
    config(['reward_coins.min_order_for_redemption' => 0]);
});

/**
 * An active customer cart with one simple product line, bound to the Cart
 * singleton the way the storefront resolves it for a logged-in customer.
 */
function coinCart(int $customerId, int $qty = 10): Cart
{
    $product  = (new \Webkul\Faker\Helpers\Product)->getSimpleProductFactory()->create();
    $customer = Customer::findOrFail($customerId);

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

function applyCoins($test, int $customerId, mixed $coins)
{
    return $test->actingAs(Customer::findOrFail($customerId), 'customer')
        ->postJson(route('shop.checkout.coins.apply'), ['coins' => $coins]);
}

it('rejects coin apply and remove from guests', function () {
    $this->postJson(route('shop.checkout.coins.apply'), ['coins' => 5])->assertUnauthorized();
    $this->postJson(route('shop.checkout.coins.remove'))->assertUnauthorized();

    expect(session(CoinController::SESSION_KEY))->toBeNull();
});

it('rejects malformed coin amounts', function (mixed $coins) {
    $customer = $this->customer();
    $this->earnConfirmed($this->order($customer), 100);
    coinCart($customer);

    applyCoins($this, $customer, $coins)
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('coins');

    expect(session(CoinController::SESSION_KEY))->toBeNull();
})->with([
    'missing'  => [null],
    'zero'     => [0],
    'negative' => [-5],
    'decimal'  => [1.5],
    'string'   => ['ten'],
    'array'    => [[5]],
]);

it('refuses more coins than the customer holds', function () {
    $customer = $this->customer();
    $this->earnConfirmed($this->order($customer), 3);
    coinCart($customer);

    applyCoins($this, $customer, 50)
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'insufficient_coins');

    expect(session(CoinController::SESSION_KEY))->toBeNull();
});

it('refuses to discount a cart that belongs to another customer', function () {
    $owner    = $this->customer();
    $attacker = $this->customer();
    $this->earnConfirmed($this->order($attacker), 100);

    coinCart($owner);

    applyCoins($this, $attacker, 1)
        ->assertForbidden()
        ->assertJsonPath('error_code', 'cart_mismatch');

    expect(session(CoinController::SESSION_KEY))->toBeNull();
    expect(session(\Gabha\RewardCoins\Checkout\CoinDiscount::EFFECTIVE_KEY))->toBeNull();
});

it('stages a valid redemption without debiting the wallet', function () {
    $customer = $this->customer();
    $this->earnConfirmed($this->order($customer), 100);
    coinCart($customer);

    applyCoins($this, $customer, 1)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('coins', 1);

    expect(session(CoinController::SESSION_KEY))->toBe(['coins' => 1]);

    // Staging only â€” the ledger debit happens at order placement.
    expect(CoinTransaction::query()->where('customer_id', $customer)->where('type', 'redeemed')->exists())->toBeFalse();
    expect($this->walletRow($customer)->balance)->toBe(100);
});

it('rate limits coin apply per customer', function () {
    config(['reward_coins.rate_limit.per_customer' => 3, 'reward_coins.rate_limit.per_ip' => 100]);

    $customer = $this->customer();
    $this->earnConfirmed($this->order($customer), 100);
    coinCart($customer);

    foreach (range(1, 3) as $attempt) {
        applyCoins($this, $customer, 1)->assertOk();
    }

    applyCoins($this, $customer, 1)
        ->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertJsonPath('error_code', 'too_many_attempts');

    // Remove shares the same budget.
    $this->postJson(route('shop.checkout.coins.remove'))->assertStatus(429);
});

it('rate limits coin apply per IP across customers', function () {
    config(['reward_coins.rate_limit.per_customer' => 100, 'reward_coins.rate_limit.per_ip' => 2]);

    $first  = $this->customer();
    $second = $this->customer();

    applyCoins($this, $first, 1);
    applyCoins($this, $first, 1);

    applyCoins($this, $second, 1)->assertStatus(429);
});

it('measures the redemption cap on products only, never shipping', function () {
    $service = app(\Gabha\RewardCoins\Services\CoinRedemptionService::class);

    $cart = fn (float $grand, float $shipping, float $shippingDiscount = 0) => (new Cart([
        'base_grand_total'              => $grand,
        'base_shipping_amount_incl_tax' => $shipping,
        'shipping_method'               => 'flatrate_flatrate',
    ]))->setRelation('shipping_rates', collect([
        (object) ['method' => 'flatrate_flatrate', 'base_discount_amount' => $shippingDiscount],
    ]));

    // ₹299 tee + ₹50 shipping (incl. GST).
    expect($service->eligibleTotal($cart(349, 50)))->toBe(299.0);

    // Free-shipping rule: the grand total already nets the shipping out.
    expect($service->eligibleTotal($cart(299, 50, 50)))->toBe(299.0);

    // Never negative.
    expect($service->eligibleTotal($cart(40, 50)))->toBe(0.0);
});

it('offers the same coin cap before and after shipping is chosen', function () {
    $customer = $this->customer();
    $this->earnConfirmed($this->order($customer), 1000);
    $service = app(\Gabha\RewardCoins\Services\CoinRedemptionService::class);

    $beforeShipping = new Cart(['base_grand_total' => 299, 'base_shipping_amount_incl_tax' => 0]);
    $beforeShipping->setRelation('selected_shipping_rate', null);

    $afterShipping = new Cart(['base_grand_total' => 349, 'base_shipping_amount_incl_tax' => 50]);
    $afterShipping->setRelation('selected_shipping_rate', null);

    // 20% of ₹299 = 59 coins either way (was 69 once shipping counted).
    expect($service->getRedeemableCoins($customer, $service->eligibleTotal($beforeShipping)))->toBe(59);
    expect($service->getRedeemableCoins($customer, $service->eligibleTotal($afterShipping)))->toBe(59);
});
