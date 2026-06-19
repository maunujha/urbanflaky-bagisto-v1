<?php

declare(strict_types=1);

use Gabha\Search\Services\NaturalLanguage\QueryParser;

/*
|--------------------------------------------------------------------------
| QueryParser unit tests
|--------------------------------------------------------------------------
|
| The parser is pure, so these run without booting the framework or touching
| the database. We feed it the REAL `natural_language` config block so the tests
| double as a guard on the shipped lexicons (colours, gender keywords, price
| cues, product-type aliases).
|
*/

function nlParser(): QueryParser
{
    $config = require __DIR__.'/../../src/Config/gabha-search.php';

    return new QueryParser($config['natural_language']);
}

/**
 * Same lexicons but with colour promoted to a hard filter (the word is consumed).
 */
function nlParserColorFilter(): QueryParser
{
    $config = require __DIR__.'/../../src/Config/gabha-search.php';
    $nl = $config['natural_language'];
    $nl['color_as_filter'] = true;

    return new QueryParser($nl);
}

it('parses "black oversized tee under 300" (colour kept as full-text by default)', function () {
    $intent = nlParser()->parse('black oversized tee under 300');

    expect($intent->color)->toBe('Black')
        ->and($intent->priceMax)->toBe(300.0)
        ->and($intent->priceMin)->toBeNull()
        ->and($intent->gender)->toBeNull()
        ->and($intent->productType)->toBe('tshirt')
        ->and($intent->cleanQuery)->toBe('black oversized tee');
});

it('strips the colour word when colour drives a hard filter', function () {
    $intent = nlParserColorFilter()->parse('black oversized tee under 300');

    expect($intent->color)->toBe('Black')
        ->and($intent->priceMax)->toBe(300.0)
        ->and($intent->cleanQuery)->toBe('oversized tee');
});

it('keeps the vibe word "dark" in the query and does not force a colour filter', function () {
    $intent = nlParser()->parse('dark aesthetic hoodie');

    expect($intent->color)->toBeNull()
        ->and($intent->priceMin)->toBeNull()
        ->and($intent->priceMax)->toBeNull()
        ->and($intent->productType)->toBe('hoodie')
        ->and($intent->cleanQuery)->toBe('dark aesthetic hoodie');
});

it('detects a hyphenated product type', function () {
    $intent = nlParser()->parse('dark aesthetic t-shirt');

    expect($intent->productType)->toBe('tshirt')
        ->and($intent->color)->toBeNull()
        ->and($intent->cleanQuery)->toBe('dark aesthetic t-shirt');
});

it('parses "oversized tshirt for men" into a gender intent', function () {
    $intent = nlParser()->parse('oversized tshirt for men');

    expect($intent->gender)->toBe('men')
        ->and($intent->productType)->toBe('tshirt')
        ->and($intent->color)->toBeNull()
        ->and($intent->cleanQuery)->toBe('oversized tshirt');
});

it('parses "black streetwear under 500"', function () {
    $intent = nlParser()->parse('black streetwear under 500');

    expect($intent->color)->toBe('Black')
        ->and($intent->priceMax)->toBe(500.0)
        ->and($intent->productType)->toBeNull()
        ->and($intent->cleanQuery)->toBe('black streetwear');
});

it('parses a between-range', function () {
    $intent = nlParser()->parse('polo between 300 and 500');

    expect($intent->priceMin)->toBe(300.0)
        ->and($intent->priceMax)->toBe(500.0)
        ->and($intent->productType)->toBe('polo')
        ->and($intent->cleanQuery)->toBe('polo');
});

it('parses a hyphen range and a minimum budget', function () {
    expect(nlParser()->parse('shirt 300-500')->priceMin)->toBe(300.0);
    expect(nlParser()->parse('shirt 300-500')->priceMax)->toBe(500.0);

    $min = nlParser()->parse('tshirt over 200');
    expect($min->priceMin)->toBe(200.0)
        ->and($min->priceMax)->toBeNull();
});

it('treats a bare currency amount as a budget ceiling', function () {
    $intent = nlParser()->parse('₹300 polo');

    expect($intent->priceMax)->toBe(300.0)
        ->and($intent->productType)->toBe('polo')
        ->and($intent->cleanQuery)->toBe('polo');
});

it('does not mistake a non-price number for a budget', function () {
    $intent = nlParser()->parse('100% cotton tshirt');

    expect($intent->priceMin)->toBeNull()
        ->and($intent->priceMax)->toBeNull()
        ->and($intent->productType)->toBe('tshirt');
});

it('collapses possessive gender ("women\'s kurta")', function () {
    $intent = nlParser()->parse("women's kurta");

    expect($intent->gender)->toBe('women')
        ->and($intent->productType)->toBe('kurta');
});

it('canonicalises colour aliases', function () {
    expect(nlParser()->parse('gray tee')->color)->toBe('Grey');
    expect(nlParser()->parse('navy polo')->color)->toBe('Blue');
});

it('returns the query untouched when there is no intent', function () {
    $intent = nlParser()->parse('urbanflaky premium');

    expect($intent->hasIntent())->toBeFalse()
        ->and($intent->cleanQuery)->toBe('urbanflaky premium');
});
