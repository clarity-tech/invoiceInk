<?php

use App\Enums\ResetFrequency;
use App\Models\InvoiceNumberingSeries;
use App\Models\Location;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create invoice numbering series', function () {
    $organization = Organization::factory()->create();
    $location = Location::factory()->create();
    
    $series = InvoiceNumberingSeries::create([
        'organization_id' => $organization->id,
        'location_id' => $location->id,
        'name' => 'Test Series',
        'prefix' => 'TST',
        'format_pattern' => '{PREFIX}-{YEAR}{SEQUENCE:4}',
        'current_number' => 0,
        'reset_frequency' => ResetFrequency::YEARLY,
        'is_active' => true,
        'is_default' => false,
    ]);
    
    expect($series->name)->toBe('Test Series');
    expect($series->prefix)->toBe('TST');
    expect($series->current_number)->toBe(0);
    expect($series->is_active)->toBe(true);
    expect($series->is_default)->toBe(false);
});

test('belongs to organization', function () {
    $organization = Organization::factory()->create();
    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
    ]);
    
    expect($series->organization->id)->toBe($organization->id);
});

test('belongs to location', function () {
    $location = Location::factory()->create();
    $series = InvoiceNumberingSeries::factory()->create([
        'location_id' => $location->id,
    ]);
    
    expect($series->location->id)->toBe($location->id);
});

test('can have null location for organization-wide series', function () {
    $series = InvoiceNumberingSeries::factory()->create([
        'location_id' => null,
    ]);
    
    expect($series->location_id)->toBeNull();
    expect($series->location)->toBeNull();
});

test('scopes work correctly', function () {
    $organization = Organization::factory()->create();
    $location = Location::factory()->create();
    
    InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'is_active' => true,
        'is_default' => true,
    ]);
    
    InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
        'location_id' => $location->id,
        'is_active' => false,
        'is_default' => false,
    ]);
    
    expect(InvoiceNumberingSeries::active()->count())->toBe(1);
    expect(InvoiceNumberingSeries::default()->count())->toBe(1);
    expect(InvoiceNumberingSeries::forOrganization($organization->id)->count())->toBe(2);
    expect(InvoiceNumberingSeries::forLocation($location->id)->count())->toBe(1);
});

test('should reset method works correctly', function () {
    // Never reset
    $series = InvoiceNumberingSeries::factory()->create([
        'reset_frequency' => ResetFrequency::NEVER,
        'last_reset_at' => now()->subYear(),
    ]);
    expect($series->shouldReset())->toBe(false);
    
    // Yearly reset - should reset
    $series = InvoiceNumberingSeries::factory()->create([
        'reset_frequency' => ResetFrequency::YEARLY,
        'last_reset_at' => now()->subYear(),
    ]);
    expect($series->shouldReset())->toBe(true);
    
    // Yearly reset - should not reset
    $series = InvoiceNumberingSeries::factory()->create([
        'reset_frequency' => ResetFrequency::YEARLY,
        'last_reset_at' => now()->subMonth(),
    ]);
    expect($series->shouldReset())->toBe(false);
    
    // Monthly reset - should reset
    $series = InvoiceNumberingSeries::factory()->create([
        'reset_frequency' => ResetFrequency::MONTHLY,
        'last_reset_at' => now()->subMonth()->startOfMonth(),
    ]);
    expect($series->shouldReset())->toBe(true);
    
    // First time (no last_reset_at) - should reset
    $series = InvoiceNumberingSeries::factory()->create([
        'reset_frequency' => ResetFrequency::YEARLY,
        'last_reset_at' => null,
    ]);
    expect($series->shouldReset())->toBe(true);
});

test('get next sequence number works correctly', function () {
    $series = InvoiceNumberingSeries::factory()->create([
        'current_number' => 10,
        'reset_frequency' => ResetFrequency::NEVER,
    ]);
    
    expect($series->getNextSequenceNumber())->toBe(11);
    expect($series->current_number)->toBe(10); // Should not change until saved
});

test('get next sequence number resets when needed', function () {
    $series = InvoiceNumberingSeries::factory()->create([
        'current_number' => 10,
        'reset_frequency' => ResetFrequency::YEARLY,
        'last_reset_at' => now()->subYear(),
    ]);
    
    expect($series->getNextSequenceNumber())->toBe(1);
});

test('increment and save method works correctly', function () {
    $series = InvoiceNumberingSeries::factory()->create([
        'current_number' => 5,
        'reset_frequency' => ResetFrequency::NEVER,
    ]);
    
    $series->incrementAndSave();
    
    expect($series->fresh()->current_number)->toBe(6);
});

test('increment and save method updates reset timestamp when needed', function () {
    $series = InvoiceNumberingSeries::factory()->create([
        'current_number' => 5,
        'reset_frequency' => ResetFrequency::YEARLY,
        'last_reset_at' => now()->subYear(),
    ]);
    
    $series->incrementAndSave();
    
    $fresh = $series->fresh();
    expect($fresh->current_number)->toBe(6);
    expect($fresh->last_reset_at)->not()->toBeNull();
    expect($fresh->last_reset_at->isToday())->toBe(true);
});