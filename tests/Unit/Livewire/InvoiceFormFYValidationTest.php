<?php

use App\Enums\ResetFrequency;
use App\Livewire\InvoiceForm;
use App\Models\InvoiceNumberingSeries;
use App\Models\Organization;
use Livewire\Livewire;

test('invoice form shows error when using FY series without proper setup', function () {
    // Create organization without financial year setup
    $organization = createOrganizationWithLocation([
        'financial_year_type' => null,
        'country_code' => null,
    ]);

    $customer = createCustomerWithLocation([], [], $organization);

    // Create an invalid FY series
    $series = InvoiceNumberingSeries::create([
        'organization_id' => $organization->id,
        'name' => 'Invalid FY Series',
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}-{FY}-{SEQUENCE:4}',
        'current_number' => 0,
        'reset_frequency' => ResetFrequency::FINANCIAL_YEAR,
        'is_active' => true,
        'is_default' => false,
    ]);

    $this->actingAs($organization->owner);

    Livewire::test(InvoiceForm::class)
        ->set('type', 'invoice')
        ->set('organization_id', $organization->id)
        ->set('customer_id', $customer->id)
        ->set('organization_location_id', $organization->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('invoice_numbering_series_id', $series->id)
        ->set('items.0.description', 'Test Item')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 100.00)
        ->call('save')
        ->assertHasErrors('invoice_numbering_series_id');
});

test('invoice form creates invoice successfully with proper FY setup', function () {
    // Create organization with proper financial year setup
    $organization = createOrganizationWithLocation([
        'financial_year_type' => \App\Enums\FinancialYearType::APRIL_MARCH,
        'country_code' => \App\Enums\Country::IN,
    ]);

    $customer = createCustomerWithLocation([], [], $organization);

    // Create a valid FY series
    $series = InvoiceNumberingSeries::create([
        'organization_id' => $organization->id,
        'name' => 'Valid FY Series',
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}-{FY}-{SEQUENCE:4}',
        'current_number' => 0,
        'reset_frequency' => ResetFrequency::FINANCIAL_YEAR,
        'is_active' => true,
        'is_default' => false,
    ]);

    $this->actingAs($organization->owner);

    expect(\App\Models\Invoice::count())->toBe(0);

    Livewire::test(InvoiceForm::class)
        ->set('type', 'invoice')
        ->set('organization_id', $organization->id)
        ->set('customer_id', $customer->id)
        ->set('organization_location_id', $organization->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('invoice_numbering_series_id', $series->id)
        ->set('items.0.description', 'Test Item')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 100.00)
        ->call('save')
        ->assertSessionHas('message');

    expect(\App\Models\Invoice::count())->toBe(1);

    $invoice = \App\Models\Invoice::first();
    expect($invoice->invoice_number)->toMatch('/^INV-\d{4}-\d{2}-\d{4}$/');
});

test('invoice form works with default series when no specific series selected', function () {
    // Create organization with proper setup
    $organization = createOrganizationWithLocation([
        'financial_year_type' => \App\Enums\FinancialYearType::APRIL_MARCH,
        'country_code' => \App\Enums\Country::IN,
    ]);

    $customer = createCustomerWithLocation([], [], $organization);

    $this->actingAs($organization->owner);

    expect(\App\Models\Invoice::count())->toBe(0);

    Livewire::test(InvoiceForm::class)
        ->set('type', 'invoice')
        ->set('organization_id', $organization->id)
        ->set('customer_id', $customer->id)
        ->set('organization_location_id', $organization->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        // Don't set invoice_numbering_series_id - should use default fallback
        ->set('items.0.description', 'Test Item')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 100.00)
        ->call('save')
        ->assertSessionHas('message');

    expect(\App\Models\Invoice::count())->toBe(1);
    
    $invoice = \App\Models\Invoice::first();
    expect($invoice->invoice_number)->toMatch('/^INV-\d{4}-\d{2}-\d{4}$/');
});