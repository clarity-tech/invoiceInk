<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceNumberingSeries;
use App\Models\Organization;
use App\Services\EstimateToInvoiceConverter;
use App\Services\InvoiceCalculator;
use App\Services\InvoiceNumberingService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('invoice creation uses numbering series correctly', function () {
    $organization = Organization::factory()->withLocation()->create();
    $customer = Customer::factory()->withLocation()->create();

    // Create invoice using factory with numbering service
    $invoice = Invoice::factory()->invoice()->withLocations()->create([
        'organization_id' => $organization->id,
        'customer_id' => $customer->id,
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    expect($invoice->type)->toBe('invoice');
    expect($invoice->invoice_numbering_series_id)->not()->toBeNull();
    expect($invoice->invoice_number)->toContain('INV-');

    // Check that series was created and used
    $series = InvoiceNumberingSeries::find($invoice->invoice_numbering_series_id);
    expect($series->organization_id)->toBe($organization->id);
    expect($series->is_default)->toBe(true);
    expect($series->current_number)->toBe(1);
});

test('estimate creation does not use numbering series', function () {
    $organization = Organization::factory()->withLocation()->create();
    $customer = Customer::factory()->withLocation()->create();

    $estimate = Invoice::factory()->estimate()->withLocations()->create([
        'organization_id' => $organization->id,
        'customer_id' => $customer->id,
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    expect($estimate->type)->toBe('estimate');
    expect($estimate->invoice_numbering_series_id)->toBeNull();
    expect($estimate->invoice_number)->toContain('EST-');
});

test('multiple invoices from same organization have sequential numbers', function () {
    $organization = Organization::factory()->withLocation()->create();
    $customer = Customer::factory()->withLocation()->create();

    $invoice1 = Invoice::factory()->invoice()->withLocations()->create([
        'organization_id' => $organization->id,
        'customer_id' => $customer->id,
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    $invoice2 = Invoice::factory()->invoice()->withLocations()->create([
        'organization_id' => $organization->id,
        'customer_id' => $customer->id,
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    // Both should use the same series
    expect($invoice1->invoice_numbering_series_id)->toBe($invoice2->invoice_numbering_series_id);

    // Numbers should be sequential
    $series = InvoiceNumberingSeries::find($invoice1->invoice_numbering_series_id);
    expect($series->current_number)->toBe(2);
});

test('different organizations have independent numbering series', function () {
    $org1 = Organization::factory()->withLocation()->create();
    $org2 = Organization::factory()->withLocation()->create();
    $customer = Customer::factory()->withLocation()->create();

    $invoice1 = Invoice::factory()->invoice()->withLocations()->create([
        'organization_id' => $org1->id,
        'customer_id' => $customer->id,
        'organization_location_id' => $org1->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    $invoice2 = Invoice::factory()->invoice()->withLocations()->create([
        'organization_id' => $org2->id,
        'customer_id' => $customer->id,
        'organization_location_id' => $org2->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    // Should use different series
    expect($invoice1->invoice_numbering_series_id)->not()->toBe($invoice2->invoice_numbering_series_id);

    // Both should have sequence number 1
    $series1 = InvoiceNumberingSeries::find($invoice1->invoice_numbering_series_id);
    $series2 = InvoiceNumberingSeries::find($invoice2->invoice_numbering_series_id);

    expect($series1->current_number)->toBe(1);
    expect($series2->current_number)->toBe(1);
    expect($series1->organization_id)->toBe($org1->id);
    expect($series2->organization_id)->toBe($org2->id);
});

test('estimate to invoice converter uses numbering series', function () {
    $organization = Organization::factory()->withLocation()->create();
    $customer = Customer::factory()->withLocation()->create();

    $estimate = Invoice::factory()->estimate()->create([
        'organization_id' => $organization->id,
        'customer_id' => $customer->id,
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
        'invoice_number' => 'EST-2025-001',
        'invoice_numbering_series_id' => null,
    ]);

    $converter = new EstimateToInvoiceConverter(
        new InvoiceCalculator,
        new InvoiceNumberingService
    );

    $invoice = $converter->convert($estimate);

    expect($invoice->type)->toBe('invoice');
    expect($invoice->invoice_numbering_series_id)->not()->toBeNull();
    expect($invoice->invoice_number)->not()->toBe('EST-2025-001');
    expect($invoice->invoice_number)->toContain('INV-');

    // Check that series was used
    $series = InvoiceNumberingSeries::find($invoice->invoice_numbering_series_id);
    expect($series->organization_id)->toBe($organization->id);
    expect($series->current_number)->toBe(1);
});

test('invoice number uniqueness constraint works at organization level', function () {
    $org1 = Organization::factory()->withLocation()->create();
    $org2 = Organization::factory()->withLocation()->create();
    $customer = Customer::factory()->withLocation()->create();

    // Create invoice in org1
    Invoice::factory()->create([
        'organization_id' => $org1->id,
        'customer_id' => $customer->id,
        'organization_location_id' => $org1->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
        'invoice_number' => 'INV-2025-001',
        'type' => 'invoice',
    ]);

    // Should be able to create same invoice number in org2
    $invoice2 = Invoice::factory()->create([
        'organization_id' => $org2->id,
        'customer_id' => $customer->id,
        'organization_location_id' => $org2->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
        'invoice_number' => 'INV-2025-001',
        'type' => 'invoice',
    ]);

    expect($invoice2->invoice_number)->toBe('INV-2025-001');
    expect($invoice2->organization_id)->toBe($org2->id);
});

test('cannot create duplicate invoice numbers within same organization', function () {
    $organization = Organization::factory()->withLocation()->create();
    $customer = Customer::factory()->withLocation()->create();

    // Create first invoice
    Invoice::factory()->create([
        'organization_id' => $organization->id,
        'customer_id' => $customer->id,
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
        'invoice_number' => 'INV-2025-001',
        'type' => 'invoice',
    ]);

    // Try to create duplicate - should fail
    expect(function () use ($organization, $customer) {
        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'organization_location_id' => $organization->primaryLocation->id,
            'customer_location_id' => $customer->primaryLocation->id,
            'invoice_number' => 'INV-2025-001',
            'type' => 'invoice',
        ]);
    })->toThrow(QueryException::class);
});

test('can create invoice and estimate with same number in same organization', function () {
    $organization = Organization::factory()->withLocation()->create();
    $customer = Customer::factory()->withLocation()->create();

    // Create invoice
    $invoice = Invoice::factory()->create([
        'organization_id' => $organization->id,
        'customer_id' => $customer->id,
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
        'invoice_number' => 'INV-2025-001',
        'type' => 'invoice',
    ]);

    // Create estimate with same number - should work
    $estimate = Invoice::factory()->create([
        'organization_id' => $organization->id,
        'customer_id' => $customer->id,
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
        'invoice_number' => 'INV-2025-001',
        'type' => 'estimate',
    ]);

    expect($invoice->invoice_number)->toBe('INV-2025-001');
    expect($estimate->invoice_number)->toBe('INV-2025-001');
    expect($invoice->type)->toBe('invoice');
    expect($estimate->type)->toBe('estimate');
});
