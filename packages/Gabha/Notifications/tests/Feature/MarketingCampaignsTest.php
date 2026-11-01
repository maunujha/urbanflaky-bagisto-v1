<?php

use Gabha\Notifications\Mail\CampaignMail;
use Gabha\Notifications\Mail\OrderConfirmation;
use Gabha\Notifications\Models\EmailLog;
use Gabha\Notifications\Models\EmailSuppression;
use Gabha\Notifications\Models\MarketingConsent;
use Gabha\Notifications\Services\OrderConfirmationCoordinator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Webkul\Core\Models\SubscribersList;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Models\CustomerGroup;
use Webkul\Marketing\Helpers\Campaign;
use Webkul\Marketing\Models\Campaign as CampaignModel;
use Webkul\Marketing\Models\Event as MarketingEvent;
use Webkul\Marketing\Models\Template;

/*
 * Marketing email on Bagisto's own Campaigns / Templates / Events (admin
 * Marketing → Communications), sent through SafeCampaignSender: consent-only,
 * segmented by customer group, scheduled by event date, logged per campaign.
 */

require_once __DIR__.'/Helpers.php';

beforeEach(function () {
    Mail::fake();
});

function mktCampaign(string $group = 'guest', ?string $date = null): CampaignModel
{
    $template = Template::factory()->create(['status' => 'active', 'content' => '<p>New drop: The Night Edit.</p>']);

    return CampaignModel::factory()->create([
        'status'                => 1,
        'subject'               => 'The Night Edit is live',
        'marketing_template_id' => $template->id,
        'marketing_event_id'    => $date ? MarketingEvent::factory()->create(['name' => 'Sale', 'date' => $date])->id : null,
        'customer_group_id'     => CustomerGroup::where('code', $group)->value('id'),
        'channel_id'            => core()->getDefaultChannel()->id,
    ]);
}

function mktSubscriber(string $email, bool $subscribed = true, ?int $customerId = null): SubscribersList
{
    return SubscribersList::create([
        'email'         => $email,
        'is_subscribed' => $subscribed ? 1 : 0,
        'customer_id'   => $customerId,
        'token'         => uniqid(),
        'channel_id'    => core()->getDefaultChannel()->id,
    ]);
}

function mktCustomer(string $email, string $group, bool $subscribed): Customer
{
    $customer = Customer::factory()->create([
        'email'                    => $email,
        'customer_group_id'        => CustomerGroup::where('code', $group)->value('id'),
        'subscribed_to_news_letter' => $subscribed ? 1 : 0,
    ]);

    mktSubscriber($email, $subscribed, $customer->id);

    return $customer;
}

/* ---- Consent & segmentation ---- */

it('sends a group campaign only to that group\'s subscribed customers', function () {
    mktCampaign('general');

    mktCustomer('member@example.com', 'general', true);
    mktCustomer('optedout@example.com', 'general', false);
    mktCustomer('wholesale@example.com', 'wholesale', true);
    mktSubscriber('guest@example.com');

    app(Campaign::class)->process();

    Mail::assertQueued(CampaignMail::class, 1);
    Mail::assertQueued(CampaignMail::class, fn ($mail) => $mail->email === 'member@example.com');
});

it('never mails a suppressed address, even if its subscriber row says subscribed', function () {
    mktCampaign();
    mktSubscriber('stale@example.com');
    EmailSuppression::suppress('stale@example.com', EmailSuppression::SCOPE_MARKETING);

    app(Campaign::class)->process();

    Mail::assertNothingQueued();
});

it('sends nothing when campaigns are switched off', function () {
    notificationsConfig(['emails.automation.lifecycle.campaigns' => '0']);
    mktCampaign();
    mktSubscriber('in@example.com');

    app(Campaign::class)->process();

    Mail::assertNothingQueued();
});

/* ---- Scheduling ---- */

it('holds a scheduled campaign until its date', function () {
    mktCampaign('guest', now()->addDays(2)->toDateString());
    mktSubscriber('in@example.com');

    app(Campaign::class)->process();
    Mail::assertNothingQueued();

    $this->travel(2)->days();

    app(Campaign::class)->process();
    Mail::assertQueued(CampaignMail::class, 1);
});

it('does not resend a dated campaign once its send window has passed', function () {
    mktCampaign('guest', now()->subDays(10)->toDateString());
    mktSubscriber('in@example.com');

    app(Campaign::class)->process();

    Mail::assertNothingQueued();
});

/* ---- History ---- */

it('logs each campaign send against its campaign', function () {
    $campaign = mktCampaign();
    mktSubscriber('in@example.com');

    app(Campaign::class)->process();

    $log = EmailLog::where('recipient', 'in@example.com')->sole();

    expect($log->type)->toBe('campaign.'.$campaign->id)
        ->and($log->category)->toBe(EmailLog::CATEGORY_CAMPAIGN)
        ->and($log->related_id)->toBe($campaign->id);
});

/* ---- Unsubscribe ---- */

it('withdraws consent through the unsubscribe link: ledger, newsletter and account toggle', function () {
    mktCustomer('isha@example.com', 'general', true);
    $url = URL::signedRoute('shop.notifications.unsubscribe', ['email' => 'isha@example.com', 'scope' => 'marketing']);

    $this->post($url, ['List-Unsubscribe' => 'One-Click'])->assertOk();

    expect(MarketingConsent::latestFor('isha@example.com')->action)->toBe(MarketingConsent::WITHDRAWN)
        ->and((int) SubscribersList::where('email', 'isha@example.com')->value('is_subscribed'))->toBe(0)
        ->and((int) Customer::where('email', 'isha@example.com')->value('subscribed_to_news_letter'))->toBe(0);
});

it('lets a fresh opt-in lift an earlier unsubscribe', function () {
    $subscriber = mktSubscriber('back@example.com', false);
    EmailSuppression::suppress('back@example.com', EmailSuppression::SCOPE_MARKETING);

    $subscriber->update(['is_subscribed' => 1]);

    expect(EmailSuppression::isSuppressed('back@example.com', EmailSuppression::SCOPE_MARKETING))->toBeFalse();
});

/* ---- Transactional independence ---- */

it('still sends order emails to an address that unsubscribed from marketing', function () {
    bagistoMailSwitch('new_order');

    $order = notificationsOrder();
    EmailSuppression::suppress($order->customer_email, EmailSuppression::SCOPE_MARKETING);

    Event::dispatch('checkout.order.save.after', $order);
    app(OrderConfirmationCoordinator::class)->flush();

    Mail::assertQueued(OrderConfirmation::class, 1);
});
