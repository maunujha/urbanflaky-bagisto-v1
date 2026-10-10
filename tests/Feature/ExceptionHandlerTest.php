<?php

use App\Exceptions\Handler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Production mode: core only installs its renderers when debug is off.
    config(['app.debug' => false]);

    Route::middleware(['web', 'throttle:2,1'])->post('_test/throttled', fn () => response()->json(['ok' => true]));
    Route::middleware('web')->post('_test/response-exception', fn () => throw new HttpResponseException(response()->json(['custom' => true], 418)));
    Route::middleware('web')->post('_test/server-error', fn () => throw new RuntimeException('boom'));
    Route::middleware('web')->post('_test/not-found', fn () => abort(404));
});

it('binds the app handler over the core handler', function () {
    expect(app(ExceptionHandler::class))->toBeInstanceOf(Handler::class);
});

it('answers a throttled JSON request with 429 and Retry-After', function () {
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

    $this->postJson('_test/throttled')->assertOk();
    $this->postJson('_test/throttled')->assertOk();

    $this->postJson('_test/throttled')
        ->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertJsonPath('error', '429 Too Many Requests');
});

it('renders the storefront error page with 429 for browser requests', function () {
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

    $this->post('_test/throttled');
    $this->post('_test/throttled');

    $this->post('_test/throttled')
        ->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertSee('429 Too Many Requests');
});

it('returns the response carried by an HttpResponseException', function () {
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

    $this->postJson('_test/response-exception')
        ->assertStatus(418)
        ->assertExactJson(['custom' => true]);
});

it('keeps core rendering for other errors', function () {
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

    $this->postJson('_test/server-error')
        ->assertStatus(500)
        ->assertJsonMissing(['message' => 'boom']);

    $this->postJson('_test/not-found')
        ->assertNotFound()
        ->assertJsonPath('error', '404 Page Not Found');
});

it('keeps 405 and 419 instead of reporting a server error', function () {
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

    Route::middleware('web')->get('_test/get-only', fn () => 'ok');
    Route::middleware('web')->post('_test/expired', fn () => abort(419));

    $this->postJson('_test/get-only')
        ->assertStatus(405)
        ->assertHeader('Allow')
        ->assertJsonPath('error', '405 Method Not Allowed');

    $this->postJson('_test/expired')
        ->assertStatus(419)
        ->assertJsonPath('error', '419 Page Expired');

    $this->post('_test/expired')
        ->assertStatus(419)
        ->assertSee('Your session expired');
});
