<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Webkul\Shop\Mail\Customer\SubscriptionNotification;

require_once __DIR__.'/helpers.php';

beforeEach(function () {
    secIsolateSecurityKeys();
    secCaptchaOn();
    secFakeGoogle(['action' => 'newsletter']);
    Mail::fake();

    config(['core.customer.settings.newsletter.subscription' => true]);
});

function secSubscribe($test, array $data, string $ip = '10.1.0.1')
{
    return $test->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson('/subscription', $data + ['recaptcha_token' => secToken()]);
}

function secRowsFor(string $email): int
{
    return DB::table('subscribers_list')->where('email', $email)->count();
}

it('subscribes a valid email, normalised, and sends one welcome mail', function () {
    secSubscribe($this, ['email' => '  New.Reader@Example.com '])->assertOk()->assertJson(['success' => true]);

    expect(secRowsFor('new.reader@example.com'))->toBe(1);

    $row = DB::table('subscribers_list')->where('email', 'new.reader@example.com')->first();
    expect(strlen($row->token))->toBe(40)->and(ctype_alnum($row->token))->toBeTrue();

    Mail::assertQueued(SubscriptionNotification::class, 1);
});

it('silently swallows honeypot submissions', function () {
    secSubscribe($this, ['email' => 'bot@example.com', 'website' => 'http://spam.example'])
        ->assertOk()->assertJson(['success' => true]);

    expect(secRowsFor('bot@example.com'))->toBe(0);
    Mail::assertNothingQueued();
    Http::assertNothingSent();
});

it('rejects invalid emails server-side', function (string $email) {
    secSubscribe($this, ['email' => $email])->assertStatus(422);

    Mail::assertNothingQueued();
})->with(['not-an-email', 'a@b', 'x@example.com'.str_repeat('m', 160), '<script>@x.com', '']);

it('tells an existing subscriber they are already subscribed, without writing or mailing', function () {
    secSubscribe($this, ['email' => 'dup@example.com'])->assertOk()
        ->assertJsonPath('message', trans('shop::app.subscription.subscribe-success'));

    secSubscribe($this, ['email' => 'DUP@example.com'])->assertOk()
        ->assertJson(['success' => true, 'already' => true, 'message' => trans('shop::app.subscription.already')]);

    expect(secRowsFor('dup@example.com'))->toBe(1);
    Mail::assertQueued(SubscriptionNotification::class, 1);
});

it('refuses a signup while another request for the same email holds the lock', function () {
    $tag = 'em_'.substr(hash_hmac('sha256', 'race@example.com', (string) config('app.key')), 0, 12);
    $lock = Cache::lock('nl:lock:'.$tag, 10);
    $lock->get();

    secSubscribe($this, ['email' => 'race@example.com'])->assertStatus(429);

    $lock->release();

    expect(secRowsFor('race@example.com'))->toBe(0);
});

it('rejects bad reCAPTCHA verdicts', function (array $google) {
    secFakeGoogle($google + ['action' => 'newsletter']);

    secSubscribe($this, ['email' => 'human@example.com'])->assertStatus(422);

    expect(secRowsFor('human@example.com'))->toBe(0);
})->with([
    'invalid'      => [['success' => false]],
    'low score'    => [['score' => 0.1]],
    'otp action'   => [['action' => 'otp_send']],
    'bad hostname' => [['hostname' => 'evil.example']],
]);

it('rejects a missing token', function () {
    $this->postJson('/subscription', ['email' => 'human@example.com'])->assertStatus(422);

    Http::assertNothingSent();
});

it('caps signups per IP per minute and per day', function () {
    foreach (range(1, 5) as $i) {
        secSubscribe($this, ['email' => "m{$i}@example.com"])->assertOk();
    }

    secSubscribe($this, ['email' => 'm6@example.com'])->assertStatus(429);

    config(['otp_security.newsletter.per_ip_minute_max' => 100, 'otp_security.newsletter.per_ip_daily_max' => 7]);

    secSubscribe($this, ['email' => 'm7@example.com'])->assertOk();
    secSubscribe($this, ['email' => 'm8@example.com'])->assertOk();
    secSubscribe($this, ['email' => 'm9@example.com'])->assertStatus(429);

    /* Rate-limited requests never reach Google. */
    Http::assertSentCount(7);
});

it('caps welcome mails to one inbox per day, whoever triggers them', function () {
    foreach (range(1, 3) as $i) {
        secSubscribe($this, ['email' => 'victim@example.com'], "10.1.1.{$i}")->assertOk();
        DB::table('subscribers_list')->where('email', 'victim@example.com')->delete();
    }

    secSubscribe($this, ['email' => 'victim@example.com'], '10.1.1.9')->assertStatus(429);

    Mail::assertQueued(SubscriptionNotification::class, 3);
});

it('fails closed with a friendly error when Redis is unavailable', function () {
    config(['otp_security.redis_connection' => 'missing-connection']);

    secSubscribe($this, ['email' => 'human@example.com'])->assertStatus(503)
        ->assertJsonPath('message', 'Something went wrong. Please try again shortly.');

    expect(secRowsFor('human@example.com'))->toBe(0);
});

it('never writes the raw email address to the log', function () {
    Illuminate\Support\Facades\Log::spy();

    secSubscribe($this, ['email' => 'private.person@example.com'])->assertOk();

    Illuminate\Support\Facades\Log::shouldHaveReceived('info')
        ->withArgs(fn ($msg, $ctx = []) => $msg === 'newsletter.subscribed' && ! str_contains(json_encode($ctx), 'private.person'));
});
