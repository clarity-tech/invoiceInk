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

test('duplicating an invoice persists nothing and opens a seeded create form', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems([], null, $organization, $customer);

    expect(Invoice::count())->toBe(1);

    $this->get("/invoices/{$invoice->id}/duplicate")
        ->assertRedirect(route('invoices.create', ['from' => $invoice->id]))
        // A seeded create form looks like a blank one, so say what is going on.
        ->assertSessionHas('info', "Reviewing a copy of {$invoice->invoice_number}. Save to create it.");

    // The whole point of the change: reviewing a copy must not create one.
    expect(Invoice::withoutGlobalScopes()->count())->toBe(1);
});

test('duplicating an estimate opens the estimate create form', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $estimate = createInvoiceWithItems(['type' => 'estimate'], null, $organization, $customer);

    $this->get("/invoices/{$estimate->id}/duplicate")
        ->assertRedirect(route('estimates.create', ['from' => $estimate->id]));

    expect(Invoice::withoutGlobalScopes()->count())->toBe(1);
});

test('the duplicate create form is pre-filled from the source document', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems([
        'subject' => 'Quarterly retainer',
        'order_number' => 'PO-8891',
        'notes' => 'Thanks for your business.',
        'terms' => 'Net 30. Late payments accrue 1.5% monthly.',
        'tds_rate' => 1000,
        'tds_section' => '194C',
        'email_recipients' => [['email' => 'ap@acme.test', 'checked' => true]],
    ], [
        ['description' => 'Consulting', 'sac_code' => '9983', 'quantity' => 3, 'unit_price' => 250000, 'tax_rate' => 1800],
    ], $organization, $customer);

    $this->get("/invoices/create?from={$invoice->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Invoices/Create')
            ->where('defaults.customer_id', $customer->id)
            ->where('defaults.subject', 'Quarterly retainer')
            ->where('defaults.order_number', 'PO-8891')
            ->where('defaults.notes', 'Thanks for your business.')
            ->where('defaults.terms', 'Net 30. Late payments accrue 1.5% monthly.')
            ->where('defaults.tds_rate', 1000)
            ->where('defaults.tds_section', '194C')
            ->where('defaults.email_recipients.0.email', 'ap@acme.test')
            ->has('defaults.items', 1)
            ->where('defaults.items.0.description', 'Consulting')
            ->where('defaults.items.0.sac_code', '9983')
            ->where('defaults.items.0.quantity', 3)
            ->where('defaults.items.0.unit_price', 250000)
            ->where('defaults.items.0.tax_rate', 1800)
            // The number is minted on save, never pre-filled.
            ->missing('defaults.invoice_number')
        );
});

test('a duplicate saved from the create form gets the next series number', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    // A date-independent pattern, so the expected number never drifts.
    createNumberingSeries([
        'prefix' => 'DUP',
        'format_pattern' => '{PREFIX}-{SEQUENCE:4}',
        'reset_frequency' => 'never',
        'is_default' => true,
    ], $organization);

    $invoice = createInvoiceWithItems([], null, $organization, $customer);

    $defaults = $this->get("/invoices/create?from={$invoice->id}")
        ->assertOk()
        ->viewData('page')['props']['defaults'];

    // Post the seeded form back the way the Vue form would.
    $this->post('/invoices', [
        'type' => 'invoice',
        'status' => 'draft',
        'organization_id' => $organization->id,
        'customer_id' => $defaults['customer_id'],
        'organization_location_id' => $defaults['organization_location_id'],
        'customer_location_id' => $defaults['customer_location_id'],
        'customer_shipping_location_id' => $defaults['customer_shipping_location_id'],
        'issued_at' => $defaults['issued_at'],
        'due_at' => $defaults['due_at'],
        'items' => $defaults['items'],
    ])->assertRedirect();

    $saved = Invoice::withoutGlobalScopes()->latest('id')->first();

    // Previously this was a 'DRAFT-<timestamp>' string that nothing could repair.
    expect($saved->invoice_number)->toBe('DUP-0001')
        ->and($saved->invoice_numbering_series_id)->not->toBeNull();
});

test('a duplicate saved in a foreign currency gets a recomputed exchange rate', function () {
    $organization = createOrganizationWithLocation(['currency' => 'INR']);
    $customer = createCustomerWithLocation(['currency' => 'USD'], [], $organization);
    $this->actingAs($organization->owner);

    // Seed today's USD-based rates so getRate() resolves without hitting the network.
    Http::fake();
    ExchangeRate::insert([
        ['date' => now()->toDateString(), 'base' => 'USD', 'quote' => 'USD', 'rate' => 1.0, 'created_at' => now(), 'updated_at' => now()],
        ['date' => now()->toDateString(), 'base' => 'USD', 'quote' => 'INR', 'rate' => 95.0, 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Original USD invoice in an INR org — the copy should convert at 95 INR per USD.
    $invoice = createInvoiceWithItems(['currency' => 'USD', 'exchange_rate' => 95.0], null, $organization, $customer);

    $defaults = $this->get("/invoices/create?from={$invoice->id}")
        ->assertOk()
        ->viewData('page')['props']['defaults'];

    $this->post('/invoices', [
        'type' => 'invoice',
        'status' => 'draft',
        'organization_id' => $organization->id,
        'customer_id' => $defaults['customer_id'],
        'organization_location_id' => $defaults['organization_location_id'],
        'customer_location_id' => $defaults['customer_location_id'],
        'customer_shipping_location_id' => $defaults['customer_shipping_location_id'],
        'issued_at' => $defaults['issued_at'],
        'due_at' => $defaults['due_at'],
        'items' => $defaults['items'],
    ])->assertRedirect();

    $saved = Invoice::withoutGlobalScopes()->latest('id')->first();

    // store() resolves the rate from the customer's currency at the save date,
    // so the copy is priced correctly rather than defaulting to 1.0.
    expect((float) $saved->exchange_rate)->toBe(95.0)
        ->and($saved->currency->value)->toBe('USD');
});

test('a duplicate saved for an Indian org gets its GST breakdown rebuilt', function () {
    $organization = createOrganizationWithLocation([], ['country' => 'IN', 'tax_id' => '29ABCDE1234F1Z5']);
    $customer = createCustomerWithLocation([], ['country' => 'IN'], $organization);
    $this->actingAs($organization->owner);

    // Supplier state 29 == place of supply 29, so an intra-state CGST/SGST split.
    $invoice = createInvoiceWithItems(['place_of_supply' => '29'], [
        ['description' => 'Consulting', 'sac_code' => '9983', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 1800],
    ], $organization, $customer);

    $defaults = $this->get("/invoices/create?from={$invoice->id}")
        ->assertOk()
        ->viewData('page')['props']['defaults'];

    expect($defaults['place_of_supply'])->toBe('29');

    $this->post('/invoices', [
        'type' => 'invoice',
        'status' => 'draft',
        'organization_id' => $organization->id,
        'customer_id' => $defaults['customer_id'],
        'organization_location_id' => $defaults['organization_location_id'],
        'customer_location_id' => $defaults['customer_location_id'],
        'customer_shipping_location_id' => $defaults['customer_shipping_location_id'],
        'place_of_supply' => $defaults['place_of_supply'],
        'issued_at' => $defaults['issued_at'],
        'due_at' => $defaults['due_at'],
        'items' => $defaults['items'],
    ])->assertRedirect();

    $saved = Invoice::withoutGlobalScopes()->latest('id')->first();

    // The old duplicate() never called applyGstBreakdown(), so copies rendered
    // without the CGST/SGST split. store() rebuilds it.
    expect($saved->tax_type)->toBe('GST')
        ->and($saved->tax_breakdown)->not->toBeEmpty()
        ->and(array_keys($saved->tax_breakdown))->toBe(['CGST @ (9%)', 'SGST @ (9%)']);
});

test('the estimate create form is pre-filled from a duplicated estimate', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'terms' => 'Valid for 14 days.',
    ], [
        ['description' => 'Scoping workshop', 'sac_code' => null, 'quantity' => 1, 'unit_price' => 500000, 'tax_rate' => 0],
    ], $organization, $customer);

    $this->get("/estimates/create?from={$estimate->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Invoices/Create')
            ->where('type', 'estimate')
            ->where('defaults.terms', 'Valid for 14 days.')
            ->where('defaults.items.0.description', 'Scoping workshop')
        );
});

test('duplicating drops references to archived customers and locations', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems(['terms' => 'Net 15.'], null, $organization, $customer);

    $customer->delete();

    // The selects only offer live records, so a stale id would show as blank
    // and fail validation on save. Better to leave the field empty.
    $this->get("/invoices/create?from={$invoice->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('defaults.customer_id', null)
            ->where('defaults.customer_location_id', null)
            ->where('defaults.customer_shipping_location_id', null)
            // Everything not tied to the archived customer still carries over.
            ->where('defaults.terms', 'Net 15.')
            ->has('defaults.items', 1)
        );
});

test('duplicating another organization\'s invoice is not found', function () {
    $organization = createOrganizationWithLocation();
    $other = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $other);
    $this->actingAs($organization->owner);

    $foreign = createInvoiceWithItems([], null, $other, $customer);

    $this->get("/invoices/{$foreign->id}/duplicate")->assertNotFound();
    $this->get("/invoices/create?from={$foreign->id}")->assertNotFound();
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
