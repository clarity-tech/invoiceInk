<?php

use App\Enums\Country;
use App\Enums\FinancialYearType;
use App\Enums\ResetFrequency;
use App\Models\InvoiceNumberingSeries;
use App\Models\Organization;
use App\Services\InvoiceNumberingService;

test('throws exception when FY reset frequency used without financial year setup', function () {
    $organization = Organization::factory()->create([
        'financial_year_type' => null,
        'country_code' => null,
    ]);

    $series = InvoiceNumberingSeries::create([
        'organization_id' => $organization->id,
        'name' => 'Invalid FY Series',
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}-{YEAR}-{SEQUENCE:4}',
        'current_number' => 0,
        'reset_frequency' => ResetFrequency::FINANCIAL_YEAR, // This should fail
        'is_active' => true,
        'is_default' => true,
    ]);

    $service = new InvoiceNumberingService;

    expect(fn () => $service->generateInvoiceNumber($organization))
        ->toThrow(InvalidArgumentException::class, 'Organization must have financial year configuration');
});

test('throws exception when FY tokens used without financial year setup', function () {
    $organization = Organization::factory()->create([
        'financial_year_type' => null,
        'country_code' => null,
    ]);

    $series = InvoiceNumberingSeries::create([
        'organization_id' => $organization->id,
        'name' => 'Invalid FY Token Series',
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}-{FY}-{SEQUENCE:4}', // This should fail
        'current_number' => 0,
        'reset_frequency' => ResetFrequency::YEARLY,
        'is_active' => true,
        'is_default' => true,
    ]);

    $service = new InvoiceNumberingService;

    expect(fn () => $service->generateInvoiceNumber($organization))
        ->toThrow(InvalidArgumentException::class, 'Organization must have financial year configuration to use FY tokens');
});

test('throws exception when FY reset frequency used without country setup', function () {
    $organization = Organization::factory()->create([
        'financial_year_type' => FinancialYearType::APRIL_MARCH,
        'country_code' => null, // Missing country
    ]);

    $series = InvoiceNumberingSeries::create([
        'organization_id' => $organization->id,
        'name' => 'Invalid Country Series',
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}-{FY}-{SEQUENCE:4}',
        'current_number' => 0,
        'reset_frequency' => ResetFrequency::FINANCIAL_YEAR,
        'is_active' => true,
        'is_default' => true,
    ]);

    $service = new InvoiceNumberingService;

    expect(fn () => $service->generateInvoiceNumber($organization))
        ->toThrow(InvalidArgumentException::class, 'Organization must have country configuration');
});

test('validates successfully when organization has proper FY setup', function () {
    $organization = Organization::factory()->withFinancialYear(Country::IN)->create();

    $series = InvoiceNumberingSeries::create([
        'organization_id' => $organization->id,
        'name' => 'Valid FY Series',
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}-{FY}-{SEQUENCE:4}',
        'current_number' => 0,
        'reset_frequency' => ResetFrequency::FINANCIAL_YEAR,
        'is_active' => true,
        'is_default' => true,
    ]);

    $service = new InvoiceNumberingService;
    $result = $service->generateInvoiceNumber($organization);

    expect($result['invoice_number'])->toStartWith('INV-');
    expect($result['series_id'])->toBe($series->id);
});

test('validation passes for non-FY series without FY setup', function () {
    $organization = Organization::factory()->create([
        'financial_year_type' => null,
        'country_code' => null,
    ]);

    $series = InvoiceNumberingSeries::create([
        'organization_id' => $organization->id,
        'name' => 'Regular Series',
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}-{YEAR}-{MONTH}-{SEQUENCE:4}',
        'current_number' => 0,
        'reset_frequency' => ResetFrequency::YEARLY,
        'is_active' => true,
        'is_default' => true,
    ]);

    $service = new InvoiceNumberingService;
    $result = $service->generateInvoiceNumber($organization);

    expect($result['invoice_number'])->toStartWith('INV-');
    expect($result['series_id'])->toBe($series->id);
});

test('default series creation uses FY format only when organization has complete setup', function () {
    // Organization with partial setup (missing country)
    $orgPartial = Organization::factory()->create([
        'financial_year_type' => FinancialYearType::APRIL_MARCH,
        'country_code' => null,
    ]);

    $service = new InvoiceNumberingService;
    $seriesPartial = $service->createDefaultSeries($orgPartial);

    expect($seriesPartial->format_pattern)->toBe('{PREFIX}-{YEAR}-{MONTH}-{SEQUENCE:4}');
    expect($seriesPartial->reset_frequency)->toBe(ResetFrequency::YEARLY);

    // Organization with complete setup
    $orgComplete = Organization::factory()->withFinancialYear(Country::IN)->create();
    $seriesComplete = $service->createDefaultSeries($orgComplete);

    expect($seriesComplete->format_pattern)->toBe('{PREFIX}-{FY}-{SEQUENCE:4}');
    expect($seriesComplete->reset_frequency)->toBe(ResetFrequency::FINANCIAL_YEAR);
});
