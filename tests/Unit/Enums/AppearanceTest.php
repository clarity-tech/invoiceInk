<?php

use App\Enums\Appearance;

it('reads a stored choice', function (string $value, Appearance $expected) {
    expect(Appearance::fromCookie($value))->toBe($expected);
})->with([
    ['light', Appearance::LIGHT],
    ['dark', Appearance::DARK],
    ['system', Appearance::SYSTEM],
]);

it('follows the system when there is no usable choice', function (?string $value) {
    expect(Appearance::fromCookie($value))->toBe(Appearance::SYSTEM);
})->with([
    'missing' => null,
    'empty' => '',
    'unknown' => 'sepia',
    'wrong case' => 'DARK',
]);
