<?php

use App\Enums\Country;
use App\Enums\ResetFrequency;
use App\Models\InvoiceNumberingSeries;
use App\Models\Organization;
use App\Services\InvoiceNumberingService;
use Carbon\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2024-06-15')); // Mid-year date for testing
});

test('financial year tokens are replaced correctly for indian organization', function () {
    // Create organization with Indian financial year (April-March)
    $organization = Organization::factory()->withFinancialYear(Country::IN)->create();

    // Create numbering series with FY tokens
    $series = InvoiceNumberingSeries::create([
        'organization_id' => $organization->id,
        'name' => 'FY Test Series',
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}-{FY}-{SEQUENCE:4}',
        'current_number' => 0,
        'reset_frequency' => ResetFrequency::FINANCIAL_YEAR,
        'is_active' => true,
        'is_default' => true,
    ]);

    $service = new InvoiceNumberingService;
    $result = $service->generateInvoiceNumber($organization);

    // For June 2024, FY should be 2024-25 (April 2024 to March 2025)
    expect($result['invoice_number'])->toBe('INV-2024-25-0001');
});

test('financial year tokens work for different patterns', function () {
    $organization = Organization::factory()->withFinancialYear(Country::IN)->create();

    $testCases = [
        ['{PREFIX}-{FY}-{SEQUENCE:4}', 'INV-2024-25-0001'],
        ['{PREFIX}-{FY_START}-{FY_END}-{SEQUENCE}', 'INV-2024-2025-1'],
        ['FY{FY_START}/{FY_END}-{SEQUENCE:3}', 'FY2024/2025-001'],
    ];

    foreach ($testCases as [$pattern, $expected]) {
        $series = InvoiceNumberingSeries::create([
            'organization_id' => $organization->id,
            'name' => 'Test Series '.uniqid(), // Unique name to avoid conflicts
            'prefix' => 'INV',
            'format_pattern' => $pattern,
            'current_number' => 0,
            'reset_frequency' => ResetFrequency::FINANCIAL_YEAR,
            'is_active' => true,
            'is_default' => false,
        ]);

        $service = new InvoiceNumberingService;
        $result = $service->generateInvoiceNumber($organization, null, $series->name);
        expect($result['invoice_number'])->toBe($expected);
    }
});

test('financial year tokens work across financial year boundary', function () {
    // Test at the end of FY (March)
    $this->travelTo(Carbon::parse('2024-03-31'));

    $organization = Organization::factory()->withFinancialYear(Country::IN)->create();

    $series = InvoiceNumberingSeries::create([
        'organization_id' => $organization->id,
        'name' => 'FY Test Series',
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}-{FY}-{SEQUENCE:4}',
        'current_number' => 5,
        'reset_frequency' => ResetFrequency::FINANCIAL_YEAR,
        'is_active' => true,
        'is_default' => true,
        'last_reset_at' => Carbon::parse('2023-04-01'), // Set reset to start of current FY
    ]);

    $service = new InvoiceNumberingService;
    $result = $service->generateInvoiceNumber($organization);

    // Should be FY 2023-24 (March 31, 2024 is end of FY 2023-24)
    // Since current_number starts at 5 and we're still in same FY, next should be 6
    expect($result['invoice_number'])->toBe('INV-2023-24-0006');

    // Now test at the start of new FY (April)
    $this->travelTo(Carbon::parse('2024-04-01'));

    $result = $service->generateInvoiceNumber($organization);

    // Should be FY 2024-25 and sequence should continue (auto-reset happens in getNextNumber)
    expect($result['invoice_number'])->toBe('INV-2024-25-0001');
});

test('organizations without financial year setup use regular year tokens', function () {
    $organization = Organization::factory()->create([
        'country_code' => null,
        'financial_year_type' => null,
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

    // Should use calendar year and month
    expect($result['invoice_number'])->toBe('INV-2024-06-0001');
});

test('default series creation uses financial year pattern when organization has fy setup', function () {
    $organization = Organization::factory()->withFinancialYear(Country::IN)->create();

    $service = new InvoiceNumberingService;
    $series = $service->createDefaultSeries($organization);

    expect($series->format_pattern)->toBe('{PREFIX}-{FY}-{SEQUENCE:4}');
    expect($series->reset_frequency)->toBe(ResetFrequency::FINANCIAL_YEAR);
});
