<?php

use App\Enums\IndianState;

it('resolves a state from its GST code', function () {
    expect(IndianState::from('27'))->toBe(IndianState::Maharashtra);
    expect(IndianState::Maharashtra->label())->toBe('Maharashtra');
});

it('formats the display name as "Name (code)"', function () {
    expect(IndianState::Maharashtra->displayName())->toBe('Maharashtra (27)');
    expect(IndianState::Delhi->displayName())->toBe('Delhi (07)');
});

it('exposes options keyed by code and sorted by name', function () {
    $options = IndianState::options();

    expect($options)->toHaveKey('27');
    expect($options['27'])->toBe('Maharashtra (27)');

    // Sorted alphabetically by state name.
    $labels = array_values($options);
    $sorted = $labels;
    sort($sorted);
    expect($labels)->toBe($sorted);
});

it('rejects unknown codes', function () {
    expect(IndianState::tryFrom('99'))->toBeNull();
});
