<?php

use App\Livewire\InvoiceWizard;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Location;
use App\ValueObjects\EmailCollection;
use Livewire\Livewire;

test('can render invoice wizard component', function () {
    Livewire::test(InvoiceWizard::class)
        ->assertStatus(200)
        ->assertSee('Invoices');
});

test('initializes with default values on mount', function () {
    Livewire::test(InvoiceWizard::class)
        ->assertSet('type', 'invoice')
        ->assertSet('currentStep', 1)
        ->assertSet('showInvoices', true)
        ->assertCount('items', 1)
        ->assertSet('issued_at', now()->format('Y-m-d'))
        ->assertSet('due_at', now()->addDays(30)->format('Y-m-d'));
});

test('can load invoices with pagination', function () {
    $company = createCompanyWithLocation();
    $customer = createCustomerWithLocation();
    
    // Create test invoices
    for ($i = 1; $i <= 12; $i++) {
        createInvoiceWithItems([
            'type' => 'invoice',
            'invoice_number' => "INV-{$i}",
            'company_location_id' => $company->primaryLocation->id,
            'customer_location_id' => $customer->primaryLocation->id,
        ]);
    }

    Livewire::test(InvoiceWizard::class)
        ->assertSee('INV-1')
        ->assertSee('INV-10')
        ->assertDontSee('INV-11') // Should be on page 2
        ->assertSee('Next');
});

test('can show create form', function () {
    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->assertSet('showInvoices', false)
        ->assertSet('currentStep', 1)
        ->assertSet('editingId', null);
});

test('can add and remove items', function () {
    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->assertCount('items', 1)
        ->call('addItem')
        ->assertCount('items', 2)
        ->call('addItem')
        ->assertCount('items', 3)
        ->call('removeItem', 1)
        ->assertCount('items', 2);
});

test('cannot remove last item', function () {
    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->assertCount('items', 1)
        ->call('removeItem', 0)
        ->assertCount('items', 1); // Should still have 1
});

test('calculates totals when items are updated', function () {
    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('items.0.description', 'Test Service')
        ->set('items.0.quantity', 2)
        ->set('items.0.unit_price', 100) // $100 in dollars
        ->set('items.0.tax_rate', 18)
        ->call('calculateTotals')
        ->assertSet('subtotal', 20000) // $200 in cents
        ->assertSet('tax', 3600) // 18% of $200 = $36 in cents
        ->assertSet('total', 23600); // $236 in cents
});

test('can navigate between wizard steps', function () {
    $company = createCompanyWithLocation();
    $customer = createCustomerWithLocation();

    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->assertSet('currentStep', 1)
        ->set('company_id', $company->id)
        ->set('customer_id', $customer->id)
        ->set('company_location_id', $company->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->call('nextStep')
        ->assertSet('currentStep', 2)
        ->call('nextStep')
        ->assertSet('currentStep', 3)
        ->call('previousStep')
        ->assertSet('currentStep', 2)
        ->call('previousStep')
        ->assertSet('currentStep', 1);
});

test('validates step 1 when moving to next step', function () {
    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->call('nextStep')
        ->assertHasErrors([
            'company_id' => 'required',
            'customer_id' => 'required',
            'company_location_id' => 'required',
            'customer_location_id' => 'required',
        ]);
});

test('cannot go beyond step 3 or below step 1', function () {
    $company = createCompanyWithLocation();
    $customer = createCustomerWithLocation();

    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('company_id', $company->id)
        ->set('customer_id', $customer->id)
        ->set('company_location_id', $company->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->call('nextStep')
        ->call('nextStep')
        ->assertSet('currentStep', 3)
        ->call('nextStep') // Should not go beyond 3
        ->assertSet('currentStep', 3)
        ->call('previousStep')
        ->call('previousStep')
        ->assertSet('currentStep', 1)
        ->call('previousStep') // Should not go below 1
        ->assertSet('currentStep', 1);
});

test('can create new invoice with items', function () {
    $company = createCompanyWithLocation();
    $customer = createCustomerWithLocation();

    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('type', 'invoice')
        ->set('company_id', $company->id)
        ->set('customer_id', $customer->id)
        ->set('company_location_id', $company->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('issued_at', '2025-01-01')
        ->set('due_at', '2025-01-31')
        ->set('items.0.description', 'Web Development')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 1000)
        ->set('items.0.tax_rate', 18)
        ->call('addItem')
        ->set('items.1.description', 'SEO Services')
        ->set('items.1.quantity', 2)
        ->set('items.1.unit_price', 500)
        ->set('items.1.tax_rate', 18)
        ->call('save')
        ->assertSet('showInvoices', true)
        ->assertSee('Invoice created successfully!');

    $this->assertDatabaseHas('invoices', [
        'type' => 'invoice',
        'company_location_id' => $company->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
        'status' => 'draft',
    ]);

    $invoice = Invoice::where('type', 'invoice')->latest()->first();
    expect($invoice->items)->toHaveCount(2);
    expect($invoice->items->first()->description)->toBe('Web Development');
    expect($invoice->items->last()->description)->toBe('SEO Services');
});

test('can create estimate', function () {
    $company = createCompanyWithLocation();
    $customer = createCustomerWithLocation();

    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('type', 'estimate')
        ->set('company_id', $company->id)
        ->set('customer_id', $customer->id)
        ->set('company_location_id', $company->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('items.0.description', 'Project Estimate')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 5000)
        ->set('items.0.tax_rate', 18)
        ->call('save')
        ->assertSet('showInvoices', true);

    $this->assertDatabaseHas('invoices', [
        'type' => 'estimate',
        'status' => 'draft',
    ]);
});

test('validates all fields when saving', function () {
    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('items.0.description', '') // Invalid
        ->set('items.0.quantity', 0) // Invalid
        ->set('items.0.unit_price', -100) // Invalid
        ->call('save')
        ->assertHasErrors([
            'company_id' => 'required',
            'customer_id' => 'required',
            'company_location_id' => 'required',
            'customer_location_id' => 'required',
            'items.0.description' => 'required',
            'items.0.quantity' => 'min:1',
            'items.0.unit_price' => 'min:0',
        ]);
});

test('can edit existing invoice', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-EDIT',
        'issued_at' => '2025-01-01',
        'due_at' => '2025-01-31',
    ], [
        [
            'description' => 'Original Service',
            'quantity' => 1,
            'unit_price' => 5000,
            'tax_rate' => 18,
        ]
    ]);

    Livewire::test(InvoiceWizard::class)
        ->call('edit', $invoice)
        ->assertSet('showInvoices', false)
        ->assertSet('editingId', $invoice->id)
        ->assertSet('type', 'invoice')
        ->assertSet('company_id', $invoice->companyLocation->locatable_id)
        ->assertSet('customer_id', $invoice->customerLocation->locatable_id)
        ->assertSet('issued_at', '2025-01-01')
        ->assertSet('due_at', '2025-01-31')
        ->assertCount('items', 1)
        ->assertSet('items.0.description', 'Original Service')
        ->assertSet('items.0.quantity', 1)
        ->assertSet('items.0.unit_price', 50.0) // Converted from cents
        ->assertSet('items.0.tax_rate', '18.00');
});

test('can update existing invoice', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-UPDATE',
    ], [
        [
            'description' => 'Original Service',
            'quantity' => 1,
            'unit_price' => 5000,
            'tax_rate' => 18,
        ]
    ]);

    Livewire::test(InvoiceWizard::class)
        ->call('edit', $invoice)
        ->set('items.0.description', 'Updated Service')
        ->set('items.0.unit_price', 2000)
        ->call('save')
        ->assertSet('showInvoices', true);

    $invoice->refresh();
    expect($invoice->items->first()->description)->toBe('Updated Service');
    expect($invoice->items->first()->unit_price)->toBe(200000); // $2000 in cents
});

test('can delete invoice', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-DELETE',
    ]);

    Livewire::test(InvoiceWizard::class)
        ->call('delete', $invoice)
        ->assertSee('Invoice deleted successfully!');

    $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
});

test('can delete estimate', function () {
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-DELETE',
    ]);

    Livewire::test(InvoiceWizard::class)
        ->call('delete', $estimate)
        ->assertSee('Estimate deleted successfully!');

    $this->assertDatabaseMissing('invoices', ['id' => $estimate->id]);
});

test('can cancel form', function () {
    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('company_id', 1)
        ->assertSet('showInvoices', false)
        ->call('cancel')
        ->assertSet('showInvoices', true)
        ->assertSet('company_id', null)
        ->assertSet('editingId', null);
});

test('resets form after successful save', function () {
    $company = createCompanyWithLocation();
    $customer = createCustomerWithLocation();

    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('company_id', $company->id)
        ->set('customer_id', $customer->id)
        ->set('company_location_id', $company->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('items.0.description', 'Test Service')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 1000)
        ->call('save')
        ->assertSet('company_id', null)
        ->assertSet('customer_id', null)
        ->assertSet('editingId', null)
        ->assertCount('items', 1)
        ->assertSet('items.0.description', '');
});

test('generates correct invoice number format', function () {
    $company = createCompanyWithLocation();
    $customer = createCustomerWithLocation();

    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('type', 'invoice')
        ->set('company_id', $company->id)
        ->set('customer_id', $customer->id)
        ->set('company_location_id', $company->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('items.0.description', 'Test')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 100)
        ->call('save');

    $invoice = Invoice::where('type', 'invoice')->latest()->first();
    $year = now()->year;
    $month = now()->format('m');
    expect($invoice->invoice_number)->toStartWith("INV-{$year}-{$month}-");
});

test('generates correct estimate number format', function () {
    $company = createCompanyWithLocation();
    $customer = createCustomerWithLocation();

    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('type', 'estimate')
        ->set('company_id', $company->id)
        ->set('customer_id', $customer->id)
        ->set('company_location_id', $company->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('items.0.description', 'Test')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 100)
        ->call('save');

    $estimate = Invoice::where('type', 'estimate')->latest()->first();
    $year = now()->year;
    $month = now()->format('m');
    expect($estimate->invoice_number)->toStartWith("EST-{$year}-{$month}-");
});

test('loads company locations based on selected company', function () {
    $company = createCompanyWithLocation();
    
    // Create additional location for the company
    createLocation(Company::class, $company->id, [
        'name' => 'Branch Office',
        'address_line_1' => '456 Branch St',
        'city' => 'Branch City',
    ]);

    $component = Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('company_id', $company->id);

    $locations = $component->instance()->companyLocations;
    expect($locations)->toHaveCount(2);
    expect($locations->pluck('name')->toArray())->toContain('Branch Office');
});

test('loads customer locations based on selected customer', function () {
    $customer = createCustomerWithLocation();
    
    // Create additional location for the customer
    createLocation(Customer::class, $customer->id, [
        'name' => 'Customer Branch',
        'address_line_1' => '789 Customer Branch St',
        'city' => 'Customer Branch City',
    ]);

    $component = Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('customer_id', $customer->id);

    $locations = $component->instance()->customerLocations;
    expect($locations)->toHaveCount(2);
    expect($locations->pluck('name')->toArray())->toContain('Customer Branch');
});

test('returns empty collection when no company selected', function () {
    $component = Livewire::test(InvoiceWizard::class)->call('create');
    
    $locations = $component->instance()->companyLocations;
    expect($locations)->toHaveCount(0);
});

test('returns empty collection when no customer selected', function () {
    $component = Livewire::test(InvoiceWizard::class)->call('create');
    
    $locations = $component->instance()->customerLocations;
    expect($locations)->toHaveCount(0);
});

test('handles dates correctly when saving', function () {
    $company = createCompanyWithLocation();
    $customer = createCustomerWithLocation();

    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('company_id', $company->id)
        ->set('customer_id', $customer->id)
        ->set('company_location_id', $company->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('issued_at', '2025-03-15')
        ->set('due_at', '2025-04-15')
        ->set('items.0.description', 'Test')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 100)
        ->call('save');

    $invoice = Invoice::latest()->first();
    expect($invoice->issued_at->format('Y-m-d'))->toBe('2025-03-15');
    expect($invoice->due_at->format('Y-m-d'))->toBe('2025-04-15');
});

test('handles null dates when saving', function () {
    $company = createCompanyWithLocation();
    $customer = createCustomerWithLocation();

    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('company_id', $company->id)
        ->set('customer_id', $customer->id)
        ->set('company_location_id', $company->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('issued_at', null)
        ->set('due_at', null)
        ->set('items.0.description', 'Test')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 100)
        ->call('save');

    $invoice = Invoice::latest()->first();
    expect($invoice->issued_at)->toBeNull();
    expect($invoice->due_at)->toBeNull();
});