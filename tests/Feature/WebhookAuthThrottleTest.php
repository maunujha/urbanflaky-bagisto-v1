<?php

beforeEach(function () {
    config(['shiprocket.webhook_token' => 'test-webhook-token']);
});

it('locks out an IP after repeated failed authentications', function () {
    $url = '/webhooks/tracking';

    foreach (range(1, 10) as $attempt) {
        $this->withHeader('x-api-key', 'guess-'.$attempt)->postJson($url, [])->assertUnauthorized();
    }

    $this->withHeader('x-api-key', 'guess-11')->postJson($url, [])
        ->assertStatus(429)
        ->assertHeader('Retry-After');

    // The lockout holds even if the next guess would be right.
    $this->withHeader('x-api-key', 'test-webhook-token')->postJson($url, ['channel_order_id' => ''])
        ->assertStatus(429);
});

it('never throttles authenticated deliveries, however many', function () {
    foreach (range(1, 30) as $attempt) {
        $this->withHeader('x-api-key', 'test-webhook-token')
            ->postJson('/webhooks/tracking', ['channel_order_id' => 'enter your channel order id'])
            ->assertOk();
    }
});

it('releases the lockout after the decay window', function () {
    foreach (range(1, 10) as $attempt) {
        $this->withHeader('x-api-key', 'wrong')->postJson('/webhooks/tracking', [])->assertUnauthorized();
    }

    $this->withHeader('x-api-key', 'wrong')->postJson('/webhooks/tracking', [])->assertStatus(429);

    $this->travel(11)->minutes();

    $this->withHeader('x-api-key', 'test-webhook-token')
        ->postJson('/webhooks/tracking', ['channel_order_id' => 'enter your channel order id'])
        ->assertOk();
});
