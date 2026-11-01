<?php

use App\Services\Security\AtomicLimiter;
use App\Support\IndianMobile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Webkul\Customer\Models\Customer;
use Webkul\Shop\Mail\ContactUs;

require_once __DIR__.'/helpers.php';

beforeEach(function () {
    $this->prefix = secIsolateSecurityKeys();
    $this->sms = secFakeSms();
    secCaptchaOn();
    secFakeGoogle();
    Mail::fake();
});

function secSendOtp($test, string $flow, string $phone, string $ip = '10.0.0.1', ?string $token = null)
{
    return $test->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson("/api/{$flow}/otp/send", ['phone' => $phone, 'recaptcha_token' => $token ?? secToken()]);
}

function secVerifyOtp($test, string $flow, string $phone, string $otp, string $ip = '10.0.0.1')
{
    return $test->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson("/api/{$flow}/otp/verify", ['phone' => $phone, 'otp' => $otp]);
}

/* ── 3. Indian mobile validation ─────────────────────────────────────── */

it('normalises honest formats and rejects everything else', function (string $input, ?string $expected) {
    expect(IndianMobile::normalize($input))->toBe($expected);
})->with([
    ['9876543210', '9876543210'],
    ['+91 98765 43210', '9876543210'],
    ['919876543210', '9876543210'],
    ['09876543210', '9876543210'],
    ['98765-43210', '9876543210'],
    ['5876543210', null],              // starts with 5
    ['0123456789', null],
    ['987654321', null],               // 9 digits
    ['98765432101', null],             // 11 digits, no leading 0
    ['55555555559876543210', null],    // padding attack
    ['9876543210abc', null],
    ['', null],
]);

it('rejects invalid Indian mobiles on send and verify without sending SMS', function (string $flow, string $phone) {
    secSendOtp($this, $flow, $phone)->assertStatus(422);
    secVerifyOtp($this, $flow, $phone, '123456')->assertStatus(422);

    expect($this->sms)->toHaveCount(0);
})->with(['contact', 'checkout'])->with(['5876543210', '55555555559876543210', '12345', 'abcdefghij']);

/* ── 1. Verification binding (contact) ───────────────────────────────── */

function secVerifiedContactSession($test, string $phone = '9876543210'): void
{
    secSendOtp($test, 'contact', $phone)->assertOk();
    $otp = $test->sms[count($test->sms) - 1][1];
    secVerifyOtp($test, 'contact', $phone, $otp)->assertOk();
}

function secSubmitContact($test, string $contact)
{
    return $test->postJson('/contact-us/send-mail', [
        'name' => 'Asha', 'email' => 'asha@example.com', 'contact' => $contact,
        'topic' => 'Order Issue', 'message' => 'Hello', 'recaptcha_token' => secToken(),
    ]);
}

it('rejects a contact submission without OTP verification', function () {
    secSubmitContact($this, '9876543210')->assertStatus(422)->assertJsonValidationErrors('contact');

    Mail::assertNothingQueued();
});

it('rejects padded and suffix-manipulated numbers after verification', function (string $tampered) {
    secVerifiedContactSession($this);

    secSubmitContact($this, $tampered)->assertStatus(422)->assertJsonValidationErrors('contact');

    Mail::assertNotQueued(ContactUs::class);
})->with(['55555555559876543210', '19876543210', '9876543211', '+91 9876543211']);

it('mails the server-verified number, not the raw input', function () {
    secVerifiedContactSession($this);

    secSubmitContact($this, '+91 98765 43210')->assertRedirect();

    Mail::assertQueued(ContactUs::class, fn ($mail) => $mail->contactUs['contact'] === '9876543210');

    /* One verification = one enquiry. */
    secSubmitContact($this, '9876543210')->assertStatus(422);
});

it('drops the verified state when an OTP is requested for another number', function () {
    secVerifiedContactSession($this);
    secSendOtp($this, 'contact', '9123456780')->assertOk();

    secSubmitContact($this, '9876543210')->assertStatus(422);
    secSubmitContact($this, '9123456780')->assertStatus(422);
});

/* ── 2. SMS rate limiting ────────────────────────────────────────────── */

it('caps sends per phone per window, separately per flow', function () {
    foreach (range(1, 3) as $i) {
        secSendOtp($this, 'contact', '9876543210')->assertOk();
    }

    secSendOtp($this, 'contact', '9876543210')->assertStatus(429)->assertJsonPath('message', fn ($m) => str_contains($m, 'Too many OTP requests'));

    /* Contact abuse of a number must not block that customer's checkout. */
    secSendOtp($this, 'checkout', '9876543210')->assertOk();

    expect($this->sms)->toHaveCount(4);
});

it('caps sends per phone per day', function () {
    config(['otp_security.send.per_phone_window_max' => 100]);

    foreach (range(1, 10) as $i) {
        secSendOtp($this, 'checkout', '9876543210', "10.0.1.{$i}")->assertOk();
    }

    secSendOtp($this, 'checkout', '9876543210', '10.0.1.99')->assertStatus(429);

    expect($this->sms)->toHaveCount(10);
});

it('caps sends per IP per day across both flows', function () {
    foreach (range(0, 19) as $i) {
        $flow = $i % 2 ? 'checkout' : 'contact';
        secSendOtp($this, $flow, '98765432'.str_pad($i, 2, '0', STR_PAD_LEFT), '10.0.2.1')->assertOk();
    }

    secSendOtp($this, 'contact', '9000000001', '10.0.2.1')->assertStatus(429);
    secSendOtp($this, 'checkout', '9000000002', '10.0.2.1')->assertStatus(429);

    /* A different client is unaffected. */
    secSendOtp($this, 'checkout', '9000000003', '10.0.2.2')->assertOk();

    expect($this->sms)->toHaveCount(21);
});

it('buckets missing or invalid client IPs together instead of skipping the limit', function () {
    config(['otp_security.send.per_ip_daily_max' => 2]);

    secSendOtp($this, 'contact', '9000000001', '')->assertOk();
    secSendOtp($this, 'contact', '9000000002', 'not-an-ip')->assertOk();
    secSendOtp($this, 'contact', '9000000003', '')->assertStatus(429);
});

it('groups IPv6 clients by /64 so address rotation does not reset the IP cap', function () {
    config(['otp_security.send.per_ip_daily_max' => 2]);

    secSendOtp($this, 'contact', '9000000001', '2001:db8:1:2::1')->assertOk();
    secSendOtp($this, 'contact', '9000000002', '2001:db8:1:2::ffff')->assertOk();
    secSendOtp($this, 'contact', '9000000003', '2001:db8:1:2:abcd::9')->assertStatus(429);
    secSendOtp($this, 'contact', '9000000004', '2001:db8:1:3::1')->assertOk();
});

it('enforces the site ceiling while reserving checkout capacity from contact abuse', function () {
    config(['otp_security.send.site_daily_max' => 5, 'otp_security.send.site_daily_max_contact' => 2]);

    secSendOtp($this, 'contact', '9000000001', '10.0.3.1')->assertOk();
    secSendOtp($this, 'contact', '9000000002', '10.0.3.2')->assertOk();
    secSendOtp($this, 'contact', '9000000003', '10.0.3.3')->assertStatus(429)
        ->assertJsonPath('message', fn ($m) => str_contains($m, 'busy'));

    /* Checkout still has the rest of the global budget. */
    foreach (range(4, 6) as $i) {
        secSendOtp($this, 'checkout', "900000000{$i}", "10.0.3.{$i}")->assertOk();
    }

    secSendOtp($this, 'checkout', '9000000007', '10.0.3.7')->assertStatus(429);

    expect($this->sms)->toHaveCount(5);
});

it('does not consume any bucket when one bucket rejects', function () {
    config(['otp_security.send.per_ip_daily_max' => 1]);

    secSendOtp($this, 'contact', '9000000001', '10.0.4.1')->assertOk();
    secSendOtp($this, 'contact', '9000000002', '10.0.4.1')->assertStatus(429);

    /* The rejected attempt did not count against phone 9000000002. */
    config(['otp_security.send.per_phone_window_max' => 1]);
    secSendOtp($this, 'contact', '9000000002', '10.0.4.2')->assertOk();
});

it('never lets concurrent requests overshoot a limit', function () {
    $results = secRunConcurrently(12, ['reserve'], $this->prefix);

    $counts = array_count_values($results);
    ksort($counts);

    expect($counts)->toBe(['allowed' => 3, 'blocked' => 9]);
});

it('fails closed with no SMS when Redis is unavailable', function () {
    config(['otp_security.redis_connection' => 'missing-connection']);

    secSendOtp($this, 'contact', '9876543210')->assertStatus(503);
    secSendOtp($this, 'checkout', '9876543210')->assertStatus(503);

    expect($this->sms)->toHaveCount(0);
});

/* ── 4. reCAPTCHA v3 on Send OTP ─────────────────────────────────────── */

it('rejects bad reCAPTCHA verdicts on Send OTP', function (array $google) {
    secFakeGoogle($google);

    secSendOtp($this, 'contact', '9876543210')->assertStatus(422);
    secSendOtp($this, 'checkout', '9876543210')->assertStatus(422);

    expect($this->sms)->toHaveCount(0);
})->with([
    'invalid/expired' => [['success' => false, 'error-codes' => ['timeout-or-duplicate']]],
    'low score'       => [['score' => 0.2]],
    'wrong action'    => [['action' => 'checkout']],
    'wrong hostname'  => [['hostname' => 'evil.example']],
    'no score'        => [['score' => null]],
]);

it('rejects a missing token without calling Google', function () {
    $this->postJson('/api/contact/otp/send', ['phone' => '9876543210'])->assertStatus(422);

    Http::assertNothingSent();
    expect($this->sms)->toHaveCount(0);
});

it('rejects a reused token even if Google would accept it', function () {
    $token = secToken();

    secSendOtp($this, 'contact', '9876543210', token: $token)->assertOk();
    secSendOtp($this, 'contact', '9876543210', token: $token)->assertStatus(422);

    Http::assertSentCount(1);
});

it('honours the configurable score threshold', function () {
    secFakeGoogle(['score' => 0.6]);
    config(['otp_security.recaptcha.min_score' => 0.7]);

    secSendOtp($this, 'contact', '9876543210')->assertStatus(422);

    config(['otp_security.recaptcha.min_score' => 0.5]);

    secSendOtp($this, 'contact', '9876543210')->assertOk();
});

it('fails open only on a Google outage, and only when configured to', function () {
    secFakeGoogle(status: 503);

    config(['otp_security.recaptcha.fail_open_on_transport_error' => true]);
    secSendOtp($this, 'checkout', '9876543210')->assertOk();

    config(['otp_security.recaptcha.fail_open_on_transport_error' => false]);
    secSendOtp($this, 'checkout', '9123456780')->assertStatus(422);
});

it('skips the reCAPTCHA check entirely when it is disabled in admin', function () {
    secCaptchaOff();

    $this->postJson('/api/contact/otp/send', ['phone' => '9876543210'])->assertOk();

    Http::assertNothingSent();
});

it('never renders the reCAPTCHA secret on the storefront', function () {
    $this->get('/contact-us')->assertOk()->assertDontSee('test-secret-never-rendered');
});

/* ── 5. Incorrect attempts ───────────────────────────────────────────── */

it('invalidates the OTP after the fifth incorrect attempt', function (string $flow) {
    secSendOtp($this, $flow, '9876543210')->assertOk();
    $otp = $this->sms[0][1];
    $wrong = $otp === '000000' ? '111111' : '000000';

    foreach (range(1, 4) as $i) {
        secVerifyOtp($this, $flow, '9876543210', $wrong)->assertStatus(422)
            ->assertJsonPath('message', 'Incorrect OTP. '.(5 - $i).' '.str('attempt')->plural(5 - $i).' left.');
    }

    secVerifyOtp($this, $flow, '9876543210', $wrong)->assertStatus(422)
        ->assertJsonPath('message', 'Too many incorrect attempts. Please request a new OTP.');

    /* The correct code no longer works — a new OTP is required. */
    secVerifyOtp($this, $flow, '9876543210', $otp)->assertStatus(422);

    secSendOtp($this, $flow, '9876543210')->assertOk();
    secVerifyOtp($this, $flow, '9876543210', $this->sms[1][1])->assertOk();
})->with(['contact', 'checkout']);

it('makes resend kill the previous code', function () {
    secSendOtp($this, 'contact', '9876543210')->assertOk();
    secSendOtp($this, 'contact', '9876543210')->assertOk();

    [$first, $second] = [$this->sms[0][1], $this->sms[1][1]];

    if ($first !== $second) {
        secVerifyOtp($this, 'contact', '9876543210', $first)->assertStatus(422);
    }

    secVerifyOtp($this, 'contact', '9876543210', $second)->assertOk();
});

it('accepts an OTP only once', function () {
    secSendOtp($this, 'contact', '9876543210')->assertOk();
    $otp = $this->sms[0][1];

    secVerifyOtp($this, 'contact', '9876543210', $otp)->assertOk();
    secVerifyOtp($this, 'contact', '9876543210', $otp)->assertStatus(422);
});

it('refuses to verify a code against a different number than it was sent to', function () {
    secSendOtp($this, 'contact', '9876543210')->assertOk();

    secVerifyOtp($this, 'contact', '9123456780', $this->sms[0][1])->assertStatus(422)
        ->assertJsonPath('message', fn ($m) => str_contains($m, 'doesn’t match'));
});

it('counts concurrent wrong guesses atomically and locks after five', function () {
    secSendOtp($this, 'contact', '9876543210')->assertOk();
    $otp = $this->sms[0][1];
    $wrong = $otp === '000000' ? '111111' : '000000';
    $challenge = session('contact_otp_challenge');

    $results = secRunConcurrently(12, ['verify', $challenge, '9876543210', $wrong], $this->prefix);
    $counts = array_count_values($results);

    /* Exactly 4 "wrong, N left" + 1 lock; every later racer sees it gone. */
    expect($counts['wrong'] ?? 0)->toBe(4)
        ->and(($counts['locked'] ?? 0) + ($counts['expired'] ?? 0))->toBe(8)
        ->and($counts['verified'] ?? 0)->toBe(0);

    secVerifyOtp($this, 'contact', '9876543210', $otp)->assertStatus(422);
});

it('limits verify attempts per IP independently of sending', function () {
    config(['otp_security.verify.per_ip_window_max' => 3]);

    secSendOtp($this, 'contact', '9876543210')->assertOk();

    foreach (range(1, 3) as $i) {
        secVerifyOtp($this, 'contact', '9876543210', '000001')->assertStatus(422);
    }

    secVerifyOtp($this, 'contact', '9876543210', '000001')->assertStatus(429);

    /* Verify pressure did not eat the send budget. */
    secSendOtp($this, 'contact', '9876543210')->assertOk();
});

/* ── Checkout regression ─────────────────────────────────────────────── */

it('keeps the checkout send → verify → session flow working', function () {
    secSendOtp($this, 'checkout', '9811122233')->assertOk();

    secVerifyOtp($this, 'checkout', '9811122233', $this->sms[0][1])
        ->assertOk()
        ->assertJsonStructure(['message', 'customer_id', 'is_new_customer']);

    expect(session('checkout_phone_verified'))->toBeTrue()
        ->and(session('checkout_otp_phone'))->toBe('9811122233')
        ->and(session('verified_phone'))->toBe('9811122233')
        ->and(Customer::find(session('checkout_customer_id'))->phone)->toBe('9811122233');
});

it('auto-verifies a logged-in customer’s own number with no SMS and no captcha', function () {
    $customer = Customer::factory()->create(['phone' => '+91 98111 22244']);

    $this->actingAs($customer, 'customer')
        ->postJson('/api/checkout/otp/send', ['phone' => '9811122244'])
        ->assertOk()
        ->assertJson(['verified' => true]);

    expect(session('checkout_phone_verified'))->toBeTrue();
    expect($this->sms)->toHaveCount(0);
    Http::assertNothingSent();
});

it('reports an SMS provider failure without marking the phone verified', function () {
    $this->sms = secFakeSms(succeeds: false);

    secSendOtp($this, 'checkout', '9876543210')->assertStatus(502);

    expect(session('checkout_phone_verified'))->toBeFalse();
});

/* ── Login OTP (flow "login") ────────────────────────────────────────── */

function secLoginCustomer(string $phone = '9822233344', array $attrs = []): Customer
{
    return Customer::factory()->create(array_merge([
        'phone'      => $phone,
        'status'     => 1,
        'channel_id' => core()->getCurrentChannel()->id,
    ], $attrs));
}

function secLoginSend($test, string $phone, string $ip = '10.0.5.1')
{
    /* The sign-in form's token carries the generic "submit" action. */
    secFakeGoogle(['action' => 'submit']);

    $response = $test->withServerVariables(['REMOTE_ADDR' => $ip])
        ->post('/customer/otp/send', ['phone' => $phone, 'recaptcha_token' => secToken()]);

    secFakeGoogle();

    return $response;
}

it('logs a customer in with the login OTP and never stores the code on the customer row', function () {
    $customer = secLoginCustomer();

    secLoginSend($this, '9822233344')->assertRedirectToRoute('shop.customer.session.index')
        ->assertSessionHas('show_login_otp', true);

    expect($customer->fresh()->otp)->toBeNull();

    $this->post('/customer/otp/verify', ['otp' => $this->sms[0][1]])->assertRedirect();

    expect(auth()->guard('customer')->id())->toBe($customer->id);
});

it('sends no SMS for an unknown number and requires reCAPTCHA before the lookup', function () {
    secLoginSend($this, '9822233355')->assertSessionHas('error', trans('shop::app.customers.otp.no-account'));

    secFakeGoogle(['action' => 'submit', 'score' => 0.1]);
    $this->post('/customer/otp/send', ['phone' => '9822233355', 'recaptcha_token' => secToken()])
        ->assertSessionHas('error', fn ($m) => str_contains($m, 'couldn’t verify'));

    expect($this->sms)->toHaveCount(0);
});

it('locks the login code after five wrong attempts and lets resend issue a new one', function () {
    $customer = secLoginCustomer();

    secLoginSend($this, '9822233344');
    $otp = $this->sms[0][1];
    $wrong = $otp === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $i) {
        $this->post('/customer/otp/verify', ['otp' => $wrong]);
    }

    expect(session('error'))->toBe('Too many incorrect attempts. Please request a new OTP.');

    /* Locked: even the right code fails, and the phone is cleared from session. */
    $this->post('/customer/otp/verify', ['otp' => $otp]);
    expect(auth()->guard('customer')->check())->toBeFalse();

    secLoginSend($this, '9822233344');
    $this->post('/customer/otp/resend')->assertSessionHas('success');

    $this->post('/customer/otp/verify', ['otp' => $this->sms[count($this->sms) - 1][1]]);
    expect(auth()->guard('customer')->id())->toBe($customer->id);
});

it('applies the per-phone send window to login resends', function () {
    secLoginCustomer();

    secLoginSend($this, '9822233344');
    $this->post('/customer/otp/resend')->assertSessionHas('success');
    $this->post('/customer/otp/resend')->assertSessionHas('success');
    $this->post('/customer/otp/resend')->assertSessionHas('error', fn ($m) => str_contains($m, 'Too many OTP requests'));

    expect($this->sms)->toHaveCount(3);
});

it('redirects the legacy GET /customer/otp/verify instead of erroring', function () {
    $this->get('/customer/otp/verify')->assertRedirectToRoute('shop.customer.session.index');
});

/* ── Site ceilings across all flows + alert ──────────────────────────── */

it('caps contact, login and signup together so checkout keeps its share', function () {
    config(['otp_security.send.site_daily_max' => 6, 'otp_security.send.site_daily_max_non_checkout' => 3]);

    secLoginCustomer('9822200001');

    secSendOtp($this, 'contact', '9822200002', '10.0.6.1')->assertOk();
    secLoginSend($this, '9822200001', '10.0.6.2')->assertSessionHas('show_login_otp', true);
    secSendOtp($this, 'contact', '9822200003', '10.0.6.3')->assertOk();

    /* Non-checkout group exhausted (3) — another contact OTP is refused... */
    secSendOtp($this, 'contact', '9822200004', '10.0.6.4')->assertStatus(429);

    /* ...but checkout still has the remaining 3 of the 6. */
    foreach (range(5, 7) as $i) {
        secSendOtp($this, 'checkout', "982220000{$i}", "10.0.6.{$i}")->assertOk();
    }

    secSendOtp($this, 'checkout', '9822200008', '10.0.6.8')->assertStatus(429);
});

it('emails the store once per ceiling per day when an SMS ceiling trips', function () {
    config(['otp_security.send.site_daily_max_contact' => 1]);

    secSendOtp($this, 'contact', '9822211101', '10.0.7.1')->assertOk();
    secSendOtp($this, 'contact', '9822211102', '10.0.7.2')->assertStatus(429);
    secSendOtp($this, 'contact', '9822211103', '10.0.7.3')->assertStatus(429);

    Mail::assertQueued(App\Mail\OtpCeilingAlert::class, 1);
    Mail::assertQueued(App\Mail\OtpCeilingAlert::class, fn ($m) => $m->bucket === 'site_daily_contact');
});
