<?php

use Webkul\Customer\Models\Customer;
use Webkul\Shop\Services\SmsAlertService;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

/*
 * Urbanflaky sign-up is phone-first: name + Indian mobile number, then a 4-digit SMS OTP.
 * No email or password is collected; the account is only created once the OTP verifies.
 */

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
    return test()->mock(SmsAlertService::class, function ($mock) use ($delivered) {
        $mock->shouldReceive('sendOtp')->andReturn($delivered);

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

    expect(session('signup_otp'))->toMatch('/^\d{4}$/');

    expect(Customer::query()->where('phone', $details['phone'])->exists())->toBeFalse();
});

it('returns to the form with an error when the otp sms cannot be sent', function () {
    fakeSms(delivered: false);

    post(route('shop.customers.register.store'), signupDetails())
        ->assertRedirect()
        ->assertSessionHas('error', trans('shop::app.customers.otp.send-failed'))
        ->assertSessionMissing('signup_pending');
});

it('creates the account and signs the customer in when the otp is correct', function () {
    $sms = fakeSms();

    $details = signupDetails();

    $sms->shouldReceive('sendWelcome')->once()->with($details['phone'])->andReturn(true);

    post(route('shop.customers.register.store'), $details);

    post(route('shop.customers.register.verify.phone.store'), ['otp' => session('signup_otp')])
        ->assertRedirect(route('shop.home.index'))
        ->assertSessionHas('success', trans('shop::app.customers.signup-form.success'));

    $customer = Customer::query()->where('phone', $details['phone'])->first();

    expect($customer)->not->toBeNull();
    expect($customer->first_name)->toBe($details['first_name']);
    expect((int) $customer->is_verified)->toBe(1);
    expect(auth()->guard('customer')->id())->toBe($customer->id);
});

it('does not create the account when the otp is wrong', function () {
    fakeSms();

    $details = signupDetails();

    post(route('shop.customers.register.store'), $details);

    $wrongOtp = session('signup_otp') === '1111' ? '2222' : '1111';

    post(route('shop.customers.register.verify.phone.store'), ['otp' => $wrongOtp])
        ->assertRedirectToRoute('shop.customers.register.index')
        ->assertSessionHas('error', trans('shop::app.customers.otp.invalid-otp'));

    expect(Customer::query()->where('phone', $details['phone'])->exists())->toBeFalse();
});

it('does not create the account when the otp has expired', function () {
    fakeSms();

    $details = signupDetails();

    post(route('shop.customers.register.store'), $details);

    session()->put('signup_otp_expires_at', now()->subMinute()->toDateTimeString());

    post(route('shop.customers.register.verify.phone.store'), ['otp' => session('signup_otp')])
        ->assertSessionHas('error', trans('shop::app.customers.otp.otp-expired'));

    expect(Customer::query()->where('phone', $details['phone'])->exists())->toBeFalse();
});
