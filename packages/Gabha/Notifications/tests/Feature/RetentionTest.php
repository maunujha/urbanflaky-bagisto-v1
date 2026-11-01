<?php

use Gabha\Notifications\Mail\BackInStock;
use Gabha\Notifications\Mail\CampaignMail;
use Gabha\Notifications\Mail\FeedbackRequest;
use Gabha\Notifications\Mail\SubscriptionConfirmation;
use Gabha\Notifications\Models\MarketingConsent;
use Gabha\Notifications\Models\OrderFeedback;
use Gabha\Notifications\Models\StockAlert;
use Gabha\Notifications\Services\WelcomeOffer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Webkul\CartRule\Models\CartRule;
use Webkul\CartRule\Models\CartRuleCoupon;
use Webkul\Core\Models\SubscribersList;
use Webkul\Customer\Models\CustomerGroup;
use Webkul\Marketing\Helpers\Campaign;
use Webkul\Marketing\Models\Campaign as CampaignModel;
use Webkul\Marketing\Models\Template;
use Webkul\Shop\Mail\Customer\SubscriptionNotification;

require_once __DIR__.'/Helpers.php';

beforeEach(function () {
    Mail::fake();
});

function delivered($order, int $daysAgo): void
{
    DB::table('shiprocket_orders')->insert([
        'order_id' => $order->id, 'status' => 'delivered', 'delivered_at' => now()->subDays($daysAgo),
        'created_at' => now(), 'updated_at' => now(),
    ]);
}

function soldOut(): \Webkul\Product\Models\Product
{
    $product = notificationsProduct();

    stockLevel($product, 0);

    return $product->refresh();
}

function restock($product, int $qty = 5): void
{
    stockLevel($product, $qty);
}

/* Stock checks read the storefront's inventory index, as the indexer would leave it. */
function stockLevel($product, int $qty): void
{
    DB::table('product_inventories')->where('product_id', $product->id)->update(['qty' => $qty]);
    DB::table('product_inventory_indices')->where('product_id', $product->id)->update(['qty' => $qty]);
}

/* ---- Consent ledger ---- */

it('records every consent change, including Bagisto\'s token unsubscribe', function () {
    $subscriber = SubscribersList::create([
        'email' => 'ledger@example.com', 'is_subscribed' => 1, 'token' => 'tok-ledger', 'channel_id' => core()->getDefaultChannel()->id,
    ]);

    $this->get(route('shop.subscription.destroy', 'tok-ledger'))->assertRedirect();

    expect(MarketingConsent::where('email', 'ledger@example.com')->orderBy('id')->pluck('action')->all())
        ->toBe([MarketingConsent::GRANTED, MarketingConsent::WITHDRAWN]);
    expect(SubscribersList::find($subscriber->id))->toBeNull();
});

/* ---- Double opt-in ---- */

it('keeps today\'s instant subscription while double opt-in is off', function () {
    $this->postJson(route('shop.subscription.store'), ['email' => 'instant@example.com'])->assertOk();

    expect((int) SubscribersList::where('email', 'instant@example.com')->value('is_subscribed'))->toBe(1);
    Mail::assertQueued(SubscriptionNotification::class);
    Mail::assertNotQueued(SubscriptionConfirmation::class);
});

it('asks new subscribers to confirm when double opt-in is on, then sends the usual welcome', function () {
    notificationsConfig(['emails.automation.welcome.double_opt_in' => '1']);

    $this->postJson(route('shop.subscription.store'), ['email' => 'confirm@example.com'])
        ->assertOk()
        ->assertJsonPath('message', trans('shop::app.subscription.confirm-sent'));

    $subscriber = SubscribersList::where('email', 'confirm@example.com')->first();

    expect((int) $subscriber->is_subscribed)->toBe(0);
    Mail::assertNotQueued(SubscriptionNotification::class);

    $confirmation = Mail::queued(SubscriptionConfirmation::class)->first();
    expect($confirmation)->not->toBeNull();

    $this->get($confirmation->confirmUrl)->assertRedirect(route('shop.home.index'));

    expect((int) $subscriber->refresh()->is_subscribed)->toBe(1);
    Mail::assertQueued(SubscriptionNotification::class, 1);
    expect(MarketingConsent::where('email', 'confirm@example.com')->pluck('action')->all())
        ->toContain(MarketingConsent::CONFIRMATION_SENT, MarketingConsent::GRANTED);
});

it('rejects an old confirmation link once a newer one was sent', function () {
    $subscriber = SubscribersList::create([
        'email' => 'old@example.com', 'is_subscribed' => 0, 'token' => 'new-token', 'channel_id' => core()->getDefaultChannel()->id,
    ]);

    $old = URL::temporarySignedRoute('shop.notifications.subscription.confirm', now()->addDay(), ['subscriber' => $subscriber->id, 'token' => 'old-token']);

    $this->get($old)->assertRedirect(route('shop.home.index'));

    expect((int) $subscriber->refresh()->is_subscribed)->toBe(0);
});

/* ---- Welcome offer ---- */

function welcomeCoupon(array $rule = [], array $coupon = []): string
{
    $cartRule = CartRule::factory()->create(array_merge(['status' => 1, 'starts_from' => null, 'ends_till' => null], $rule));

    CartRuleCoupon::create(array_merge([
        'code' => 'WELCOME10', 'cart_rule_id' => $cartRule->id, 'usage_limit' => 0, 'times_used' => 0, 'type' => 0, 'is_primary' => 1,
    ], $coupon));

    notificationsConfig([
        'emails.automation.welcome.offer_enabled' => '1',
        'emails.automation.welcome.offer_coupon'  => 'WELCOME10',
        'emails.automation.welcome.offer_text'    => '10% off your first order',
    ]);

    return 'WELCOME10';
}

it('shows the welcome offer while its coupon is valid', function () {
    welcomeCoupon();

    expect(app(WelcomeOffer::class)->current())->toBe(['code' => 'WELCOME10', 'text' => '10% off your first order']);

    $html = (new SubscriptionNotification(SubscribersList::create([
        'email' => 'offer@example.com', 'is_subscribed' => 1, 'token' => 't-offer', 'channel_id' => core()->getDefaultChannel()->id,
    ])))->render();

    expect($html)->toContain('WELCOME10')->toContain('10% off your first order');
});

it('leaves the offer out when the coupon no longer works', function (array $rule, array $coupon) {
    welcomeCoupon($rule, $coupon);

    expect(app(WelcomeOffer::class)->current())->toBeNull();
})->with([
    'rule switched off' => [['status' => 0], []],
    'rule ended'        => [['ends_till' => now()->subDay()->toDateString()], []],
    'coupon expired'    => [[], ['expired_at' => now()->subDay()->toDateString()]],
    'coupon used up'    => [[], ['usage_limit' => 5, 'times_used' => 5]],
]);

it('shows no offer when switched off, even with a valid coupon', function () {
    welcomeCoupon();
    notificationsConfig(['emails.automation.welcome.offer_enabled' => '0']);

    expect(app(WelcomeOffer::class)->current())->toBeNull();
});

/* ---- Feedback ---- */

it('asks for a rating a week after delivery only when switched on, once', function () {
    $order = notificationsOrder(['status' => 'completed']);
    delivered($order, 8);

    $this->artisan('notifications:feedback-requests')->assertSuccessful();
    Mail::assertNothingQueued();

    notificationsConfig(['emails.automation.lifecycle.feedback_request' => '1']);

    $this->artisan('notifications:feedback-requests')->assertSuccessful();
    $this->artisan('notifications:feedback-requests')->assertSuccessful();

    Mail::assertQueued(FeedbackRequest::class, 1);
});

it('never asks for feedback without a courier-confirmed delivery', function () {
    notificationsConfig(['emails.automation.lifecycle.feedback_request' => '1']);
    notificationsOrder(['status' => 'completed', 'created_at' => now()->subDays(10)]);

    $this->artisan('notifications:feedback-requests')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('saves a rating only when the customer presses send', function () {
    $order = notificationsOrder();
    $urls = (new FeedbackRequest($order))->ratingUrls();

    $this->get($urls[4])->assertOk()->assertSee(trans('notifications::app.feedback.question'));
    expect(OrderFeedback::count())->toBe(0);

    $this->post($urls[4], ['rating' => 4, 'comment' => 'Great fit'])->assertOk()->assertSee(trans('notifications::app.feedback.thanks'));

    expect(OrderFeedback::where('order_id', $order->id)->first())
        ->rating->toBe(4)
        ->comment->toBe('Great fit');
});

it('rejects a tampered rating link', function () {
    $order = notificationsOrder();
    $url = (new FeedbackRequest($order))->ratingUrls()[5];

    $this->get(str_replace('/'.$order->id.'/5', '/'.$order->id.'/1', $url))->assertForbidden();
});

/* ---- Back in stock ---- */

it('takes a restock request for a sold-out product without emailing anyone yet', function () {
    $product = soldOut();

    $this->postJson(route('shop.notifications.stock-alerts.store'), ['product_id' => $product->id, 'email' => 'Wait@Example.com'])
        ->assertOk()
        ->assertJsonPath('message', trans('notifications::app.stock.saved'));

    $this->postJson(route('shop.notifications.stock-alerts.store'), ['product_id' => $product->id, 'email' => 'wait@example.com'])->assertOk();

    expect(StockAlert::where('product_id', $product->id)->pluck('email')->all())->toBe(['wait@example.com']);
    Mail::assertNothingQueued();
});

it('refuses restock requests for products in stock, and placeholder addresses', function () {
    $this->postJson(route('shop.notifications.stock-alerts.store'), ['product_id' => notificationsProduct()->id, 'email' => 'a@example.com'])
        ->assertStatus(422);

    $this->postJson(route('shop.notifications.stock-alerts.store'), ['product_id' => soldOut()->id, 'email' => '9876543210@noreply.urbanflaky.in'])
        ->assertStatus(422);

    expect(StockAlert::count())->toBe(0);
});

it('emails each waiting customer once when the product is back', function () {
    $product = soldOut();
    StockAlert::create(['product_id' => $product->id, 'email' => 'wait@example.com']);

    $this->artisan('notifications:back-in-stock')->assertSuccessful();
    Mail::assertNothingQueued();

    restock($product);

    $this->artisan('notifications:back-in-stock')->assertSuccessful();
    $this->artisan('notifications:back-in-stock')->assertSuccessful();

    Mail::assertQueued(BackInStock::class, 1);
    expect(StockAlert::first()->status)->toBe(StockAlert::NOTIFIED);
});

it('renders the restock email with the product and a link', function () {
    $product = notificationsProduct();
    $alert = StockAlert::create(['product_id' => $product->id, 'email' => 'wait@example.com']);

    expect((new BackInStock($alert))->render())->toContain($product->name)->toContain(trans('notifications::app.emails.back-in-stock.cta'));
});

/* ---- Campaigns ---- */

function campaignFor(string $group = 'guest'): CampaignModel
{
    $template = Template::factory()->create(['status' => 'active', 'content' => '<p>The Winter Edit is here.</p>']);

    return CampaignModel::factory()->create([
        'status'                => 1,
        'subject'               => 'The Winter Edit',
        'marketing_template_id' => $template->id,
        'marketing_event_id'    => null,
        'customer_group_id'     => CustomerGroup::where('code', $group)->value('id'),
        'channel_id'            => core()->getDefaultChannel()->id,
    ]);
}

it('sends a campaign only to subscribed guests, once, however often it runs', function () {
    campaignFor();

    foreach (['in@example.com' => 1, 'out@example.com' => 0] as $email => $subscribed) {
        SubscribersList::create(['email' => $email, 'is_subscribed' => $subscribed, 'token' => uniqid(), 'channel_id' => core()->getDefaultChannel()->id]);
    }

    app(Campaign::class)->process();
    app(Campaign::class)->process();

    Mail::assertQueued(CampaignMail::class, 1);
    Mail::assertQueued(CampaignMail::class, fn ($mail) => $mail->email === 'in@example.com' && $mail->unsubscribeUrl);
});

it('stops a campaign at the daily campaign cap and carries on the next day', function () {
    config(['notifications.limits.campaign_daily_cap' => 2]);
    campaignFor();

    foreach (['a', 'b', 'c'] as $name) {
        SubscribersList::create(['email' => "{$name}@example.com", 'is_subscribed' => 1, 'token' => uniqid(), 'channel_id' => core()->getDefaultChannel()->id]);
    }

    app(Campaign::class)->process();
    Mail::assertQueued(CampaignMail::class, 2);

    $this->travel(1)->days();

    app(Campaign::class)->process();
    Mail::assertQueued(CampaignMail::class, 3);
});

it('renders the campaign inside the store layout with an unsubscribe link', function () {
    $mail = new CampaignMail('in@example.com', campaignFor(), 'https://urbanflaky.in/email/unsubscribe?x=3');

    expect($mail->render())->toContain('The Winter Edit is here.')->toContain('https://urbanflaky.in/email/unsubscribe?x=3');
});
