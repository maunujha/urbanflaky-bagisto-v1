<?php

/*
 * Child process for the concurrency tests. Boots the app, spins until a shared
 * start instant so all workers hit Redis together, performs one operation and
 * prints a one-word result.
 *
 * argv: <mode: reserve|verify> <start-unix-float> [challenge-id phone otp]
 * env : OTP_KEY_PREFIX isolates the keys of the test run.
 */

require __DIR__.'/../../../../vendor/autoload.php';

$app = require __DIR__.'/../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

/* Never reach the SMS provider from a test worker. */
app()->instance(Webkul\Shop\Services\SmsAlertService::class, Mockery::mock(Webkul\Shop\Services\SmsAlertService::class));

[$self, $mode, $startAt] = $argv;

while (microtime(true) < (float) $startAt) {
    usleep(500);
}

if ($mode === 'reserve') {
    $result = app(App\Services\Security\AtomicLimiter::class)->reserve([
        'race' => ['key' => 'race:bucket', 'limit' => 3, 'ttl' => 120],
    ]);

    echo $result['allowed'] ? 'allowed' : 'blocked';

    exit;
}

if ($mode === 'verify') {
    [, , , $challengeId, $phone, $otp] = $argv;

    session(['contact_otp_challenge' => $challengeId]);

    $result = app(App\Services\Security\OtpService::class)->verify('contact', $phone, $otp, Illuminate\Http\Request::create('/', 'POST', server: ['REMOTE_ADDR' => '10.9.9.9']));

    echo $result['ok'] ? 'verified' : (str_contains($result['message'], 'Too many') ? 'locked' : (str_contains($result['message'], 'expired') ? 'expired' : 'wrong'));
}
