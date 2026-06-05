<?php

use App\Models\ExchangeRate;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\Http;

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

    expect(Invoice::count())->toBe(0);
    expect(Invoice::withTrashed()->count())->toBe(1);
    expect(Invoice::withTrashed()->find($invoice->id)->trashed())->toBeTrue();
    expect(InvoiceItem::where('invoice_id', $invoice->id)->count())->toBe($invoice->items()->count());
});

test('can render trash page listing soft-deleted invoices', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $live = createInvoiceWithItems([], null, $organization, $customer);
    $trashed = createInvoiceWithItems([], null, $organization, $customer);
    $trashed->delete();

    $response = $this->get('/invoices/trash');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Invoices/Trash')
        ->has('invoices.data', 1)
        ->where('invoices.data.0.id', $trashed->id)
    );
});

test('can restore a soft-deleted invoice', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems([], null, $organization, $customer);
    $invoice->delete();

    expect(Invoice::count())->toBe(0);

    $this->post("/invoices/{$invoice->id}/restore")
        ->assertRedirect();

    expect(Invoice::count())->toBe(1);
    expect(Invoice::find($invoice->id)->trashed())->toBeFalse();
});

test('cannot restore another organizations invoice', function () {
    $organization1 = createOrganizationWithLocation();
    $organization2 = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization1);

    $invoice = createInvoiceWithItems([], null, $organization1, $customer);
    $invoice->delete();

    $this->actingAs($organization2->owner);

    $this->post("/invoices/{$invoice->id}/restore")
        ->assertNotFound();
});

test('can permanently delete a soft-deleted invoice', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems([], null, $organization, $customer);
    $invoiceId = $invoice->id;
    $invoice->delete();

    $this->delete("/invoices/{$invoiceId}/force")
        ->assertRedirect();

    expect(Invoice::withTrashed()->find($invoiceId))->toBeNull();
    expect(InvoiceItem::where('invoice_id', $invoiceId)->count())->toBe(0);
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

test('duplicating a foreign-currency invoice recomputes its exchange rate', function () {
    $organization = createOrganizationWithLocation(['currency' => 'INR']);
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    // Seed today's USD-based rates so getRate() resolves without hitting the network.
    Http::fake();
    ExchangeRate::insert([
        ['date' => now()->toDateString(), 'base' => 'USD', 'quote' => 'USD', 'rate' => 1.0, 'created_at' => now(), 'updated_at' => now()],
        ['date' => now()->toDateString(), 'base' => 'USD', 'quote' => 'INR', 'rate' => 95.0, 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Original USD invoice in an INR org — should convert at 95 INR per USD.
    $invoice = createInvoiceWithItems(['currency' => 'USD', 'exchange_rate' => 95.0], null, $organization, $customer);

    $this->post("/invoices/{$invoice->id}/duplicate")
        ->assertRedirect();

    $duplicate = Invoice::withoutGlobalScopes()->latest('id')->first();

    // Without the fix this would fall back to the column default of 1.0.
    expect((float) $duplicate->exchange_rate)->toBe(95.0)
        ->and($duplicate->currency->value)->toBe('USD');
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
