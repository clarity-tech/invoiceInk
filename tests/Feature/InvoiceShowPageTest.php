<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Location;
use App\Models\Payment;
use Illuminate\Support\Facades\Queue;

test('the detail page renders the document with its derived balance and overdue age', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems([
        'status' => 'sent',
        'total' => 11800,
        'amount_paid' => 5000,
        'due_at' => now()->subDays(3),
    ], null, $organization, $customer);

    $response = $this->get("/invoices/{$invoice->id}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Invoices/Show')
        ->where('invoice.id', $invoice->id)
        ->where('invoice.invoice_number', $invoice->invoice_number)
        // None of these three is serialised by the model: remaining_balance is
        // an accessor, and overdue is derived rather than a status. The page
        // cannot reconstruct any of them from `total` alone.
        ->where('invoice.remaining_balance', 6800)
        ->where('invoice.is_overdue', true)
        ->where('invoice.days_overdue', 3)
    );
});

test('a paid document is never reported as overdue, whatever its due date', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems([
        'status' => 'paid',
        'total' => 11800,
        'amount_paid' => 11800,
        'due_at' => now()->subMonth(),
    ], null, $organization, $customer);

    $this->get("/invoices/{$invoice->id}")
        ->assertInertia(fn ($page) => $page
            ->where('invoice.is_overdue', false)
            ->where('invoice.days_overdue', null)
            ->where('invoice.remaining_balance', 0)
        );
});

test('line totals and tax are computed on the server, not left to the browser', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems([], [], $organization, $customer);

    // 3 x 333 is 999, and 18% of that rounds rather than dividing evenly --
    // which is the whole reason the figure is not recomputed in Vue.
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Consulting',
        'quantity' => 3,
        'unit_price' => 333,
        'tax_rate' => 1800,
    ]);

    $this->get("/invoices/{$invoice->id}")
        ->assertInertia(fn ($page) => $page
            ->where('invoice.items.0.line_total', 999)
            ->where('invoice.items.0.tax_amount', 180)
        );
});

test('payments are listed newest first', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems([
        'total' => 11800,
    ], null, $organization, $customer);

    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 1000,
        'payment_date' => now()->subMonth(),
    ]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 2000,
        'payment_date' => now()->subDay(),
    ]);

    $this->get("/invoices/{$invoice->id}")
        ->assertInertia(fn ($page) => $page
            ->where('invoice.payments.0.amount', 2000)
            ->where('invoice.payments.1.amount', 1000)
        );
});

test('an estimate opens on the same page carrying its own type', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
    ], null, $organization, $customer);

    $this->get("/invoices/{$estimate->id}")
        ->assertInertia(fn ($page) => $page
            ->component('Invoices/Show')
            ->where('invoice.type', 'estimate')
        );
});

test('a document whose customer was archived still opens', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems([], null, $organization, $customer);
    $customer->delete();

    // Invoice::customer() is declared withTrashed() for exactly this: an
    // archived customer must not blank out the header of a document it was
    // billed to, which is a permanent record.
    $this->get("/invoices/{$invoice->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('invoice.customer.name', $customer->name)
        );
});

test('another organizations invoice is not found', function () {
    $organization = createOrganizationWithLocation();
    $other = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $other);
    $this->actingAs($organization->owner);

    $foreign = createInvoiceWithItems([], null, $other, $customer);

    // 404 rather than 403, matching the rest of the controller: a 403 would
    // confirm that the other organization's document exists.
    $this->get("/invoices/{$foreign->id}")->assertNotFound();
});

test('a guest is sent to sign in rather than shown the document', function () {
    $invoice = createInvoiceWithItems();

    $this->get("/invoices/{$invoice->id}")->assertRedirect('/login');
});

test('the literal invoice paths are not swallowed by the detail route', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    // A bare {invoice} placeholder matches "create" and "trash" too. Both are
    // registered above it and it is constrained to a numeric id, but either
    // guard alone is enough to pass, so this pins the behaviour rather than
    // the arrangement.
    $this->get('/invoices/create')->assertOk();
    $this->get('/invoices/trash')->assertOk();
});

test('a soft-deleted document is not reachable on the detail route', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems([], null, $organization, $customer);
    $invoice->delete();

    // The route does not opt into withTrashed(), unlike restore/force-destroy.
    $this->get("/invoices/{$invoice->id}")->assertNotFound();

    expect(Invoice::withTrashed()->find($invoice->id))->not->toBeNull();
});

test('a shipping address is only reported when it differs from the billing one', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    // The helper bills and ships to the same location, which is the ordinary
    // case. Printing that address twice under two headings states a second
    // fact that does not exist.
    $sameAddress = createInvoiceWithItems([], null, $organization, $customer);

    $this->get("/invoices/{$sameAddress->id}")
        ->assertInertia(fn ($page) => $page->where('hasShippingAddress', false));

    $warehouse = Location::create([
        'name' => 'Warehouse',
        'address_line_1' => '9 Dock Road',
        'city' => 'Port City',
        'state' => 'Customer State',
        'country' => 'IN',
        'postal_code' => '54399',
        'locatable_type' => Customer::class,
        'locatable_id' => $customer->id,
    ]);

    $shippedElsewhere = createInvoiceWithItems([
        'customer_shipping_location_id' => $warehouse->id,
    ], null, $organization, $customer);

    $this->get("/invoices/{$shippedElsewhere->id}")
        ->assertInertia(fn ($page) => $page->where('hasShippingAddress', true));
});

test('recording a payment from the detail page comes back to it', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems(['total' => 11800], null, $organization, $customer);

    // The payment, send and delete-payment endpoints were all written for the
    // edit page and return back(). A bare assertRedirect() would pass even if
    // one of them sent the user somewhere else entirely, so the origin is set
    // and the destination named.
    $this->from(route('invoices.show', $invoice))
        ->post("/invoices/{$invoice->id}/payments", [
            'amount' => 5000,
            'payment_date' => now()->toDateString(),
        ])
        ->assertRedirect(route('invoices.show', $invoice));

    expect($invoice->fresh()->amount_paid)->toBe(5000);
});

test('deleting a payment from the detail page comes back to it', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems(['total' => 11800], null, $organization, $customer);
    $payment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 5000,
    ]);

    $this->from(route('invoices.show', $invoice))
        ->delete("/invoices/{$invoice->id}/payments/{$payment->id}")
        ->assertRedirect(route('invoices.show', $invoice));
});

test('sending from the detail page comes back to it', function () {
    Queue::fake();

    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $invoice = createInvoiceWithItems([], null, $organization, $customer);

    $this->from(route('invoices.show', $invoice))
        ->post("/invoices/{$invoice->id}/send-email", [
            'recipients' => ['accounts@example.test'],
            'subject' => 'Your invoice',
            'body' => 'Please find the invoice attached.',
        ])
        ->assertRedirect(route('invoices.show', $invoice));
});

test('converting an estimate lands on the new invoice, not back on the estimate', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $this->actingAs($organization->owner);

    $estimate = createInvoiceWithItems(['type' => 'estimate'], null, $organization, $customer);

    // Unlike the other three this one does not return back(): conversion
    // produces a NEW document, and leaving the user on the estimate would hide
    // the thing they just created. Pinned because the existing test for this
    // endpoint asserts only that some redirect happened.
    $response = $this->from(route('invoices.show', $estimate))
        ->post("/invoices/{$estimate->id}/convert");

    $newInvoice = Invoice::where('type', 'invoice')->sole();

    $response->assertRedirect(route('invoices.edit', $newInvoice->id));
});
