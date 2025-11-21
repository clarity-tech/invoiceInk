<?php

use App\Livewire\InvoiceList;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Organization;
use Livewire\Livewire;

test('can render invoice list component', function () {
    Livewire::test(InvoiceList::class)
        ->assertStatus(200)
        ->assertSee('Invoices');
});

test('can load invoices with pagination', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation();

    // Act as the organization owner
    $this->actingAs($organization->owner);

    // Create exactly 11 test invoices to trigger pagination (page size is 10)
    for ($i = 1; $i <= 11; $i++) {
        createInvoiceWithItems([
            'type' => 'invoice',
            'invoice_number' => "TEST-INV-{$i}",
            'organization_location_id' => $organization->primaryLocation->id,
            'customer_location_id' => $customer->primaryLocation->id,
        ]);
    }

    // Verify invoices were created
    expect(Invoice::count())->toBe(11);

    $component = Livewire::test(InvoiceList::class);

    // Test that pagination is working - should have "Next" button when > 10 items
    $component->assertSee('Next');

    // Test basic functionality - should see at least one invoice number
    $component->assertSeeHtml('TEST-INV-');
});

test('can delete invoice', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation();
    
    $invoice = createInvoiceWithItems([
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    expect(Invoice::count())->toBe(1);

    // Act as the organization owner
    $this->actingAs($organization->owner);

    Livewire::test(InvoiceList::class)
        ->call('delete', $invoice);

    expect(Invoice::count())->toBe(0);
});

test('can delete estimate', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation();
    
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    expect(Invoice::count())->toBe(1);

    // Act as the organization owner
    $this->actingAs($organization->owner);

    Livewire::test(InvoiceList::class)
        ->call('delete', $estimate);

    expect(Invoice::count())->toBe(0);
});

test('shows empty state when no invoices exist', function () {
    Livewire::test(InvoiceList::class)
        ->assertSee('No documents found');
});

test('shows correct document types', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation();

    // Act as the organization owner
    $this->actingAs($organization->owner);

    createInvoiceWithItems([
        'type' => 'invoice',
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    createInvoiceWithItems([
        'type' => 'estimate', 
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    Livewire::test(InvoiceList::class)
        ->assertSee('INVOICE')
        ->assertSee('ESTIMATE');
});

test('security check prevents access to other organizations invoices', function () {
    // Create two different organizations
    $organization1 = createOrganizationWithLocation();
    $organization2 = createOrganizationWithLocation();
    
    $customer1 = createCustomerWithLocation([], [], $organization1);
    
    $invoice1 = createInvoiceWithItems([
        'organization_location_id' => $organization1->primaryLocation->id,
        'customer_location_id' => $customer1->primaryLocation->id,
    ]);

    // Act as user from organization2, try to delete organization1's invoice
    $this->actingAs($organization2->owner);

    // Should get 403 when trying to access other organization's invoice
    Livewire::test(InvoiceList::class)
        ->call('delete', $invoice1)
        ->assertStatus(403);
});