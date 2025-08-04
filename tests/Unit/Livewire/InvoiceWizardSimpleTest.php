<?php

use App\Livewire\InvoiceWizard;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\ValueObjects\EmailCollection;
use Livewire\Livewire;

test('invoice wizard component loads', function () {
    $component = Livewire::test(InvoiceWizard::class);
    expect($component)->not->toBeNull();
});

test('initializes with correct defaults', function () {
    Livewire::test(InvoiceWizard::class)
        ->assertSet('type', 'invoice')
        ->assertSet('currentStep', 1)
        ->assertSet('showInvoices', true)
        ->assertCount('items', 1);
});

test('can show and hide create form', function () {
    Livewire::test(InvoiceWizard::class)
        ->assertSet('showInvoices', true)
        ->call('create')
        ->assertSet('showInvoices', false)
        ->call('cancel')
        ->assertSet('showInvoices', true);
});

test('can manage items', function () {
    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->assertCount('items', 1)
        ->call('addItem')
        ->assertCount('items', 2)
        ->call('removeItem', 1)
        ->assertCount('items', 1);
});

test('loads invoices through computed property', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-TEST',
    ]);

    $component = Livewire::test(InvoiceWizard::class);
    $invoices = $component->instance()->invoices;
    expect($invoices->total())->toBeGreaterThan(0);
});

test('loads companies and customers', function () {
    $company = createCompanyWithLocation();
    $customer = createCustomerWithLocation();

    $component = Livewire::test(InvoiceWizard::class);
    
    $companies = $component->instance()->companies;
    $customers = $component->instance()->customers;
    
    expect($companies->count())->toBeGreaterThan(0);
    expect($customers->count())->toBeGreaterThan(0);
});

test('can navigate wizard steps', function () {
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
        ->call('previousStep')
        ->assertSet('currentStep', 1);
});

test('validates step 1 requirements', function () {
    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->call('nextStep')
        ->assertHasErrors(['company_id', 'customer_id', 'company_location_id', 'customer_location_id']);
});

test('calculates totals correctly', function () {
    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('items.0.description', 'Test Service')
        ->set('items.0.quantity', 2)
        ->set('items.0.unit_price', 100)
        ->set('items.0.tax_rate', 18)
        ->call('calculateTotals')
        ->assertSet('subtotal', 20000) // $200 in cents
        ->assertSet('tax', 3600) // 18% of $200
        ->assertSet('total', 23600); // $236 in cents
});

test('can populate form for editing', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-EDIT',
        'issued_at' => '2025-01-01',
        'due_at' => '2025-01-31',
    ], [
        [
            'description' => 'Edit Service',
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
        ->assertSet('issued_at', '2025-01-01')
        ->assertSet('due_at', '2025-01-31')
        ->assertCount('items', 1)
        ->assertSet('items.0.description', 'Edit Service');
});

test('can create new invoice', function () {
    $company = createCompanyWithLocation();
    $customer = createCustomerWithLocation();
    $initialCount = Invoice::count();

    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('type', 'invoice')
        ->set('company_id', $company->id)
        ->set('customer_id', $customer->id)
        ->set('company_location_id', $company->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('items.0.description', 'New Service')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 1000)
        ->set('items.0.tax_rate', 18)
        ->call('save');

    expect(Invoice::count())->toBe($initialCount + 1);
    expect(Invoice::latest()->first()->type)->toBe('invoice');
});

test('can create estimate', function () {
    $company = createCompanyWithLocation();
    $customer = createCustomerWithLocation();
    $initialCount = Invoice::where('type', 'estimate')->count();

    Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('type', 'estimate')
        ->set('company_id', $company->id)
        ->set('customer_id', $customer->id)
        ->set('company_location_id', $company->primaryLocation->id)
        ->set('customer_location_id', $customer->primaryLocation->id)
        ->set('items.0.description', 'Estimate Service')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_price', 2000)
        ->set('items.0.tax_rate', 18)
        ->call('save');

    expect(Invoice::where('type', 'estimate')->count())->toBe($initialCount + 1);
    expect(Invoice::latest()->first()->type)->toBe('estimate');
});

test('can delete invoice', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-DELETE',
    ]);

    $initialCount = Invoice::count();

    Livewire::test(InvoiceWizard::class)
        ->call('delete', $invoice);

    expect(Invoice::count())->toBe($initialCount - 1);
    expect(Invoice::find($invoice->id))->toBeNull();
});

test('generates correct invoice numbers', function () {
    $component = Livewire::test(InvoiceWizard::class)->call('create');
    
    // Use reflection to test the private method
    $reflection = new ReflectionClass($component->instance());
    $method = $reflection->getMethod('generateInvoiceNumber');
    $method->setAccessible(true);

    $component->instance()->type = 'invoice';
    $invoiceNumber = $method->invoke($component->instance());
    expect($invoiceNumber)->toStartWith('INV-');

    $component->instance()->type = 'estimate';
    $estimateNumber = $method->invoke($component->instance());
    expect($estimateNumber)->toStartWith('EST-');
});

test('loads locations based on selected entities', function () {
    $company = createCompanyWithLocation();
    $customer = createCustomerWithLocation();

    // Create additional locations
    createLocation(Company::class, $company->id, [
        'name' => 'Branch Office',
        'address_line_1' => '789 Branch St',
        'city' => 'Branch City',
    ]);

    createLocation(Customer::class, $customer->id, [
        'name' => 'Customer Branch',
        'address_line_1' => '321 Customer Branch St',
        'city' => 'Customer Branch City',
    ]);

    $component = Livewire::test(InvoiceWizard::class)
        ->call('create')
        ->set('company_id', $company->id)
        ->set('customer_id', $customer->id);

    $companyLocations = $component->instance()->companyLocations;
    $customerLocations = $component->instance()->customerLocations;

    expect($companyLocations->count())->toBe(2);
    expect($customerLocations->count())->toBe(2);
});

test('returns empty collections when no entity selected', function () {
    $component = Livewire::test(InvoiceWizard::class)->call('create');

    $companyLocations = $component->instance()->companyLocations;
    $customerLocations = $component->instance()->customerLocations;

    expect($companyLocations->count())->toBe(0);
    expect($customerLocations->count())->toBe(0);
});