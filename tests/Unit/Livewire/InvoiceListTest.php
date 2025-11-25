<?php

use App\Livewire\InvoiceList;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Services\PdfService;
use Livewire\Livewire;
use Mockery;

afterEach(function () {
    Mockery::close();
});

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

test('can download pdf for invoice', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation();

    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    // Act as the organization owner
    $this->actingAs($organization->owner);

    // Mock PdfService to avoid actual PDF generation in tests
    $mockPdfService = Mockery::mock(PdfService::class);
    $mockPdfService->shouldReceive('downloadInvoicePdf')
        ->with($invoice)
        ->once()
        ->andReturn(response('PDF content', 200, ['Content-Type' => 'application/pdf']));

    $this->app->instance(PdfService::class, $mockPdfService);

    $component = Livewire::test(InvoiceList::class);
    $response = $component->call('downloadPdf', $invoice);

    // Verify the response is returned (actual PDF generation is mocked)
    expect($response)->not->toBeNull();
});

test('can download pdf for estimate', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation();

    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    // Act as the organization owner
    $this->actingAs($organization->owner);

    // Mock PdfService for estimate
    $mockPdfService = Mockery::mock(PdfService::class);
    $mockPdfService->shouldReceive('downloadEstimatePdf')
        ->with($estimate)
        ->once()
        ->andReturn(response('PDF content', 200, ['Content-Type' => 'application/pdf']));

    $this->app->instance(PdfService::class, $mockPdfService);

    $component = Livewire::test(InvoiceList::class);
    $response = $component->call('downloadPdf', $estimate);

    // Verify the response is returned
    expect($response)->not->toBeNull();
});

test('pdf download security check prevents access to other organizations documents', function () {
    // Create two different organizations
    $organization1 = createOrganizationWithLocation();
    $organization2 = createOrganizationWithLocation();

    $customer1 = createCustomerWithLocation([], [], $organization1);

    $invoice1 = createInvoiceWithItems([
        'organization_location_id' => $organization1->primaryLocation->id,
        'customer_location_id' => $customer1->primaryLocation->id,
    ]);

    // Act as user from organization2, try to download organization1's invoice PDF
    $this->actingAs($organization2->owner);

    // Should get 403 when trying to download other organization's invoice PDF
    Livewire::test(InvoiceList::class)
        ->call('downloadPdf', $invoice1)
        ->assertStatus(403);
});

test('delete method handles invoice items correctly', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation();

    $invoice = createInvoiceWithItems([
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    // Verify invoice has items
    expect($invoice->items()->count())->toBeGreaterThan(0);
    $itemCount = $invoice->items()->count();

    // Act as the organization owner
    $this->actingAs($organization->owner);

    Livewire::test(InvoiceList::class)
        ->call('delete', $invoice);

    // Verify both invoice and its items are deleted
    expect(Invoice::count())->toBe(0);
    expect(\App\Models\InvoiceItem::count())->toBe(0);
});

test('delete method shows correct flash message for invoice vs estimate', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation();

    // Test invoice deletion message
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    $this->actingAs($organization->owner);

    Livewire::test(InvoiceList::class)
        ->call('delete', $invoice);

    expect(session('message'))->toBe('Invoice deleted successfully!');

    // Test estimate deletion message
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    Livewire::test(InvoiceList::class)
        ->call('delete', $estimate);

    expect(session('message'))->toBe('Estimate deleted successfully!');
});

test('computed invoices property loads correct relationships', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation();

    $invoice = createInvoiceWithItems([
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    // Act as the organization owner
    $this->actingAs($organization->owner);

    $component = Livewire::test(InvoiceList::class);
    $invoices = $component->get('invoices');

    expect($invoices)->not->toBeEmpty();

    $firstInvoice = $invoices->first();

    // Verify relationships are loaded
    expect($firstInvoice->relationLoaded('organizationLocation'))->toBeTrue();
    expect($firstInvoice->relationLoaded('customerLocation'))->toBeTrue();
});

test('invoices are ordered by latest first', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation();

    // Act as the organization owner
    $this->actingAs($organization->owner);

    // Create invoices with different timestamps
    $oldInvoice = createInvoiceWithItems([
        'invoice_number' => 'OLD-001',
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
        'created_at' => now()->subDays(2),
    ]);

    $newInvoice = createInvoiceWithItems([
        'invoice_number' => 'NEW-001',
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
        'created_at' => now(),
    ]);

    $component = Livewire::test(InvoiceList::class);
    $invoices = $component->get('invoices');

    // Newest should be first
    expect($invoices->first()->invoice_number)->toBe('NEW-001');
    expect($invoices->last()->invoice_number)->toBe('OLD-001');
});

test('pagination resets after deletion', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation();

    // Act as the organization owner
    $this->actingAs($organization->owner);

    // Create enough invoices to trigger pagination (11 invoices, 10 per page)
    $invoices = [];
    for ($i = 1; $i <= 11; $i++) {
        $invoices[] = createInvoiceWithItems([
            'invoice_number' => "INV-{$i}",
            'organization_location_id' => $organization->primaryLocation->id,
            'customer_location_id' => $customer->primaryLocation->id,
        ]);
    }

    $component = Livewire::test(InvoiceList::class);

    // Go to page 2
    $component->set('page', 2);

    // Delete the invoice on page 2
    $component->call('delete', $invoices[10]); // Last invoice

    // Should reset to page 1 after deletion
    $component->assertSet('page', 1);
});

test('handles unauthenticated user gracefully', function () {
    // Don't authenticate any user
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation();

    $invoice = createInvoiceWithItems([
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    // Should not crash when no user is authenticated
    $component = Livewire::test(InvoiceList::class);

    // Should show empty state since no user context
    $component->assertSee('No documents found');

    // Attempting operations should fail
    $component->call('delete', $invoice)
        ->assertStatus(403);

    $component->call('downloadPdf', $invoice)
        ->assertStatus(403);
});
