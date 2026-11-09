<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Webkul\Customer\Facades\Captcha;
use Webkul\Shop\Services\SmsAlertService;

/*
 * Shared helpers for the security tests. No real SMS, mail or Google calls:
 * the SMS provider is a recording fake, Google siteverify is Http::fake()d and
 * stray HTTP is blocked outright.
 */

/** Fresh Redis namespace per test so counters never leak between tests. */
function secIsolateSecurityKeys(): string
{
    $prefix = 'uf:test:'.Str::random(10).':';

    config(['otp_security.key_prefix' => $prefix]);

    return $prefix;
}

/** Recording SMS fake. Returns the live list of [phone, otp] sent. */
function secFakeSms(bool $succeeds = true): ArrayObject
{
    $sent = new ArrayObject;

    $mock = Mockery::mock(SmsAlertService::class);
    $mock->shouldReceive('sendOtp')->andReturnUsing(function ($phone, $otp) use ($sent, $succeeds) {
        $sent[] = [$phone, $otp];

        return $succeeds;
    });

    app()->instance(SmsAlertService::class, $mock);

    return $sent;
}

/**
 * reCAPTCHA on/off with a known secret, without touching admin config. A fresh
 * partial mock each call — stacking expectations on one mock would keep the
 * first answer.
 */
function secCaptchaOn(bool $active = true): void
{
    $mock = Mockery::mock(Webkul\Customer\Captcha::class)->makePartial();
    $mock->shouldReceive('isActive')->andReturn($active);
    $mock->shouldReceive('getApiKey')->andReturn('test-secret-never-rendered');
    $mock->shouldReceive('getScoreThreshold')->andReturn(0.5);
    $mock->shouldReceive('validateResponse')->andReturn(true);

    Captcha::swap($mock);

    config(['otp_security.recaptcha.allowed_hostnames' => 'urbanflaky.in,urbanflaky.test']);
}

function secCaptchaOff(): void
{
    secCaptchaOn(false);
}

/**
 * Fake Google siteverify once per test; later calls only change the verdict
 * (Http::fake stubs stack and the first match wins, so never re-register).
 */
function secFakeGoogle(array $overrides = [], int $status = 200): void
{
    $GLOBALS['uf_google_verdict'] = [array_merge([
        'success'  => true,
        'score'    => 0.9,
        'action'   => 'otp_send',
        'hostname' => 'urbanflaky.in',
    ], $overrides), $status];

    if (app()->bound('uf.google.faked')) {
        return;
    }

    app()->instance('uf.google.faked', true);

    Http::preventStrayRequests();

    Http::fake([
        'www.google.com/recaptcha/*' => function () {
            [$body, $status] = $GLOBALS['uf_google_verdict'];

            return Http::response($status === 200 ? array_filter($body, fn ($v) => $v !== null) : 'down', $status);
        },
    ]);
}

function secToken(): string
{
    return 'tok-'.Str::random(32);
}

/** Run N copies of the concurrency worker in parallel; returns their outputs. */
function secRunConcurrently(int $workers, array $args, string $prefix): array
{
    $php = PHP_BINARY;
    $script = __DIR__.'/fixtures/concurrent_worker.php';
    $startAt = sprintf('%.3f', microtime(true) + 4);
    $env = array_merge(getenv(), ['OTP_KEY_PREFIX' => $prefix]);

    $procs = [];

    foreach (range(1, $workers) as $i) {
        $cmd = array_merge([$php, $script, $args[0], $startAt], array_slice($args, 1));
        $procs[] = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), $env);
        $out[] = $pipes;
    }

    $results = [];

    foreach ($procs as $i => $proc) {
        $results[] = trim(stream_get_contents($out[$i][1]));
        $err = trim(stream_get_contents($out[$i][2]));
        proc_close($proc);

        if ($err !== '') {
            fwrite(STDERR, "worker {$i}: {$err}\n");
        }
    }

    return $results;
}
