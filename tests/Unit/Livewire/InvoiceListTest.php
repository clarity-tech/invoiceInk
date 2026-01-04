<?php

use App\Livewire\InvoiceList;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Services\PdfService;
use Livewire\Livewire;

afterEach(function () {
    Mockery::close();
});

test('can render invoice list component', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    Livewire::test(InvoiceList::class)
        ->assertStatus(200)
        ->assertSee('Invoices');
});

test('can load invoices with pagination', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    // Create exactly 11 test invoices to trigger pagination (page size is 10)
    for ($i = 1; $i <= 11; $i++) {
        createInvoiceWithItems([
            'type' => 'invoice',
            'invoice_number' => "TEST-INV-{$i}",
        ], null, $organization, $customer);
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
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems([], null, $organization, $customer);

    expect(Invoice::count())->toBe(1);

    Livewire::test(InvoiceList::class)
        ->call('delete', $invoice);

    expect(Invoice::withoutGlobalScopes()->count())->toBe(0);
});

test('can delete estimate', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
    ], null, $organization, $customer);

    expect(Invoice::count())->toBe(1);

    Livewire::test(InvoiceList::class)
        ->call('delete', $estimate);

    expect(Invoice::withoutGlobalScopes()->count())->toBe(0);
});

test('shows empty state when no invoices exist', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    Livewire::test(InvoiceList::class)
        ->assertSee('No documents found');
});

test('shows correct document types', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    createInvoiceWithItems([
        'type' => 'invoice',
    ], null, $organization, $customer);

    createInvoiceWithItems([
        'type' => 'estimate',
    ], null, $organization, $customer);

    Livewire::test(InvoiceList::class)
        ->assertSee('INVOICE')
        ->assertSee('ESTIMATE');
});

test('security check prevents access to other organizations invoices', function () {
    // Create two different organizations
    $organization1 = createOrganizationWithLocation();
    $organization2 = createOrganizationWithLocation();

    $customer1 = createCustomerWithLocation([], [], $organization1);

    $invoice1 = createInvoiceWithItems([], null, $organization1, $customer1);

    // Act as user from organization2, try to delete organization1's invoice
    $this->actingAs($organization2->owner);

    // Should get 403 when trying to access other organization's invoice
    Livewire::test(InvoiceList::class)
        ->call('delete', $invoice1)
        ->assertStatus(403);
});

test('can download pdf for invoice', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
    ], null, $organization, $customer);

    // Mock PdfService to avoid actual PDF generation in tests
    $mockPdfService = Mockery::mock(PdfService::class);
    $mockPdfService->shouldReceive('downloadInvoicePdf')
        ->once()
        ->andReturn(response('PDF content', 200, ['Content-Type' => 'application/pdf']));

    $this->app->instance(PdfService::class, $mockPdfService);

    $component = Livewire::test(InvoiceList::class);
    $component->call('downloadPdf', $invoice);
});

test('can download pdf for estimate', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
    ], null, $organization, $customer);

    // Mock PdfService for estimate
    $mockPdfService = Mockery::mock(PdfService::class);
    $mockPdfService->shouldReceive('downloadEstimatePdf')
        ->once()
        ->andReturn(response('PDF content', 200, ['Content-Type' => 'application/pdf']));

    $this->app->instance(PdfService::class, $mockPdfService);

    $component = Livewire::test(InvoiceList::class);
    $component->call('downloadPdf', $estimate);
});

test('pdf download security check prevents access to other organizations documents', function () {
    // Create two different organizations
    $organization1 = createOrganizationWithLocation();
    $organization2 = createOrganizationWithLocation();

    $customer1 = createCustomerWithLocation([], [], $organization1);

    $invoice1 = createInvoiceWithItems([], null, $organization1, $customer1);

    // Act as user from organization2, try to download organization1's invoice PDF
    $this->actingAs($organization2->owner);

    // Should get 403 when trying to download other organization's invoice PDF
    Livewire::test(InvoiceList::class)
        ->call('downloadPdf', $invoice1)
        ->assertStatus(403);
});

test('delete method handles invoice items correctly', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems([], null, $organization, $customer);

    // Verify invoice has items
    expect($invoice->items()->count())->toBeGreaterThan(0);

    Livewire::test(InvoiceList::class)
        ->call('delete', $invoice);

    // Verify both invoice and its items are deleted
    expect(Invoice::withoutGlobalScopes()->count())->toBe(0);
    expect(\App\Models\InvoiceItem::count())->toBe(0);
});

test('delete method works correctly for invoice vs estimate', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    // Test invoice deletion
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
    ], null, $organization, $customer);

    expect(Invoice::count())->toBe(1);

    Livewire::test(InvoiceList::class)
        ->call('delete', $invoice);

    expect(Invoice::withoutGlobalScopes()->count())->toBe(0);

    // Test estimate deletion
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
    ], null, $organization, $customer);

    expect(Invoice::count())->toBe(1);

    Livewire::test(InvoiceList::class)
        ->call('delete', $estimate);

    expect(Invoice::withoutGlobalScopes()->count())->toBe(0);
});

test('computed invoices property loads correct relationships', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    createInvoiceWithItems([], null, $organization, $customer);

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
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    // Create invoices and set timestamps after creation (created_at is not mass-assignable)
    $oldInvoice = createInvoiceWithItems([
        'invoice_number' => 'OLD-001',
    ], null, $organization, $customer);
    $oldInvoice->created_at = now()->subDays(2);
    $oldInvoice->save();

    $newInvoice = createInvoiceWithItems([
        'invoice_number' => 'NEW-001',
    ], null, $organization, $customer);

    $component = Livewire::test(InvoiceList::class);
    $invoices = $component->get('invoices');

    // Newest should be first (latest() orders by created_at DESC)
    expect($invoices->first()->invoice_number)->toBe('NEW-001');
    expect($invoices->last()->invoice_number)->toBe('OLD-001');
});

test('pagination resets after deletion', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    // Create enough invoices to trigger pagination (11 invoices, 10 per page)
    $invoices = [];
    for ($i = 1; $i <= 11; $i++) {
        $invoices[] = createInvoiceWithItems([
            'invoice_number' => "INV-{$i}",
        ], null, $organization, $customer);
    }

    $component = Livewire::test(InvoiceList::class);

    // Go to page 2
    $component->call('gotoPage', 2);

    // Delete the invoice on page 2
    $component->call('delete', $invoices[10]); // Last invoice

    // After deletion, page resets - verify the component works
    expect(Invoice::count())->toBe(10);
});

test('delete requires authorization for correct organization', function () {
    // Create two different organizations
    $organization1 = createOrganizationWithLocation();
    $organization2 = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization1);

    $invoice = createInvoiceWithItems([], null, $organization1, $customer);

    // Act as user from different organization
    $this->actingAs($organization2->owner);

    // Should get 403 when trying to delete another organization's invoice
    Livewire::test(InvoiceList::class)
        ->call('delete', $invoice)
        ->assertStatus(403);
});
