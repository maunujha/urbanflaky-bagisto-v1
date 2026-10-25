<?php

it('redirects the old Shopify Hindi home page to the home page', function (string $path) {
    $this->get($path)->assertStatus(301)->assertRedirect('/');
})->with(['/hi', '/hi/']);

it('retires the legacy /pod category listing to the same target as the Shopify POD collection', function () {
    $this->get('/pod')->assertStatus(301)->assertRedirect('/mens-t-shirts');
});

it('still strips the Hindi prefix from deeper Shopify paths', function () {
    $this->get('/hi/collections/mens')->assertStatus(301)->assertRedirect('/mens');
});
