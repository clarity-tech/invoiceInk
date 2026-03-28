<?php

use App\Enums\FinancialYearType;
use App\Enums\ResetFrequency;
use App\Models\InvoiceNumberingSeries;
use App\Models\Organization;
use App\Services\InvoiceNumberingService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new InvoiceNumberingService;
});

// --- Monthly reset edge cases ---

test('monthly reset triggers when month changes', function () {
    $organization = Organization::factory()->withLocation()->create();

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'current_number' => 50,
        'reset_frequency' => ResetFrequency::MONTHLY,
        'last_reset_at' => now()->subMonthNoOverflow(),
        'is_default' => true,
        'is_active' => true,
    ]);

    $result = $this->service->generateInvoiceNumber($organization, $organization->primaryLocation);

    expect($result['sequence_number'])->toBe(1);
    expect($series->fresh()->last_reset_at->isToday())->toBeTrue();
});

test('monthly reset does not trigger within same month', function () {
    $organization = Organization::factory()->withLocation()->create();

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'current_number' => 10,
        'reset_frequency' => ResetFrequency::MONTHLY,
        'last_reset_at' => now()->startOfMonth(),
        'is_default' => true,
        'is_active' => true,
    ]);

    $result = $this->service->generateInvoiceNumber($organization, $organization->primaryLocation);

    expect($result['sequence_number'])->toBe(11);
});

// --- Financial year reset edge cases ---

test('financial year reset triggers at FY boundary', function () {
    $organization = Organization::factory()->withLocation()->create([
        'financial_year_type' => FinancialYearType::APRIL_MARCH,
        'country_code' => 'IN',
    ]);

    // Last reset in March (end of previous FY), current date is in April (new FY)
    Carbon::setTestNow(Carbon::parse('2026-04-15'));

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'current_number' => 99,
        'reset_frequency' => ResetFrequency::FINANCIAL_YEAR,
        'last_reset_at' => Carbon::parse('2026-03-15'),
        'is_default' => true,
        'is_active' => true,
    ]);

    $result = $this->service->generateInvoiceNumber($organization, $organization->primaryLocation);

    expect($result['sequence_number'])->toBe(1);

    Carbon::setTestNow();
});

test('financial year reset does not trigger within same FY', function () {
    $organization = Organization::factory()->withLocation()->create([
        'financial_year_type' => FinancialYearType::APRIL_MARCH,
        'country_code' => 'IN',
    ]);

    // Both in the same FY (April-March)
    Carbon::setTestNow(Carbon::parse('2025-12-15'));

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'current_number' => 25,
        'reset_frequency' => ResetFrequency::FINANCIAL_YEAR,
        'last_reset_at' => Carbon::parse('2025-06-15'),
        'is_default' => true,
        'is_active' => true,
    ]);

    $result = $this->service->generateInvoiceNumber($organization, $organization->primaryLocation);

    expect($result['sequence_number'])->toBe(26);

    Carbon::setTestNow();
});

// --- FY validation errors ---

test('throws when FY reset frequency used without financial year type', function () {
    $organization = Organization::factory()->withLocation()->create([
        'financial_year_type' => null,
        'country_code' => 'IN',
    ]);

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'reset_frequency' => ResetFrequency::FINANCIAL_YEAR,
        'is_default' => true,
        'is_active' => true,
        'last_reset_at' => now(),
    ]);

    expect(fn () => $this->service->validateFinancialYearSetup($series, $organization))
        ->toThrow(InvalidArgumentException::class, 'financial year configuration');
});

test('throws when FY reset frequency used without country code', function () {
    $organization = Organization::factory()->withLocation()->create([
        'financial_year_type' => FinancialYearType::APRIL_MARCH,
        'country_code' => null,
    ]);

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'reset_frequency' => ResetFrequency::FINANCIAL_YEAR,
        'is_default' => true,
        'is_active' => true,
        'last_reset_at' => now(),
    ]);

    expect(fn () => $this->service->validateFinancialYearSetup($series, $organization))
        ->toThrow(InvalidArgumentException::class, 'country configuration');
});

test('throws when format pattern uses FY token without financial year type', function () {
    $organization = Organization::factory()->withLocation()->create([
        'financial_year_type' => null,
        'country_code' => 'IN',
    ]);

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'format_pattern' => '{PREFIX}-{FY}-{SEQUENCE:4}',
        'reset_frequency' => ResetFrequency::YEARLY,
        'is_default' => true,
        'is_active' => true,
        'last_reset_at' => now(),
    ]);

    expect(fn () => $this->service->validateFinancialYearSetup($series, $organization))
        ->toThrow(InvalidArgumentException::class, 'financial year configuration');
});

test('passes validation when FY setup is complete', function () {
    $organization = Organization::factory()->withLocation()->create([
        'financial_year_type' => FinancialYearType::APRIL_MARCH,
        'country_code' => 'IN',
    ]);

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'reset_frequency' => ResetFrequency::FINANCIAL_YEAR,
        'format_pattern' => '{PREFIX}-{FY}-{SEQUENCE:4}',
        'is_default' => true,
        'is_active' => true,
        'last_reset_at' => now(),
    ]);

    expect($this->service->validateFinancialYearSetup($series, $organization))->toBeTrue();
});

// --- Format token edge cases ---

test('formats DAY token correctly', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-15'));

    $organization = Organization::factory()->create();
    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}-{YEAR}-{MONTH}-{DAY}-{SEQUENCE:3}',
        'current_number' => 0,
        'reset_frequency' => ResetFrequency::NEVER,
        'last_reset_at' => now(),
    ]);

    $formatted = $this->service->previewNextNumber($series);

    expect($formatted)->toBe('INV-2026-02-15-001');

    Carbon::setTestNow();
});

test('formats MONTH:3 token as abbreviation', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-24'));

    $organization = Organization::factory()->create();
    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}-{MONTH:3}-{YEAR:2}-{SEQUENCE:4}',
        'current_number' => 0,
        'reset_frequency' => ResetFrequency::NEVER,
        'last_reset_at' => now(),
    ]);

    $formatted = $this->service->previewNextNumber($series);

    expect($formatted)->toBe('INV-Feb-26-0001');

    Carbon::setTestNow();
});

test('formats FY tokens correctly for April-March FY', function () {
    Carbon::setTestNow(Carbon::parse('2025-06-15'));

    $organization = Organization::factory()->create([
        'financial_year_type' => FinancialYearType::APRIL_MARCH,
        'country_code' => 'IN',
    ]);

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}-{FY}-{SEQUENCE:4}',
        'current_number' => 0,
        'reset_frequency' => ResetFrequency::NEVER,
        'last_reset_at' => now(),
    ]);

    $formatted = $this->service->previewNextNumber($series);

    expect($formatted)->toBe('INV-2025-26-0001');

    Carbon::setTestNow();
});

test('formats FY_START and FY_END tokens correctly', function () {
    Carbon::setTestNow(Carbon::parse('2025-06-15'));

    $organization = Organization::factory()->create([
        'financial_year_type' => FinancialYearType::APRIL_MARCH,
        'country_code' => 'IN',
    ]);

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}/{FY_START}-{FY_END}/{SEQUENCE:4}',
        'current_number' => 0,
        'reset_frequency' => ResetFrequency::NEVER,
        'last_reset_at' => now(),
    ]);

    $formatted = $this->service->previewNextNumber($series);

    expect($formatted)->toBe('INV/2025-2026/0001');

    Carbon::setTestNow();
});

test('formats sequence without padding', function () {
    $organization = Organization::factory()->create();
    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}-{SEQUENCE}',
        'current_number' => 41,
        'reset_frequency' => ResetFrequency::NEVER,
        'last_reset_at' => now(),
    ]);

    $formatted = $this->service->previewNextNumber($series);

    expect($formatted)->toBe('INV-42');
});

// --- createDefaultSeries edge cases ---

test('createDefaultSeries returns existing default when called twice', function () {
    $organization = Organization::factory()->create();

    $first = $this->service->createDefaultSeries($organization);
    $second = $this->service->createDefaultSeries($organization);

    expect($first->id)->toBe($second->id);
    expect(InvoiceNumberingSeries::forOrganization($organization->id)->default()->count())->toBe(1);
});

test('createDefaultSeries uses FY pattern when org has financial year config', function () {
    $organization = Organization::factory()->create([
        'financial_year_type' => FinancialYearType::APRIL_MARCH,
        'country_code' => 'IN',
    ]);

    $series = $this->service->createDefaultSeries($organization);

    expect($series->format_pattern)->toBe('{PREFIX}-{FY}-{SEQUENCE:4}');
    expect($series->reset_frequency)->toBe(ResetFrequency::FINANCIAL_YEAR);
});

test('createDefaultSeries uses standard pattern when org has no FY config', function () {
    $organization = Organization::factory()->create([
        'financial_year_type' => null,
        'country_code' => null,
    ]);

    $series = $this->service->createDefaultSeries($organization);

    expect($series->format_pattern)->toBe('{PREFIX}-{YEAR}-{MONTH}-{SEQUENCE:4}');
    expect($series->reset_frequency)->toBe(ResetFrequency::YEARLY);
});

// --- Never reset frequency ---

test('never reset does not reset regardless of time elapsed', function () {
    $organization = Organization::factory()->withLocation()->create();

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'current_number' => 999,
        'reset_frequency' => ResetFrequency::NEVER,
        'last_reset_at' => now()->subYears(5),
        'is_default' => true,
        'is_active' => true,
    ]);

    $result = $this->service->generateInvoiceNumber($organization, $organization->primaryLocation);

    expect($result['sequence_number'])->toBe(1000);
});

// --- Series without last_reset_at triggers reset ---

test('series with no last_reset_at triggers reset on first use', function () {
    $organization = Organization::factory()->withLocation()->create();

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'current_number' => 50,
        'reset_frequency' => ResetFrequency::YEARLY,
        'last_reset_at' => null,
        'is_default' => true,
        'is_active' => true,
    ]);

    $result = $this->service->generateInvoiceNumber($organization, $organization->primaryLocation);

    // Should reset because last_reset_at is null
    expect($result['sequence_number'])->toBe(1);
});
