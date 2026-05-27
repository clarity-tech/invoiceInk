<?php

use App\Enums\Country;

test('taxLabels returns label keyed by ISO country code for every enum case', function () {
    $labels = Country::taxLabels();

    expect($labels)
        ->toBeArray()
        ->toHaveCount(count(Country::cases()));

    foreach (Country::cases() as $country) {
        expect($labels)->toHaveKey($country->value);
        expect($labels[$country->value])->toBeString()->not->toBeEmpty();
    }
});

test('taxLabels returns GSTIN for India', function () {
    expect(Country::taxLabels()['IN'])->toBe('GSTIN');
});

test('taxLabels returns country-specific names for non-Indian countries', function () {
    $labels = Country::taxLabels();

    expect($labels['GB'])->toBe('VAT Registration Number');
    expect($labels['US'])->toBe('EIN');
    expect($labels['AE'])->toBe('TRN');
    expect($labels['AU'])->toBe('ABN');
    expect($labels['CA'])->toBe('GST/HST Number');
    expect($labels['SG'])->toBe('GST Registration Number');
    expect($labels['JP'])->toBe('Invoice Registration Number');
    expect($labels['DE'])->toBe('VAT Registration Number');
});
