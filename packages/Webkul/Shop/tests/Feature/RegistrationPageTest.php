<?php

use Webkul\Customer\Models\Customer;
use Webkul\Shop\Services\SmsAlertService;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

/*
 * Urbanflaky sign-up is phone-first: name + Indian mobile number, then a 6-digit SMS OTP.
 * No email or password is collected; the account is only created once the OTP verifies.
 */

beforeEach(function () {
    /* OTP counters live in Redis — isolate them so runs never accumulate. */
    config(['otp_security.key_prefix' => 'uf:test:'.Illuminate\Support\Str::random(10).':']);
});

/** The last code "sent" by the SMS fake. */
function lastSignupOtp(): ?string
{
    return $GLOBALS['uf_last_signup_otp'] ?? null;
}

function signupDetails(array $overrides = []): array
{
    return array_merge([
        'first_name' => fake()->firstName(),
        'last_name'  => fake()->lastName(),
        'phone'      => '9'.fake()->numerify('#########'),
    ], $overrides);
}

function fakeSms(bool $delivered = true)
{
    $GLOBALS['uf_last_signup_otp'] = null;

    return test()->mock(SmsAlertService::class, function ($mock) use ($delivered) {
        $mock->shouldReceive('sendOtp')->andReturnUsing(function ($phone, $otp) use ($delivered) {
            $GLOBALS['uf_last_signup_otp'] = $otp;

            return $delivered;
        });

        /* Post-signup notifications (welcome / registration SMS) from listeners. */
        foreach (['sendWelcome', 'sendRegistration'] as $notification) {
            $mock->shouldReceive($notification)->andReturn(true)->byDefault();
        }
    });
}

it('returns a successful response', function () {
    get(route('shop.customers.register.index'))
        ->assertOk()
        ->assertSeeText('Create Account');
});

it('requires a name and phone number', function () {
    postJson(route('shop.customers.register.store'))
        ->assertJsonValidationErrorFor('first_name')
        ->assertJsonValidationErrorFor('last_name')
        ->assertJsonValidationErrorFor('phone')
        ->assertUnprocessable();
});

it('rejects a phone number that is not a 10-digit Indian mobile', function (string $phone) {
    postJson(route('shop.customers.register.store'), signupDetails(['phone' => $phone]))
        ->assertJsonValidationErrorFor('phone')
        ->assertUnprocessable();
})->with(['12345', '5123456789', '98765432101', 'phone-here']);

it('rejects a phone number that already has an account', function () {
    $customer = Customer::factory()->create(['phone' => '9876543210']);

    postJson(route('shop.customers.register.store'), signupDetails(['phone' => $customer->phone]))
        ->assertJsonValidationErrorFor('phone');
});

it('sends an otp and holds the account until the phone is verified', function () {
    fakeSms();

    $details = signupDetails();

    post(route('shop.customers.register.store'), $details)
        ->assertRedirectToRoute('shop.customers.register.index')
        ->assertSessionHas('show_otp_modal', true)
        ->assertSessionHas('signup_pending.phone', $details['phone']);

    expect(lastSignupOtp())->toMatch('/^\d{6}$/')
        ->and(session('register_otp_challenge'))->not->toBeNull();

    expect(Customer::query()->where('phone', $details['phone'])->exists())->toBeFalse();
});

it('returns to the form with an error when the otp sms cannot be sent', function () {
    fakeSms(delivered: false);

    post(route('shop.customers.register.store'), signupDetails())
        ->assertRedirect()
        ->assertSessionHas('error', 'We couldn’t send the OTP right now. Please try again in a moment.')
        ->assertSessionMissing('signup_pending');
});

it('creates the account and signs the customer in when the otp is correct', function () {
    $sms = fakeSms();

    $details = signupDetails();

    $sms->shouldReceive('sendWelcome')->once()->with($details['phone'])->andReturn(true);

    post(route('shop.customers.register.store'), $details);

    post(route('shop.customers.register.verify.phone.store'), ['otp' => lastSignupOtp()])
        ->assertRedirect(route('shop.home.index'))
        ->assertSessionHas('success', trans('shop::app.customers.signup-form.success'));

    $customer = Customer::query()->where('phone', $details['phone'])->first();

    expect($customer)->not->toBeNull();
    expect($customer->first_name)->toBe($details['first_name']);
    expect((int) $customer->is_verified)->toBe(1);
    expect(auth()->guard('customer')->id())->toBe($customer->id);
});

it('queues exactly one welcome email for a new account', function () {
    Illuminate\Support\Facades\Mail::fake();

    Webkul\Core\Models\CoreConfig::where('code', 'emails.general.notifications.emails.general.notifications.registration')->delete();
    Webkul\Core\Models\CoreConfig::factory()->create([
        'code'  => 'emails.general.notifications.emails.general.notifications.registration',
        'value' => '1',
    ]);

    fakeSms();

    post(route('shop.customers.register.store'), signupDetails());
    post(route('shop.customers.register.verify.phone.store'), ['otp' => lastSignupOtp()]);

    Illuminate\Support\Facades\Mail::assertQueued(Webkul\Shop\Mail\Customer\RegistrationNotification::class, 1);
});

it('does not create the account when the otp is wrong', function () {
    fakeSms();

    $details = signupDetails();

    post(route('shop.customers.register.store'), $details);

    $wrongOtp = lastSignupOtp() === '111111' ? '222222' : '111111';

    post(route('shop.customers.register.verify.phone.store'), ['otp' => $wrongOtp])
        ->assertRedirectToRoute('shop.customers.register.index')
        ->assertSessionHas('error', 'Incorrect OTP. 4 attempts left.');

    expect(Customer::query()->where('phone', $details['phone'])->exists())->toBeFalse();
});

it('does not create the account when the otp has expired', function () {
    fakeSms();

    $details = signupDetails();

    post(route('shop.customers.register.store'), $details);

    /* Simulate Redis TTL expiry: the challenge the session points to is gone. */
    session()->put('register_otp_challenge', 'expired-challenge');

    post(route('shop.customers.register.verify.phone.store'), ['otp' => lastSignupOtp()])
        ->assertSessionHas('error', 'Your OTP has expired. Please request a new one.');

    expect(Customer::query()->where('phone', $details['phone'])->exists())->toBeFalse();
});

it('locks the signup code after five wrong attempts, until a resend', function () {
    fakeSms();

    $details = signupDetails();

    post(route('shop.customers.register.store'), $details);

    $otp = lastSignupOtp();
    $wrong = $otp === '111111' ? '222222' : '111111';

    foreach (range(1, 5) as $attempt) {
        post(route('shop.customers.register.verify.phone.store'), ['otp' => $wrong]);
    }

    expect(session('error'))->toBe('Too many incorrect attempts. Please request a new OTP.');

    post(route('shop.customers.register.verify.phone.store'), ['otp' => $otp]);
    expect(Customer::query()->where('phone', $details['phone'])->exists())->toBeFalse();

    post(route('shop.customers.register.resend.phone.otp'))->assertSessionHas('success');

    post(route('shop.customers.register.verify.phone.store'), ['otp' => lastSignupOtp()])
        ->assertRedirect(route('shop.home.index'));

    expect(Customer::query()->where('phone', $details['phone'])->exists())->toBeTrue();
});

it('rejects the old 4-digit code format', function () {
    fakeSms();

    post(route('shop.customers.register.store'), signupDetails());

    postJson(route('shop.customers.register.verify.phone.store'), ['otp' => '1234'])
        ->assertJsonValidationErrorFor('otp');
});
