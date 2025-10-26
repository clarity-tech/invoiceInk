<?php

use App\Enums\ResetFrequency;
use App\Livewire\InvoiceWizard;
use App\Models\InvoiceNumberingSeries;
use App\Models\Organization;
use Livewire\Livewire;

test('invoice wizard shows error when using FY series without proper setup', function () {
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

    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('type', 'invoice')
        ->set('organization_id', $organization->id)
        ->set('customer_id', $customer->id)
        ->set('organization_location_id', $organization->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('invoice_numbering_series_id', $series->id)
        ->set('items.0.description', 'Test Item')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 100)
        ->call('save')
        ->assertHasErrors(['invoice_numbering_series_id'])
        ->assertSee('Organization must have financial year configuration');
});

test('invoice wizard creates invoice successfully with proper FY setup', function () {
    // Create organization with proper financial year setup
    $organization = createOrganizationWithLocation([
        'country_code' => 'IN',
        'financial_year_type' => 'april_march',
        'financial_year_start_month' => 4,
        'financial_year_start_day' => 1,
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

    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('type', 'invoice')
        ->set('organization_id', $organization->id)
        ->set('customer_id', $customer->id)
        ->set('organization_location_id', $organization->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('invoice_numbering_series_id', $series->id)
        ->set('items.0.description', 'Test Item')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 100)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Invoice created successfully!');
});

test('invoice wizard works with default series when no specific series selected', function () {
    // Create organization without financial year setup - should use regular format
    $organization = createOrganizationWithLocation([
        'financial_year_type' => null,
        'country_code' => null,
    ]);

    $customer = createCustomerWithLocation([], [], $organization);

    $this->actingAs($organization->owner);

    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('type', 'invoice')
        ->set('organization_id', $organization->id)
        ->set('customer_id', $customer->id)
        ->set('organization_location_id', $organization->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('items.0.description', 'Test Item')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 100)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Invoice created successfully!');
});
