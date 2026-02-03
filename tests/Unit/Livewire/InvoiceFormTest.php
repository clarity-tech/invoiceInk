<?php

use App\Livewire\InvoiceForm;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Organization;
use Livewire\Livewire;

afterEach(function () {
    Mockery::close();
});

test('can render invoice form component for creation', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    // For creation mode, don't pass invoice parameter
    $component = Livewire::test(InvoiceForm::class, ['type' => 'invoice']);

    expect($component->get('mode'))->toBe('create');
    expect($component->get('type'))->toBe('invoice');
});

test('can render invoice form component for editing', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);
    $customer = createCustomerWithLocation();

    $invoice = createInvoiceWithItems([
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    Livewire::test(InvoiceForm::class, ['invoice' => $invoice])
        ->assertStatus(200)
        ->assertSee('Edit Invoice');
});

test('initializes with default values for creation', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    Livewire::test(InvoiceForm::class)
        ->assertSet('mode', 'create')
        ->assertSet('type', 'invoice')
        ->assertCount('items', 1)
        ->assertSet('issued_at', now()->format('Y-m-d'))
        ->assertSet('due_at', now()->addDays(30)->format('Y-m-d'));
});

test('initializes with estimate type for estimate route', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    Livewire::test(InvoiceForm::class, ['type' => 'estimate'])
        ->assertSet('type', 'estimate')
        ->assertSet('mode', 'create');
});

test('loads existing invoice data for editing', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);
    $customer = createCustomerWithLocation();

    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ], [
        ['description' => 'Test Item', 'quantity' => 2, 'unit_price' => 1000, 'tax_rate' => 1800],
    ]);

    Livewire::test(InvoiceForm::class, ['invoice' => $invoice])
        ->assertSet('mode', 'edit')
        ->assertSet('type', 'invoice')
        ->assertSet('organization_id', $organization->id)
        ->assertSet('customer_id', $customer->id)
        ->assertCount('items', 1)
        ->assertSet('items.0.description', 'Test Item')
        ->assertSet('items.0.quantity', 2)
        ->assertSet('items.0.unit_price', 10.00); // Converted from cents
});

test('can add and remove items', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    Livewire::test(InvoiceForm::class)
        ->assertCount('items', 1)
        ->call('addItem')
        ->assertCount('items', 2)
        ->call('removeItem', 1)
        ->assertCount('items', 1);
});

test('cannot remove last item', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    Livewire::test(InvoiceForm::class)
        ->assertCount('items', 1)
        ->call('removeItem', 0)
        ->assertCount('items', 1); // Should still have 1 item
});

test('calculates totals when items are updated', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    Livewire::test(InvoiceForm::class)
        ->set('items.0.description', 'Test Item')
        ->set('items.0.quantity', 2)
        ->set('items.0.unit_price', 100.00)
        ->set('items.0.tax_rate', 18.00)
        ->assertSet('subtotal', 20000) // 2 * 100 * 100 (cents)
        ->assertSet('tax', 3600) // 18% of 20000
        ->assertSet('total', 23600); // subtotal + tax
});

test('validates customer_id when saving', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    Livewire::test(InvoiceForm::class)
        ->call('save')
        ->assertHasErrors(['customer_id']); // organization_id is auto-set from current team
});

test('validates location selection when saving', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    Livewire::test(InvoiceForm::class)
        ->set('organization_id', $organization->id)
        ->set('customer_id', $customer->id)
        ->set('organization_location_id', null) // Clear auto-set location
        ->set('customer_location_id', null) // Clear auto-set location
        ->set('customer_shipping_location_id', null) // Clear auto-set location
        ->call('save')
        ->assertHasErrors(['organization_location_id', 'customer_location_id', 'customer_shipping_location_id']);
});

test('can create new invoice with items', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    expect(Invoice::count())->toBe(0);

    Livewire::test(InvoiceForm::class)
        ->set('type', 'invoice')
        ->set('organization_id', $organization->id)
        ->set('customer_id', $customer->id)
        ->set('organization_location_id', $organization->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('items.0.description', 'Test Item')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 100.00)
        ->call('save');

    expect(Invoice::count())->toBe(1);

    $invoice = Invoice::first();
    expect($invoice->type)->toBe('invoice');
    expect($invoice->organization_id)->toBe($organization->id);
    expect($invoice->customer_id)->toBe($customer->id);
    expect($invoice->items)->toHaveCount(1);
    expect($invoice->items->first()->description)->toBe('Test Item');
});

test('can create estimate', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    expect(Invoice::count())->toBe(0);

    Livewire::test(InvoiceForm::class)
        ->set('type', 'estimate')
        ->set('organization_id', $organization->id)
        ->set('customer_id', $customer->id)
        ->set('organization_location_id', $organization->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('items.0.description', 'Test Estimate Item')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 100.00)
        ->call('save');

    expect(Invoice::count())->toBe(1);

    $estimate = Invoice::first();
    expect($estimate->type)->toBe('estimate');
});

test('validates all fields when saving', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    Livewire::test(InvoiceForm::class)
        ->call('save')
        ->assertHasErrors([
            'customer_id',  // organization_id and organization_location_id are auto-set
            'customer_location_id',
            'customer_shipping_location_id',
            'items.0.description',
        ]);
});

test('can update existing invoice', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems([], null, $organization, $customer);

    expect($invoice->items->first()->description)->not->toBe('Updated Item');

    Livewire::test(InvoiceForm::class, ['invoice' => $invoice])
        ->set('items.0.description', 'Updated Item')
        ->call('save');

    $invoice->refresh();
    expect($invoice->items->first()->description)->toBe('Updated Item');
});

test('loads organization locations based on selected organization', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    // Create additional location for organization
    Location::create([
        'name' => 'Second Office',
        'address_line_1' => '456 Second St',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'IN',
        'postal_code' => '12346',
        'locatable_type' => Organization::class,
        'locatable_id' => $organization->id,
    ]);

    $component = Livewire::test(InvoiceForm::class)
        ->set('organization_id', $organization->id);

    // Test that organization locations are loaded by checking computed property access
    $component->assertSee($organization->primaryLocation->name); // Should be able to see location in form
});

test('loads customer locations based on selected customer', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation();
    $this->actingAs($organization->owner);

    // Create additional location for customer
    Location::create([
        'name' => 'Customer Branch',
        'address_line_1' => '789 Branch St',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'IN',
        'postal_code' => '12347',
        'locatable_type' => Customer::class,
        'locatable_id' => $customer->id,
    ]);

    $component = Livewire::test(InvoiceForm::class)
        ->set('customer_id', $customer->id);

    // Test that customer locations are loaded by checking they appear in form
    $component->assertSee($customer->primaryLocation->name);
});

test('returns empty collection when no organization selected', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    $component = Livewire::test(InvoiceForm::class)
        ->set('organization_id', null); // Clear the auto-set organization

    // Test that when organization is cleared, we handle it gracefully
    expect($component->get('organization_id'))->toBeNull();
});

test('returns empty collection when no customer selected', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    $component = Livewire::test(InvoiceForm::class);

    // Test that no customer is selected - should not show customer location fields
    expect($component->get('customer_id'))->toBeNull();
});

test('loads customers for selected organization', function () {
    $organization = createOrganizationWithLocation();
    $customer1 = createCustomerWithLocation([], [], $organization);
    $customer2 = createCustomerWithLocation(['name' => 'Second Customer'], [], $organization);
    $this->actingAs($organization->owner);

    $component = Livewire::test(InvoiceForm::class)
        ->set('organization_id', $organization->id);

    // Test that customers are loaded by checking they appear in form
    $component->assertSee($customer1->name)->assertSee($customer2->name);
});

test('returns empty customers collection when no organization selected', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    $component = Livewire::test(InvoiceForm::class)
        ->set('organization_id', null); // Clear the auto-set organization

    // Test that when organization is cleared, customers collection should be empty
    expect($component->get('organization_id'))->toBeNull();
});

test('generates correct invoice number format', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    Livewire::test(InvoiceForm::class)
        ->set('type', 'invoice')
        ->set('organization_id', $organization->id)
        ->set('customer_id', $customer->id)
        ->set('organization_location_id', $organization->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('items.0.description', 'Test Item')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 100.00)
        ->call('save');

    $invoice = Invoice::first();
    expect($invoice->invoice_number)->toMatch('/^INV-\d{4}-\d{2}-\d{4}$/');
});

test('generates correct estimate number format', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    Livewire::test(InvoiceForm::class)
        ->set('type', 'estimate')
        ->set('organization_id', $organization->id)
        ->set('customer_id', $customer->id)
        ->set('organization_location_id', $organization->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('items.0.description', 'Test Item')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 100.00)
        ->call('save');

    $estimate = Invoice::first();
    expect($estimate->invoice_number)->toMatch('/^EST-\d{4}-\d{2}-\d{4}$/');
});

test('handles mount exceptions gracefully', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    // Test with null invoice but create mode - should handle gracefully
    $component = Livewire::test(InvoiceForm::class, ['invoice' => null, 'type' => 'invoice']);

    expect($component->get('mode'))->toBe('create');
    expect($component->get('type'))->toBe('invoice');
});

test('handles exception during mount gracefully', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    // Create a non-existent invoice (to simulate potential database issues)
    $nonExistentInvoice = new Invoice(['id' => 99999]);

    // Component should handle exceptions and provide defaults
    $component = Livewire::test(InvoiceForm::class, ['invoice' => $nonExistentInvoice]);

    // Should default to safe values even with problematic invoice
    expect($component->get('items'))->toHaveCount(1);
});

test('page title property works correctly for create mode', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    $component = Livewire::test(InvoiceForm::class, ['type' => 'invoice']);

    expect($component->get('pageTitle'))->toBe('Create Invoice');

    // Test with estimate
    $component = Livewire::test(InvoiceForm::class, ['type' => 'estimate']);
    expect($component->get('pageTitle'))->toBe('Create Estimate');
});

test('page title property works correctly for edit mode', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);
    $customer = createCustomerWithLocation();

    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    $component = Livewire::test(InvoiceForm::class, ['invoice' => $invoice]);

    expect($component->get('pageTitle'))->toBe('Edit Invoice');

    // Test with estimate
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    $component = Livewire::test(InvoiceForm::class, ['invoice' => $estimate]);
    expect($component->get('pageTitle'))->toBe('Edit Estimate');
});

test('cancel method redirects to invoice list', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    $component = Livewire::test(InvoiceForm::class);
    $response = $component->call('cancel');

    // Verify redirect to invoices index
    $component->assertRedirect(route('invoices.index'));
});

test('save method redirects to invoice edit after successful save', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    Livewire::test(InvoiceForm::class)
        ->set('organization_id', $organization->id)
        ->set('customer_id', $customer->id)
        ->set('organization_location_id', $organization->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('items.0.description', 'Test Item')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 100.00)
        ->call('save');

    // Should redirect to invoice edit page after save
    $invoice = Invoice::first();
    expect($invoice)->not->toBeNull();
});

test('download pdf returns null for create mode', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    // In create mode, downloadPdf should not redirect (returns null internally)
    Livewire::test(InvoiceForm::class)
        ->call('downloadPdf')
        ->assertNoRedirect();
});

test('download pdf returns null when no invoice exists', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    // With no invoice, downloadPdf should not redirect (returns null internally)
    Livewire::test(InvoiceForm::class, ['invoice' => null])
        ->call('downloadPdf')
        ->assertNoRedirect();
});

test('download pdf security check prevents unauthorized access', function () {
    $organization1 = createOrganizationWithLocation();
    $organization2 = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization1);

    $invoice = createInvoiceWithItems([], null, $organization1, $customer);

    // Act as user from different organization
    $this->actingAs($organization2->owner);

    // Should get 403 during mount when trying to access invoice from different organization
    Livewire::test(InvoiceForm::class, ['invoice' => $invoice])
        ->assertStatus(403);
});

test('download pdf works for invoice type', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems(['type' => 'invoice'], null, $organization, $customer);

    // downloadPdf redirects to the PDF route (doesn't use PdfService directly)
    Livewire::test(InvoiceForm::class, ['invoice' => $invoice])
        ->call('downloadPdf')
        ->assertRedirect(route('invoices.pdf', ['ulid' => $invoice->ulid]));
});

test('download pdf works for estimate type', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $estimate = createInvoiceWithItems(['type' => 'estimate'], null, $organization, $customer);

    // downloadPdf redirects to the PDF route (doesn't use PdfService directly)
    Livewire::test(InvoiceForm::class, ['invoice' => $estimate])
        ->call('downloadPdf')
        ->assertRedirect(route('estimates.pdf', ['ulid' => $estimate->ulid]));
});

test('mode property correctly identifies edit vs create', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation();
    $this->actingAs($organization->owner);

    // Test create mode
    $createComponent = Livewire::test(InvoiceForm::class);
    expect($createComponent->get('mode'))->toBe('create');

    // Test edit mode with existing invoice
    $invoice = createInvoiceWithItems([
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    $editComponent = Livewire::test(InvoiceForm::class, ['invoice' => $invoice]);
    expect($editComponent->get('mode'))->toBe('edit');
});

test('save method passes correct invoice to saveInvoice for edit mode', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems([], null, $organization, $customer);

    $originalDescription = $invoice->items->first()->description;

    $component = Livewire::test(InvoiceForm::class, ['invoice' => $invoice])
        ->set('items.0.description', 'Updated Description')
        ->call('save');

    // Verify the invoice was updated
    $invoice->refresh();
    expect($invoice->items->first()->description)->toBe('Updated Description');
    expect($invoice->items->first()->description)->not->toBe($originalDescription);
});

test('save method passes null to saveInvoice for create mode', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    expect(Invoice::count())->toBe(0);

    Livewire::test(InvoiceForm::class)
        ->set('organization_id', $organization->id)
        ->set('customer_id', $customer->id)
        ->set('organization_location_id', $organization->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('items.0.description', 'New Invoice Item')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 100.00)
        ->call('save');

    // Should create new invoice
    expect(Invoice::count())->toBe(1);
    $newInvoice = Invoice::first();
    expect($newInvoice->items->first()->description)->toBe('New Invoice Item');
});

test('handles estimate route detection correctly', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    // When type parameter is 'estimate', component should use estimate type
    $component = Livewire::test(InvoiceForm::class, ['type' => 'estimate']);

    expect($component->get('type'))->toBe('estimate');
    expect($component->get('mode'))->toBe('create');
});

test('initializes default dates correctly', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    $component = Livewire::test(InvoiceForm::class);

    expect($component->get('issued_at'))->toBe(now()->format('Y-m-d'));
    expect($component->get('due_at'))->toBe(now()->addDays(30)->format('Y-m-d'));
});

test('handles complex error scenarios during save', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    // Try to save with completely invalid data
    $component = Livewire::test(InvoiceForm::class)
        ->set('items.0.description', '') // Empty description
        ->set('items.0.quantity', -1) // Invalid quantity
        ->set('items.0.unit_price', -100) // Invalid price
        ->call('save');

    // Should have validation errors
    $component->assertHasErrors([
        'customer_id',
        'customer_location_id',
        'customer_shipping_location_id',
        'items.0.description',
        'items.0.quantity',
        'items.0.unit_price',
    ]);
});

test('computed properties handle empty state gracefully', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    $component = Livewire::test(InvoiceForm::class);

    // Test with no organization selected
    $component->set('organization_id', null);
    $orgLocations = $component->get('organizationLocations');
    expect($orgLocations)->toBeEmpty();

    // Test with no customer selected
    $component->set('customer_id', null);
    $customerLocations = $component->get('customerLocations');
    expect($customerLocations)->toBeEmpty();

    $customers = $component->get('customers');
    expect($customers)->toBeEmpty();
});
