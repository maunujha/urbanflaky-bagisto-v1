<?php

use Gabha\Notifications\Mail\OrderConfirmation;
use Gabha\Notifications\Mail\ShipmentUpdate;
use Gabha\Notifications\Mail\TestEmail;
use Gabha\Notifications\Models\EmailTemplateSetting;
use Gabha\Notifications\Support\HtmlToText;
use Gabha\Notifications\Templates\EmailCatalog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Webkul\Shop\Mail\Customer\RegistrationNotification;
use Webkul\User\Models\Admin;

require_once __DIR__.'/Helpers.php';

/*
 * Previews render only; test sends go to the tester alone. The mailer is
 * faked or the in-memory array transport throughout: nothing leaves.
 */
beforeEach(function () {
    config(['mail.default' => 'array']);

    $this->admin = Admin::factory()->create(['email' => 'owner@example.com']);
    $this->actingAs($this->admin, 'admin');

    RateLimiter::clear('email-template-test:'.$this->admin->id);
});

function sentMessages()
{
    return Mail::mailer('array')->getSymfonyTransport()->messages();
}

/* ---- Pages and previews ---- */

it('lists every customer email', function () {
    $response = $this->get(route('admin.notifications.templates.index'))->assertOk();

    foreach (app(EmailCatalog::class)->all() as $entry) {
        $response->assertSee($entry['label'], false);
    }
});

it('opens and previews every email type without sending anything', function (string $key) {
    notificationsOrder();

    $this->get(route('admin.notifications.templates.edit', $key))->assertOk();
    $this->get(route('admin.notifications.templates.preview', $key))->assertOk();
    $this->get(route('admin.notifications.templates.preview', [$key, 'text']))->assertOk();

    expect(sentMessages())->toHaveCount(0);
})->with(fn () => array_keys(app(EmailCatalog::class)->all()));

it('previews with real data only, never invented', function () {
    $order = notificationsOrder(['increment_id' => 'UF90210']);

    $this->get(route('admin.notifications.templates.preview', 'order_confirmation'))
        ->assertOk()
        ->assertSee('UF90210');
});

it('404s an unknown email type', function () {
    $this->get(route('admin.notifications.templates.edit', 'nope'))->assertNotFound();
});

/* ---- Editable subject ---- */

it('saves a subject and uses it, with placeholders, on the real email', function () {
    $this->put(route('admin.notifications.templates.update', 'order_confirmation'), ['subject' => 'Order {order_id} is in, {customer_name}'])
        ->assertRedirect(route('admin.notifications.templates.edit', 'order_confirmation'));

    $order = notificationsOrder(['increment_id' => 'UF555']);

    Mail::send(new OrderConfirmation($order));

    expect(sentMessages()->last()->getOriginalMessage()->getSubject())->toBe('Order UF555 is in, Aarav');
});

it('keeps the built-in subject when the field is cleared', function () {
    EmailTemplateSetting::create(['key' => 'order_confirmation', 'subject' => 'Custom']);

    $this->put(route('admin.notifications.templates.update', 'order_confirmation'), ['subject' => ''])->assertRedirect();

    expect(EmailTemplateSetting::subjects())->not->toHaveKey('order_confirmation');
});

it('matches each courier stage to its own subject', function () {
    EmailTemplateSetting::create(['key' => 'shipment_delivered', 'subject' => 'Delivered {order_id}']);

    $order = notificationsOrder(['increment_id' => 'UF777']);

    Mail::send(new ShipmentUpdate($order, 'out_for_delivery'));
    Mail::send(new ShipmentUpdate($order, 'delivered'));

    $subjects = sentMessages()->map(fn ($m) => $m->getOriginalMessage()->getSubject())->all();

    expect($subjects[0])->not->toBe('Delivered UF777')
        ->and($subjects[1])->toBe('Delivered UF777');
});

/* ---- Test sends ---- */

it('sends a test only to the tester, marked as a test', function () {
    notificationsOrder(['customer_email' => 'real-customer@example.com']);

    $this->post(route('admin.notifications.templates.test', 'order_confirmation'), ['email' => 'owner@example.com'])
        ->assertRedirect()
        ->assertSessionHas('success');

    $messages = sentMessages();

    expect($messages)->toHaveCount(1);

    $message = $messages->first()->getOriginalMessage();

    expect(collect($message->getTo())->map->getAddress()->all())->toBe(['owner@example.com'])
        ->and($message->getSubject())->toStartWith('[Test] ')
        ->and($message->getTextBody())->not->toBeEmpty();
});

it('limits test sends per hour', function () {
    notificationsOrder();

    foreach (range(1, 6) as $i) {
        $this->post(route('admin.notifications.templates.test', 'order_confirmation'), ['email' => 'owner@example.com']);
    }

    expect(sentMessages())->toHaveCount(5);
});

it('refuses a test to a placeholder address', function () {
    notificationsOrder();

    $this->post(route('admin.notifications.templates.test', 'order_confirmation'), ['email' => '9876543210@noreply.urbanflaky.in'])
        ->assertSessionHas('error');

    expect(sentMessages())->toHaveCount(0);
});

/* ---- Plain text ---- */

it('adds a plain-text part to emails that have none', function () {
    $customer = Webkul\Customer\Models\Customer::factory()->create(['email' => 'plain@example.com', 'first_name' => 'Kabir']);

    Mail::send(new RegistrationNotification($customer));

    $text = sentMessages()->last()->getOriginalMessage()->getTextBody();

    expect($text)->toContain('Kabir')->not->toContain('<');
});

it('turns HTML into readable text with working links', function () {
    $text = HtmlToText::convert('<html><head><style>p{}</style></head><body><div style="display:none">preheader</div><p>Hello <b>you</b></p><a href="https://urbanflaky.in/x">Track order</a></body></html>');

    expect($text)->toBe("Hello you\nTrack order (https://urbanflaky.in/x)");
});

/* ---- Layout ---- */

it('puts support, social links and the store address in every email footer', function () {
    $html = (new OrderConfirmation(notificationsOrder()))->render();

    expect($html)
        ->toContain(core()->getContactEmailDetails()['email'])
        ->toContain('https://instagram.com/urbanflaky')
        ->toContain('Gabha Enterprise');
});

it('adds the unsubscribe line only to emails that can be opted out of', function () {
    $order = notificationsOrder();

    expect((new OrderConfirmation($order))->render())->not->toContain(trans('shop::app.emails.footer.unsubscribe'));

    expect((new Gabha\Notifications\Mail\WinBack(Webkul\Customer\Models\Customer::factory()->create(), 'https://urbanflaky.in/u'))->render())
        ->toContain('https://urbanflaky.in/u');
});
