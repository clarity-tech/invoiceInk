<?php

use App\Enums\Country;

test('names returns the plain country name, without the flag options() prefixes', function () {
    $names = Country::names();

    expect($names)
        ->toBeArray()
        ->toHaveCount(count(Country::cases()));

    expect($names['AE'])->toBe('United Arab Emirates');
    expect($names['IN'])->toBe('India');

    foreach (Country::cases() as $country) {
        expect($names[$country->value])->toBe($country->name());
        expect(Country::options()[$country->value])->toContain($country->name());
    }
});

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

test('taxOptions returns a non-empty, well-formed set for every country', function () {
    foreach (Country::cases() as $country) {
        $options = $country->taxOptions();

        expect($options)->toBeArray()->not->toBeEmpty();

        foreach ($options as $option) {
            expect($option)->toHaveKeys(['key', 'name', 'rate']);
            expect($option['key'])->toBeString()->not->toBeEmpty();
            expect($option['name'])->toBeString()->not->toBeEmpty();
            expect($option['rate'])->toBeInt()->toBeGreaterThanOrEqual(0);
        }

        // Keys are the dropdown option values, so they must be unique per country.
        $keys = array_column($options, 'key');
        expect($keys)->toBe(array_values(array_unique($keys)));
    }
});

test('taxOptions stores rates in basis points where 1% = 100', function () {
    $india = collect(Country::IN->taxOptions());

    // GST 2.0 slabs (0/5/18/40), at the same scale as InvoiceItem::$tax_rate.
    expect($india->firstWhere('name', 'GST 0%')['rate'])->toBe(0);
    expect($india->firstWhere('name', 'GST 5%')['rate'])->toBe(500);
    expect($india->firstWhere('name', 'GST 18%')['rate'])->toBe(1800);
    expect($india->firstWhere('name', 'GST 40%')['rate'])->toBe(4000);

    // The legacy 12% and 28% slabs are gone under GST 2.0.
    expect($india->pluck('name'))->not->toContain('GST 12%');
    expect($india->pluck('name'))->not->toContain('GST 28%');
});

test('the standard tax rate is one the country offers', function (Country $country) {
    expect(array_column($country->taxOptions(), 'rate'))->toContain($country->standardTaxRate());
})->with(Country::cases());

test('taxOptions derives a stable slug key from the option name', function () {
    $india = collect(Country::IN->taxOptions());

    expect($india->firstWhere('name', 'GST 18%')['key'])->toBe('gst-18');
    expect($india->firstWhere('name', 'GST 0%')['key'])->toBe('gst-0');
});
