<?php

use App\Jobs\Shiprocket\PushOrderToShiprocket;
use App\Models\ShiprocketOrder;
use App\Models\ShiprocketTrackingEvent;
use App\Services\Shipping\DeliveryRules;
use App\Services\Shiprocket\OrderPayloadBuilder;
use App\Services\Shiprocket\ServiceabilityService;
use App\Services\Shiprocket\ShipmentStage;
use App\Services\Shiprocket\ShiprocketClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Webkul\Checkout\Models\Cart;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;

/*
| End-to-end Shiprocket flow against a faked API: payload money, push
| idempotency, courier assignment, webhook → Bagisto shipment → delivery,
| public tracking lookup and the delivery/COD rules. No request ever leaves
| the test (preventStrayRequests).
*/

beforeEach(function () {
    config([
        'shiprocket.email'         => 'api@example.com',
        'shiprocket.password'      => 'secret',
        'shiprocket.webhook_token' => 'hook-token',
    ]);

    Cache::put(ShiprocketClient::TOKEN_KEY, 'test-token', 3600);

    Http::preventStrayRequests();
});

/**
 * A COD order: 2 × ₹299 (GST-inclusive), free shipping, ₹50 of coins.
 * Bagisto stores grand = sub_total_incl_tax + shipping_incl_tax − discount.
 */
function shippingOrder(array $overrides = [], string $method = 'cashondelivery'): Order
{
    $product = (new \Webkul\Faker\Helpers\Product)->getSimpleProductFactory()->create();

    $order = Order::factory()->create(array_merge([
        'status'                        => 'pending',
        'customer_email'                => 'buyer@example.com',
        'customer_first_name'           => 'Asha',
        'customer_last_name'            => 'Verma',
        'base_currency_code'            => 'INR',
        'channel_currency_code'         => 'INR',
        'order_currency_code'           => 'INR',
        'sub_total'                     => 534,
        'base_sub_total'                => 534,
        'sub_total_incl_tax'            => 598,
        'base_sub_total_incl_tax'       => 598,
        'tax_amount'                    => 64,
        'base_tax_amount'               => 64,
        'shipping_amount'               => 0,
        'base_shipping_amount'          => 0,
        'shipping_amount_incl_tax'      => 0,
        'base_shipping_amount_incl_tax' => 0,
        'discount_amount'               => 50,
        'base_discount_amount'          => 50,
        'coins_redeemed'                => 50,
        'grand_total'                   => 548,
        'base_grand_total'              => 548,
        'grand_total_invoiced'          => 0,
        'base_grand_total_invoiced'     => 0,
        'sub_total_invoiced'            => 0,
        'base_sub_total_invoiced'       => 0,
    ], $overrides));

    OrderItem::factory()->create([
        'order_id'            => $order->id,
        'product_id'          => $product->id,
        'sku'                 => 'UF-TEE-BLK-M',
        'name'                => 'Heavyweight Oversized Tee',
        'type'                => 'simple',
        'qty_ordered'         => 2,
        'qty_shipped'         => 0,
        'qty_invoiced'        => 0,
        'weight'              => 250,
        'price'               => 267,
        'base_price'          => 267,
        'price_incl_tax'      => 299,
        'base_price_incl_tax' => 299,
        'total'               => 534,
        'base_total'          => 534,
        'total_incl_tax'      => 598,
        'base_total_incl_tax' => 598,
        'tax_percent'         => 12,
        'tax_amount'          => 64,
        'base_tax_amount'     => 64,
        'total_invoiced'      => 0,
        'base_total_invoiced' => 0,
    ]);

    foreach ([OrderAddress::ADDRESS_TYPE_SHIPPING, OrderAddress::ADDRESS_TYPE_BILLING] as $type) {
        OrderAddress::factory()->create([
            'order_id'     => $order->id,
            'address_type' => $type,
            'first_name'   => 'Asha',
            'last_name'    => 'Verma',
            'email'        => 'buyer@example.com',
            'phone'        => '+91 98765 43210',
            'address'      => "12 Civil Lines\nNear Clock Tower",
            'city'         => 'Jaipur',
            'state'        => 'RJ',
            'country'      => 'IN',
            'postcode'     => '302001',
        ]);
    }

    OrderPayment::factory()->create(['order_id' => $order->id, 'method' => $method]);

    return $order->refresh();
}

function fakeShiprocket(array $extra = []): void
{
    Http::fake($extra + [
        '*/orders/create/adhoc' => Http::response(['order_id' => 9001, 'shipment_id' => 8001, 'status' => 'NEW']),
        '*/courier/assign/awb'  => Http::response([
            'awb_assign_status' => 1,
            'response'          => ['data' => ['awb_code' => 'AWB123', 'courier_name' => 'Delhivery Surface', 'courier_company_id' => 12]],
        ]),
    ]);
}

function webhook($test, array $payload, string $token = 'hook-token')
{
    return $test->withHeader('x-api-key', $token)->postJson('/webhooks/tracking', $payload);
}

it('sends a COD amount equal to the order total, coins and GST included', function () {
    $payload = app(OrderPayloadBuilder::class)->build(shippingOrder());

    $shiprocketTotal = $payload['sub_total'] - $payload['total_discount'] + $payload['shipping_charges'];

    expect($shiprocketTotal)->toEqual(548.0)
        ->and($payload['payment_method'])->toBe('COD')
        ->and($payload['order_items'][0]['selling_price'])->toEqual(299.0)
        ->and($payload['order_items'][0]['tax'])->toEqual(12.0)
        ->and($payload['order_items'][0]['hsn'])->toBe('6109')
        ->and($payload['shipping_phone'])->toBe('9876543210')
        ->and($payload['shipping_address'])->toBe('12 Civil Lines, Near Clock Tower')
        ->and($payload['shipping_state'])->toBe('Rajasthan')
        ->and($payload['weight'])->toEqual(0.5);
});

it('marks prepaid orders as prepaid', function () {
    expect(app(OrderPayloadBuilder::class)->build(shippingOrder([], 'razorpay'))['payment_method'])->toBe('Prepaid');
});

it('pushes an order once and assigns the recommended courier', function () {
    fakeShiprocket();

    $order = shippingOrder();

    PushOrderToShiprocket::dispatchSync($order->id);
    PushOrderToShiprocket::dispatchSync($order->id);

    $shipment = ShiprocketOrder::where('order_id', $order->id)->first();

    expect($shipment->shiprocket_order_id)->toBe('9001')
        ->and($shipment->shipment_id)->toBe('8001')
        ->and($shipment->awb_code)->toBe('AWB123')
        ->and($shipment->courier_name)->toBe('Delhivery Surface')
        ->and($shipment->status)->toBe(ShipmentStage::AWB_ASSIGNED)
        ->and($shipment->is_cod)->toBeTrue();

    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => str_ends_with($request->url(), 'courier/assign/awb') && ! isset($request['courier_id']));
});

it('records a failed push without breaking the request on the sync queue', function () {
    Http::fake(['*/orders/create/adhoc' => Http::response(['message' => 'Invalid pickup location'], 422)]);

    $order = shippingOrder();

    PushOrderToShiprocket::dispatchSync($order->id);

    $shipment = ShiprocketOrder::where('order_id', $order->id)->first();

    expect($shipment->status)->toBe(ShipmentStage::PUSH_FAILED)
        ->and($shipment->last_error)->toContain('Invalid pickup location')
        ->and($shipment->push_attempts)->toBe(1);
});

it('does not push cancelled orders', function () {
    Http::fake();

    PushOrderToShiprocket::dispatchSync(shippingOrder(['status' => 'canceled'])->id);

    Http::assertNothingSent();
});

it('rejects webhooks without the token', function () {
    webhook($this, ['awb' => 'AWB123', 'current_status' => 'DELIVERED'], 'wrong')->assertUnauthorized();
});

it('ships, then completes and invoices a COD order from courier webhooks', function () {
    fakeShiprocket();

    $order = shippingOrder();
    PushOrderToShiprocket::dispatchSync($order->id);

    $delivered = [];
    Event::listen('shiprocket.order.delivered', function ($o) use (&$delivered) {
        $delivered[] = $o->id;
    });

    webhook($this, [
        'awb'            => 'AWB123',
        'current_status' => 'PICKED UP',
        'courier_name'   => 'Delhivery Surface',
        'scans'          => [['date' => '2026-10-11 10:00:00', 'activity' => 'Shipment picked up', 'location' => 'Dholpur', 'sr-status-label' => 'PICKED UP']],
    ])->assertOk();

    $order->refresh();

    expect($order->status)->toBe(Order::STATUS_SHIPPED)
        ->and($order->shipments)->toHaveCount(1)
        ->and($order->shipments->first()->track_number)->toBe('AWB123')
        ->and($order->shipments->first()->carrier_title)->toBe('Delhivery Surface');

    webhook($this, ['awb' => 'AWB123', 'current_status' => 'DELIVERED'])->assertOk();

    $order->refresh();
    $shipment = ShiprocketOrder::where('order_id', $order->id)->first();

    expect($shipment->status)->toBe(ShipmentStage::DELIVERED)
        ->and($shipment->delivered_at)->not->toBeNull()
        ->and($order->status)->toBe(Order::STATUS_COMPLETED)
        ->and($order->invoices)->toHaveCount(1)
        ->and($delivered)->toBe([$order->id]);
});

it('never moves a shipment backwards and stores each scan once', function () {
    fakeShiprocket();

    $order = shippingOrder();
    PushOrderToShiprocket::dispatchSync($order->id);

    $scan = ['date' => '2026-10-12 09:00:00', 'activity' => 'Out for delivery', 'location' => 'Jaipur', 'sr-status-label' => 'OUT FOR DELIVERY'];

    webhook($this, ['awb' => 'AWB123', 'current_status' => 'OUT FOR DELIVERY', 'scans' => [$scan]]);
    webhook($this, ['awb' => 'AWB123', 'current_status' => 'OUT FOR DELIVERY', 'scans' => [$scan]]);
    webhook($this, ['awb' => 'AWB123', 'current_status' => 'IN TRANSIT']);

    expect(ShiprocketOrder::where('order_id', $order->id)->value('status'))->toBe(ShipmentStage::OUT_FOR_DELIVERY)
        ->and(ShiprocketTrackingEvent::where('order_id', $order->id)->where('location', 'Jaipur')->count())->toBe(1)
        ->and(ShiprocketTrackingEvent::where('order_id', $order->id)->where('stage', ShipmentStage::OUT_FOR_DELIVERY)->whereNull('location')->count())->toBe(1);
});

it('maps courier wording to stages', function (string $status, ?string $stage) {
    expect(ShipmentStage::fromStatus($status))->toBe($stage);
})->with([
    ['Delivered', ShipmentStage::DELIVERED],
    ['RTO DELIVERED', ShipmentStage::RTO_DELIVERED],
    ['RTO In Transit', ShipmentStage::RTO_INITIATED],
    ['OUT FOR PICKUP', ShipmentStage::PICKUP_SCHEDULED],
    ['Picked Up', ShipmentStage::PICKED_UP],
    ['Out For Delivery', ShipmentStage::OUT_FOR_DELIVERY],
    ['UNDELIVERED', ShipmentStage::UNDELIVERED],
    ['Reached at Destination Hub', ShipmentStage::IN_TRANSIT],
    ['Canceled', ShipmentStage::CANCELED],
    ['Cancellation Requested', null],
    ['', null],
]);

it('tracks by order number with the right email or phone only', function () {
    fakeShiprocket();

    $order = shippingOrder();
    PushOrderToShiprocket::dispatchSync($order->id);

    /* The live refresh is skipped: just synced. */
    ShiprocketOrder::where('order_id', $order->id)->update(['last_synced_at' => now()]);

    $this->postJson(route('shop.track-order.track'), ['order_id' => $order->increment_id, 'contact' => 'someone@else.com'])
        ->assertOk()->assertJson(['found' => false]);

    $this->postJson(route('shop.track-order.track'), ['order_id' => $order->increment_id, 'contact' => '98765 43210'])
        ->assertOk()->assertJson(['found' => true, 'awb' => 'AWB123', 'state' => ShipmentStage::AWB_ASSIGNED]);

    $this->postJson(route('shop.track-order.track'), ['order_id' => $order->increment_id, 'contact' => 'BUYER@example.com'])
        ->assertOk()->assertJson(['found' => true]);
});

it('tracks only AWBs this store issued', function () {
    fakeShiprocket();

    $order = shippingOrder();
    PushOrderToShiprocket::dispatchSync($order->id);
    ShiprocketOrder::where('order_id', $order->id)->update(['last_synced_at' => now()]);

    $this->postJson(route('shop.track-order.track'), ['awb' => '000000000000'])->assertJson(['found' => false]);
    /* An AWB alone never reveals the order number, destination or date. */
    $this->postJson(route('shop.track-order.track'), ['awb' => 'AWB123'])
        ->assertJson(['found' => true, 'awb' => 'AWB123', 'order_id' => null, 'destination' => null, 'placed_on' => null]);
});

it('applies the free-shipping threshold from admin settings', function () {
    $rules = app(DeliveryRules::class);

    config(['carriers.free.active' => true, 'carriers.free.min_order_amount' => '', 'carriers.flatrate.active' => true]);
    expect($rules->qualifiesForFreeShipping(1))->toBeTrue()
        ->and($rules->promise())->toBe('Free delivery');

    config(['carriers.free.min_order_amount' => '999']);
    expect($rules->qualifiesForFreeShipping(998))->toBeFalse()
        ->and($rules->qualifiesForFreeShipping(999))->toBeTrue()
        ->and($rules->promise())->toBe('Free delivery over ₹999');

    config(['carriers.free.active' => false]);
    expect($rules->qualifiesForFreeShipping(5000))->toBeFalse()
        ->and($rules->promise())->toBeNull();
});

it('hides COD above the maximum order value', function () {
    $rules = app(DeliveryRules::class);

    $cart = new Cart(['base_grand_total' => 2500]);
    expect($rules->codMaximum())->toEqual(2000.0)
        ->and($rules->codAllowed($cart))->toBeFalse();

    $cart->base_grand_total = 1999;
    expect($rules->codAllowed($cart))->toBeTrue();

    config(['payment_methods.cashondelivery.max_order_total' => '']);
    $cart->base_grand_total = 50000;
    expect($rules->codAllowed($cart))->toBeTrue();
});

it('reads serviceability and COD support per pincode', function () {
    Http::fake([
        '*delivery_postcode=302001*' => Http::response(['data' => ['available_courier_companies' => [
            ['courier_company_id' => 12, 'courier_name' => 'Delhivery Surface', 'rate' => 61.7, 'cod' => 1, 'etd' => 'Oct 12, 2026', 'estimated_delivery_days' => '2'],
        ]]]),
        '*delivery_postcode=799001*' => Http::response(['data' => ['available_courier_companies' => [
            ['courier_company_id' => 7, 'courier_name' => 'Air', 'rate' => 120, 'cod' => 0, 'etd' => 'Oct 15, 2026', 'estimated_delivery_days' => '5'],
        ]]]),
        '*delivery_postcode=999999*' => Http::response(['message' => 'No courier service available', 'status' => 404]),
    ]);

    $service = app(ServiceabilityService::class);

    expect($service->check('302001'))->toMatchArray(['serviceable' => true, 'cod' => true, 'etd' => '2026-10-12', 'days' => 2])
        ->and($service->check('799001'))->toMatchArray(['serviceable' => true, 'cod' => false])
        ->and($service->check('999999'))->toMatchArray(['serviceable' => false, 'cod' => false]);
});

it('fails open when Shiprocket is unreachable', function () {
    Http::fake(['*' => Http::response('Bad gateway', 502)]);

    expect(app(ServiceabilityService::class)->check('302001'))->toBeNull();
});

/*
| Admin surfaces.
*/

it('shows the Shiprocket, free-shipping and COD settings in admin', function () {
    $this->actingAs(\Webkul\User\Models\Admin::factory()->create(), 'admin');

    $this->get(route('admin.configuration.index', ['slug' => 'sales', 'slug2' => 'shiprocket']))
        ->assertOk()
        ->assertSee('Assign the recommended courier automatically')
        ->assertSee('Pickup Location Name')
        ->assertSee('HSN Code');

    $this->get(route('admin.configuration.index', ['slug' => 'sales', 'slug2' => 'carriers']))
        ->assertOk()
        ->assertSee('Minimum Order Amount');

    $this->get(route('admin.configuration.index', ['slug' => 'sales', 'slug2' => 'payment_methods']))
        ->assertOk()
        ->assertSee('Maximum Order Value for COD')
        ->assertSee('Check COD Availability by Pincode');
});

it('shows the Shiprocket panel on the admin order page and pushes from it', function () {
    fakeShiprocket();

    $this->actingAs(\Webkul\User\Models\Admin::factory()->create(), 'admin');

    $order = shippingOrder();

    $this->get(route('admin.sales.orders.view', $order->id))
        ->assertOk()
        ->assertSeeText('Push to Shiprocket');

    $this->post(route('admin.sales.orders.shiprocket.push', $order->id))
        ->assertRedirect(route('admin.sales.orders.view', $order->id))
        ->assertSessionHas('success');

    $this->get(route('admin.sales.orders.view', $order->id))
        ->assertOk()
        ->assertSeeText('AWB123')
        ->assertSeeText('Book pickup')
        ->assertSeeText('Change courier');
});

it('still renders the admin create-order page, which shares the panel hook', function () {
    $this->actingAs(\Webkul\User\Models\Admin::factory()->create(), 'admin');

    $cart = \Webkul\Checkout\Models\Cart::factory()->create([
        'customer_id' => \Webkul\Customer\Models\Customer::factory()->create()->id,
        'is_active'   => 0,
    ]);

    $this->get(route('admin.sales.orders.create', $cart->id))->assertOk();
});

/*
| Code-review fixes.
*/

it('never pushes an order that was already shipped another way', function () {
    Http::fake();

    $order = shippingOrder();

    $item = $order->items->first();
    \Webkul\Sales\Models\Shipment::factory()->create(['order_id' => $order->id]);
    $item->update(['qty_shipped' => $item->qty_ordered]);

    PushOrderToShiprocket::dispatchSync($order->id);

    Http::assertNothingSent();
    expect(ShiprocketOrder::where('order_id', $order->id)->exists())->toBeFalse();
});

it('keeps the AWB when the current courier is re-selected', function () {
    fakeShiprocket(['*/orders/cancel/shipment/awbs' => Http::response(['message' => 'ok'])]);

    $order = shippingOrder();
    PushOrderToShiprocket::dispatchSync($order->id);

    $shipment = ShiprocketOrder::where('order_id', $order->id)->first();

    app(\App\Services\Shiprocket\ShipmentService::class)->assignCourier($shipment, '12');

    expect($shipment->refresh()->awb_code)->toBe('AWB123');
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'cancel/shipment/awbs'));
});

it('ships free below the minimum when no paid option is switched on', function () {
    $rules = app(DeliveryRules::class);

    config(['carriers.free.active' => true, 'carriers.free.min_order_amount' => '999', 'carriers.flatrate.active' => false]);

    expect($rules->qualifiesForFreeShipping(500))->toBeTrue()
        ->and($rules->promise())->toBe('Free delivery');

    config(['carriers.flatrate.active' => true]);

    expect($rules->qualifiesForFreeShipping(500))->toBeFalse()
        ->and($rules->promise())->toBe('Free delivery over ₹999');
});

it('does not treat a Shiprocket validation error as undeliverable, nor cache it', function () {
    Http::fake(['*' => Http::sequence()
        ->push(['message' => 'Invalid pickup postcode'], 422)
        ->push(['data' => ['available_courier_companies' => [['courier_company_id' => 1, 'courier_name' => 'X', 'rate' => 50, 'cod' => 1]]]])]);

    $service = app(ServiceabilityService::class);

    expect($service->check('302001'))->toBeNull()
        ->and($service->check('302001'))->toMatchArray(['serviceable' => true]);
});

it('sends no shipped email or shipment when the first update is a return', function () {
    fakeShiprocket();

    $order = shippingOrder();
    PushOrderToShiprocket::dispatchSync($order->id);

    webhook($this, ['awb' => 'AWB123', 'current_status' => 'RTO DELIVERED'])->assertOk();

    expect($order->refresh()->shipments)->toHaveCount(0)
        ->and(ShiprocketOrder::where('order_id', $order->id)->value('status'))->toBe(ShipmentStage::RTO_DELIVERED);
});

it('does not complete a delivered order that Bagisto could not ship', function () {
    fakeShiprocket();

    $order = shippingOrder();
    PushOrderToShiprocket::dispatchSync($order->id);

    /* No shipping address: the Bagisto shipment cannot be created. */
    OrderAddress::where('order_id', $order->id)->where('address_type', OrderAddress::ADDRESS_TYPE_SHIPPING)->delete();

    webhook($this, ['awb' => 'AWB123', 'current_status' => 'DELIVERED'])->assertOk();

    expect($order->refresh()->status)->not->toBe(Order::STATUS_COMPLETED)
        ->and(ShiprocketOrder::where('order_id', $order->id)->value('status'))->toBe(ShipmentStage::DELIVERED);
});

it('adds the shipping and COD fields without editing core admin config', function () {
    $core = collect(config('core'));

    expect(collect($core->firstWhere('key', 'sales.carriers.free')['fields'])->where('name', 'min_order_amount'))->toHaveCount(1)
        ->and(collect($core->firstWhere('key', 'sales.payment_methods.cashondelivery')['fields'])->pluck('name'))
        ->toContain('max_order_total', 'check_pincode');
});
