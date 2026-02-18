<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;

test('can render invoice list page', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    $response = $this->get('/invoices');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Invoices/Index')
        ->has('invoices')
        ->has('filters')
        ->has('statusOptions')
    );
});

test('can filter invoices by type', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    createInvoiceWithItems(['type' => 'invoice'], null, $organization, $customer);
    createInvoiceWithItems(['type' => 'estimate'], null, $organization, $customer);

    $response = $this->get('/invoices?type=invoice');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Invoices/Index')
        ->where('filters.type', 'invoice')
    );
});

test('can filter invoices by status', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    $response = $this->get('/invoices?status=draft');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('filters.status', 'draft')
    );
});

test('can delete invoice', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems([], null, $organization, $customer);

    expect(Invoice::count())->toBe(1);

    $this->delete("/invoices/{$invoice->id}")
        ->assertRedirect();

    expect(Invoice::withoutGlobalScopes()->count())->toBe(0);
    expect(InvoiceItem::count())->toBe(0);
});

test('cannot delete another organizations invoice', function () {
    $organization1 = createOrganizationWithLocation();
    $organization2 = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization1);

    $invoice = createInvoiceWithItems([], null, $organization1, $customer);

    $this->actingAs($organization2->owner);

    $this->delete("/invoices/{$invoice->id}")
        ->assertNotFound();
});

test('can duplicate invoice', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems([], null, $organization, $customer);

    expect(Invoice::count())->toBe(1);

    $this->post("/invoices/{$invoice->id}/duplicate")
        ->assertRedirect();

    expect(Invoice::withoutGlobalScopes()->count())->toBe(2);
});

test('can convert estimate to invoice', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $estimate = createInvoiceWithItems(['type' => 'estimate'], null, $organization, $customer);

    $this->post("/invoices/{$estimate->id}/convert")
        ->assertRedirect();

    // Should have original estimate + new invoice
    expect(Invoice::withoutGlobalScopes()->where('type', 'invoice')->count())->toBe(1);
});

test('cannot convert invoice to invoice', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems(['type' => 'invoice'], null, $organization, $customer);

    $this->post("/invoices/{$invoice->id}/convert")
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('invoices include customer relationship', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    createInvoiceWithItems([], null, $organization, $customer);

    $response = $this->get('/invoices');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('invoices.data.0.customer')
    );
});
