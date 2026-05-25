<?php

use App\Enums\ResetFrequency;
use App\Models\Invoice;
use App\Models\InvoiceNumberingSeries;
use App\Models\Location;
use App\Models\Organization;
use App\Services\InvoiceNumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new InvoiceNumberingService;
});

test('can generate invoice number with default series', function () {
    $organization = Organization::factory()->withLocation()->create();
    $location = $organization->primaryLocation;

    $result = $this->service->generateInvoiceNumber($organization, $location);

    expect($result)->toHaveKeys(['invoice_number', 'series_id', 'sequence_number']);
    expect($result['invoice_number'])->toContain('INV-');
    expect($result['sequence_number'])->toBe(1);

    // Check that default series was created
    $series = InvoiceNumberingSeries::find($result['series_id']);
    expect($series->is_default)->toBe(true);
    expect($series->organization_id)->toBe($organization->id);
});

test('can generate invoice number with existing series', function () {
    $organization = Organization::factory()->withLocation()->create();
    $location = $organization->primaryLocation;

    $existingSeries = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'current_number' => 5,
        'is_default' => true,
        'is_active' => true,
        'reset_frequency' => ResetFrequency::NEVER, // Don't reset
        'last_reset_at' => now(), // Set to prevent reset
    ]);

    $result = $this->service->generateInvoiceNumber($organization, $location);

    expect($result['series_id'])->toBe($existingSeries->id);
    expect($result['sequence_number'])->toBe(6);

    // Check that series was incremented
    expect($existingSeries->fresh()->current_number)->toBe(6);
});

test('can generate invoice number with location-specific series', function () {
    $organization = Organization::factory()->withLocation()->create();
    $location = $organization->primaryLocation;

    $locationSeries = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'location_id' => $location->id,
        'current_number' => 10,
        'is_active' => true,
        'is_default' => false, // Explicitly set to false
        'reset_frequency' => ResetFrequency::NEVER, // Don't reset
        'last_reset_at' => now(), // Set to prevent reset
    ]);

    // Verify the series was created correctly
    expect($locationSeries->location_id)->toBe($location->id);
    expect($locationSeries->organization_id)->toBe($organization->id);
    expect($locationSeries->is_active)->toBe(true);

    $result = $this->service->generateInvoiceNumber($organization, $location);

    expect($result['series_id'])->toBe($locationSeries->id);
    expect($result['sequence_number'])->toBe(11);
});

test('can generate invoice number with specific series name', function () {
    $organization = Organization::factory()->withLocation()->create();
    $location = $organization->primaryLocation;

    $namedSeries = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Special Series',
        'current_number' => 20,
        'is_active' => true,
        'reset_frequency' => ResetFrequency::NEVER, // Don't reset
        'last_reset_at' => now(), // Set to prevent reset
    ]);

    $result = $this->service->generateInvoiceNumber($organization, $location, 'Special Series');

    expect($result['series_id'])->toBe($namedSeries->id);
    expect($result['sequence_number'])->toBe(21);
});

test('throws exception when specific series name not found', function () {
    $organization = Organization::factory()->withLocation()->create();
    $location = $organization->primaryLocation;

    expect(fn () => $this->service->generateInvoiceNumber($organization, $location, 'Nonexistent Series'))
        ->toThrow(InvalidArgumentException::class, 'Numbering series \'Nonexistent Series\' not found for organization.');
});

test('validates invoice number uniqueness', function () {
    $organization = Organization::factory()->withLocation()->create();
    $location = $organization->primaryLocation;

    // Create an existing invoice with a number
    Invoice::factory()->create([
        'organization_id' => $organization->id,
        'invoice_number' => 'INV-2025-001',
        'type' => 'invoice',
    ]);

    expect($this->service->validateNumberUniqueness('INV-2025-002', $organization))->toBe(true);

    expect(fn () => $this->service->validateNumberUniqueness('INV-2025-001', $organization))
        ->toThrow(InvalidArgumentException::class, 'Invoice number \'INV-2025-001\' already exists for this organization.');
});

test('uniqueness validation ignores estimates', function () {
    $organization = Organization::factory()->withLocation()->create();

    // Create an estimate with a number
    Invoice::factory()->create([
        'organization_id' => $organization->id,
        'invoice_number' => 'INV-2025-001',
        'type' => 'estimate',
    ]);

    // Should be able to create invoice with same number
    expect($this->service->validateNumberUniqueness('INV-2025-001', $organization))->toBe(true);
});

test('can create default series', function () {
    $organization = Organization::factory()->create();

    $series = $this->service->createDefaultSeries($organization);

    expect($series->organization_id)->toBe($organization->id);
    expect($series->location_id)->toBeNull();
    expect($series->name)->toBe('Default Invoice Series');
    expect($series->prefix)->toBe('INV');
    expect($series->is_default)->toBe(true);
    expect($series->is_active)->toBe(true);
});

test('can create location series', function () {
    $organization = Organization::factory()->create();
    $location = Location::factory()->create();

    $series = $this->service->createLocationSeries($organization, $location, 'Dubai Branch', 'INV-DXB');

    expect($series->organization_id)->toBe($organization->id);
    expect($series->location_id)->toBe($location->id);
    expect($series->name)->toBe('Dubai Branch');
    expect($series->prefix)->toBe('INV-DXB');
    expect($series->is_default)->toBe(false);
    expect($series->is_active)->toBe(true);
});

test('format invoice number with default pattern', function () {
    $organization = Organization::factory()->create();
    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}-{YEAR}-{MONTH}-{SEQUENCE:4}',
    ]);

    $formatted = $this->service->previewNextNumber($series);

    expect($formatted)->toMatch('/INV-\d{4}-\d{2}-\d{4}/');
    expect($formatted)->toContain('INV-'.now()->year.'-'.now()->format('m'));
});

test('format invoice number with custom pattern', function () {
    $organization = Organization::factory()->create();
    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'prefix' => 'BILL',
        'format_pattern' => '{PREFIX}-{YEAR:2}-{MONTH}-{SEQUENCE:3}',
        'current_number' => 5,
        'reset_frequency' => ResetFrequency::NEVER, // Don't reset
        'last_reset_at' => now(), // Set to prevent reset
    ]);

    $formatted = $this->service->previewNextNumber($series);

    expect($formatted)->toMatch('/BILL-\d{2}-\d{2}-\d{3}/');
    expect($formatted)->toContain('BILL-'.now()->format('y').'-'.now()->format('m').'-006');
});

test('format invoice number with sequence padding', function () {
    $organization = Organization::factory()->create();
    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}-{SEQUENCE:6}',
        'current_number' => 42,
        'reset_frequency' => ResetFrequency::NEVER, // Don't reset
        'last_reset_at' => now(), // Set to prevent reset
    ]);

    $formatted = $this->service->previewNextNumber($series);

    expect($formatted)->toBe('INV-000043');
});

test('handles series reset correctly', function () {
    $organization = Organization::factory()->withLocation()->create();
    $location = $organization->primaryLocation;

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'current_number' => 100,
        'reset_frequency' => ResetFrequency::YEARLY,
        'last_reset_at' => now()->subYear(),
        'is_default' => true,
        'is_active' => true,
    ]);

    $result = $this->service->generateInvoiceNumber($organization, $location);

    expect($result['sequence_number'])->toBe(1);
    expect($series->fresh()->current_number)->toBe(1);
    expect($series->fresh()->last_reset_at->isToday())->toBe(true);
});

test('get series for organization returns correct series', function () {
    $organization = Organization::factory()->create();
    $location = Location::factory()->create();

    $defaultSeries = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $locationSeries = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'location_id' => $location->id,
        'is_active' => true,
        'is_default' => false,
    ]);

    $inactiveSeries = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'is_active' => false,
        'is_default' => false,
    ]);

    $series = $this->service->getSeriesForOrganization($organization);

    expect($series->count())->toBe(2);
    expect($series->first()->is_default)->toBe(true); // Default series first
    expect($series->pluck('id'))->toContain($defaultSeries->id);
    expect($series->pluck('id'))->toContain($locationSeries->id);
    expect($series->pluck('id'))->not()->toContain($inactiveSeries->id);
});

test('skips past pre-existing numbers when counter is out of sync', function () {
    $organization = Organization::factory()->withLocation()->create();
    $location = $organization->primaryLocation;

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}-{SEQUENCE:4}',
        'current_number' => 0,
        'is_default' => true,
        'is_active' => true,
        'reset_frequency' => ResetFrequency::NEVER,
        'last_reset_at' => now(),
    ]);

    // Pre-existing invoices that would collide with sequence 1, 2, 3
    foreach (['INV-0001', 'INV-0002', 'INV-0003'] as $number) {
        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'invoice_number' => $number,
            'type' => 'invoice',
        ]);
    }

    $result = $this->service->generateInvoiceNumber($organization, $location);

    expect($result['invoice_number'])->toBe('INV-0004');
    expect($result['sequence_number'])->toBe(4);
    expect($series->fresh()->current_number)->toBe(4);
});

test('does not leave counter at zero when reset triggers a collision', function () {
    $organization = Organization::factory()->withLocation()->create();
    $location = $organization->primaryLocation;

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}-{SEQUENCE:4}',
        'current_number' => 10,
        'is_default' => true,
        'is_active' => true,
        'reset_frequency' => ResetFrequency::YEARLY,
        'last_reset_at' => now()->subYear(), // forces reset
    ]);

    // Pre-existing invoice that would collide with the reset value (sequence 1)
    Invoice::factory()->create([
        'organization_id' => $organization->id,
        'invoice_number' => 'INV-0001',
        'type' => 'invoice',
    ]);

    $result = $this->service->generateInvoiceNumber($organization, $location);

    // Should walk past 0001 and land on 0002
    expect($result['invoice_number'])->toBe('INV-0002');
    expect($result['sequence_number'])->toBe(2);

    // Critically, the persisted counter must reflect the actual number used
    // and must NOT be left at 0 (the bug we are guarding against).
    $fresh = $series->fresh();
    expect($fresh->current_number)->toBe(2);
    expect($fresh->last_reset_at->isToday())->toBe(true);
});

test('concurrent number generation is thread safe', function () {
    $organization = Organization::factory()->withLocation()->create();
    $location = $organization->primaryLocation;

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'current_number' => 0,
        'is_default' => true,
        'is_active' => true,
        'reset_frequency' => ResetFrequency::NEVER, // Don't reset
        'last_reset_at' => now(), // Set to prevent reset
    ]);

    $results = [];

    // Simulate concurrent requests
    for ($i = 0; $i < 5; $i++) {
        $results[] = $this->service->generateInvoiceNumber($organization, $location);
    }

    // Check that all generated numbers are unique
    $numbers = collect($results)->pluck('sequence_number');
    expect($numbers->unique()->count())->toBe(5);
    expect($numbers->sort()->values()->toArray())->toBe([1, 2, 3, 4, 5]);

    // Check final state
    expect($series->fresh()->current_number)->toBe(5);
});
