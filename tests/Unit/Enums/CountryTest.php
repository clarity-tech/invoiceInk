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

test('getDefaultTaxTemplates returns a non-empty, well-formed set for every country', function () {
    foreach (Country::cases() as $country) {
        $templates = $country->getDefaultTaxTemplates();

        expect($templates)->toBeArray()->not->toBeEmpty();

        foreach ($templates as $template) {
            expect($template)
                ->toHaveKeys(['name', 'type', 'rate', 'category', 'description']);
            expect($template['name'])->toBeString()->not->toBeEmpty();
            expect($template['rate'])->toBeInt()->toBeGreaterThanOrEqual(0);
        }
    }
});

test('getDefaultTaxTemplates stores rates in basis points where 1% = 100', function () {
    $india = collect(Country::IN->getDefaultTaxTemplates());

    // GST 2.0 slabs (0/5/18/40), at the same scale as TaxTemplate::$rate.
    expect($india->firstWhere('name', 'GST 0%')['rate'])->toBe(0);
    expect($india->firstWhere('name', 'GST 5%')['rate'])->toBe(500);
    expect($india->firstWhere('name', 'GST 18%')['rate'])->toBe(1800);
    expect($india->firstWhere('name', 'GST 40%')['rate'])->toBe(4000);

    // The legacy 12% and 28% slabs are gone under GST 2.0.
    expect($india->pluck('name'))->not->toContain('GST 12%');
    expect($india->pluck('name'))->not->toContain('GST 28%');
});
