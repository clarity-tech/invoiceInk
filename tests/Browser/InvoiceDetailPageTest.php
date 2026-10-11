<?php

use App\Models\EmailDelivery;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\User;

/**
 * Visual snapshot + render guard for the invoice detail page.
 *
 * The page is assembled from the shared `Components/ui` primitives, so a
 * missing export or a token that resolves to nothing shows up here as a
 * JavaScript error or a blank region rather than as a failing unit test.
 *
 * The fixture is deliberately the busiest realistic document — part paid,
 * overdue, GST split across CGST/SGST, several payments, notes, terms and a
 * failed send — because that is the case the layout has to stay calm under.
 */
it('renders the invoice detail page for a part-paid overdue document', function () {
    $screenshotDir = base_path('tests/Browser/Screenshots/layout');
    if (! is_dir($screenshotDir)) {
        mkdir($screenshotDir, 0755, true);
    }

    $user = User::factory()->withPersonalTeam()->create([
        'name' => 'Maya Sharma',
        'email' => 'invoice-detail@example.test',
    ]);

    $org = createOrganizationWithLocation([
        'name' => 'Clarity Technologies',
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ], [], $user);

    $customer = createCustomerWithLocation([
        'name' => 'Riverbend Institute of Medical Sciences Limited',
    ], [], $org);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'CT-INV-0042',
        'type' => 'invoice',
        'status' => 'partially_paid',
        'currency' => 'INR',
        'subject' => 'Cardiology wing diagnostic equipment, Q3 instalment',
        'order_number' => 'PO-99817',
        'issued_at' => now()->subDays(40),
        'due_at' => now()->subDays(12),
        'subtotal' => 20000000,
        'tax' => 3600000,
        'total' => 23600000,
        'amount_paid' => 9000000,
        'tax_breakdown' => [
            ['name' => 'CGST 9%', 'rate' => 900, 'amount' => 1800000],
            ['name' => 'SGST 9%', 'rate' => 900, 'amount' => 1800000],
        ],
        'notes' => 'Instalment 2 of 3. Remaining balance falls due on delivery of the final consignment.',
        'terms' => 'Interest at 1.5% per month applies to amounts outstanding beyond the due date.',
    ], [], $org, $customer);

    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Echocardiography system, installation and commissioning',
        'sac_code' => '998719',
        'quantity' => 2,
        'unit_price' => 7500000,
        'tax_rate' => 1800,
    ]);

    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Annual maintenance contract, year one',
        'sac_code' => '998719',
        'quantity' => 1,
        'unit_price' => 5000000,
        'tax_rate' => 1800,
    ]);

    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 4000000,
        'payment_date' => now()->subDays(30),
        'payment_method' => 'bank_transfer',
        'reference' => 'NEFT/2026/88213',
    ]);

    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 5000000,
        'payment_date' => now()->subDays(8),
        'payment_method' => 'bank_transfer',
        'reference' => 'NEFT/2026/90551',
    ]);

    EmailDelivery::factory()->create([
        'organization_id' => $org->id,
        'invoice_id' => $invoice->id,
        'status' => 'sent',
        'recipients' => ['accounts@riverbend.test'],
        'sent_at' => now()->subDays(39),
    ]);

    $this->actingAs($user);

    $page = visit("/invoices/{$invoice->id}")->resize(1440, 1200);

    $page->assertPathIs("/invoices/{$invoice->id}")
        ->assertNoJavaScriptErrors()
        ->assertSee('CT-INV-0042')
        ->assertSee('Riverbend Institute of Medical Sciences Limited')
        // The three figures the page exists to answer, plus the overdue age,
        // which is derived rather than a status and so has no other home.
        ->assertSee('Balance due')
        ->assertSee('Overdue by 12 days')
        ->assertSee('Echocardiography system, installation and commissioning')
        // The right-hand rail is four cards, and a template edit to any one of
        // them can silently take a neighbour with it: every card below is
        // asserted by something it uniquely contains.
        ->assertSee('Payment terms')
        ->assertSee('Public link')
        ->assertSee('Addresses')
        ->assertSee('Billed to')
        ->assertSee('Issued from')
        ->assertSee('123 Business Street')
        ->assertSee('CGST 9%')
        ->assertSee('NEFT/2026/90551')
        // The page must not overflow sideways at desktop width; the invoice
        // list has regressed on exactly this before (f6550132).
        ->assertScript(
            'function() { return (document.documentElement.scrollWidth - document.documentElement.clientWidth) <= 1; }',
        )
        ->screenshot(fullPage: true, filename: 'layout/invoice-detail-desktop');
});

/**
 * An estimate is the same page with different arithmetic: it can carry no
 * payments, so the Paid and Balance figures would both be a meaningless zero,
 * and the action that matters is turning it into an invoice.
 */
it('renders an estimate without payment figures and offers conversion', function () {
    $screenshotDir = base_path('tests/Browser/Screenshots/layout');
    if (! is_dir($screenshotDir)) {
        mkdir($screenshotDir, 0755, true);
    }

    $user = User::factory()->withPersonalTeam()->create([
        'name' => 'Maya Sharma',
        'email' => 'estimate-detail@example.test',
    ]);

    $org = createOrganizationWithLocation([
        'name' => 'Clarity Technologies',
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ], [], $user);

    $customer = createCustomerWithLocation([
        'name' => 'Sandline Pharmacy LLC',
    ], [], $org);

    $estimate = createInvoiceWithItems([
        'invoice_number' => 'CT-EST-0007',
        'type' => 'estimate',
        'status' => 'sent',
        'currency' => 'INR',
        'subject' => 'Pharmacy management system, phase one',
        'issued_at' => now()->subDays(4),
        'due_at' => now()->addDays(26),
        'subtotal' => 8000000,
        'tax' => 1440000,
        'total' => 9440000,
    ], [], $org, $customer);

    InvoiceItem::factory()->create([
        'invoice_id' => $estimate->id,
        'description' => 'Implementation and staff training',
        'quantity' => 1,
        'unit_price' => 8000000,
        'tax_rate' => 1800,
    ]);

    $this->actingAs($user);

    visit("/invoices/{$estimate->id}")
        ->resize(1440, 1000)
        ->assertNoJavaScriptErrors()
        ->assertSee('CT-EST-0007')
        ->assertSee('Convert to invoice')
        ->assertSee('Valid until')
        // The figures that only an invoice can have must be absent, not zero.
        ->assertDontSee('Balance due')
        ->assertDontSee('Record payment')
        ->screenshot(fullPage: true, filename: 'layout/estimate-detail-desktop');
});

/**
 * Both modals were written for the edit page and are reused here as-is. The
 * email one in particular looks its customer up by id out of a `customers`
 * array, and this page passes it an array of exactly one -- so "Email to
 * <name>" in the heading is the assertion that the reuse actually resolves,
 * rather than silently falling back to "Customer".
 */
it('opens the send dialog with the document customer resolved', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'detail-send@example.test',
    ]);
    $org = createOrganizationWithLocation(['setup_completed_at' => now()], [], $user);
    $customer = createCustomerWithLocation(['name' => 'Sandline Pharmacy LLC'], [], $org);
    $invoice = createInvoiceWithItems(['invoice_number' => 'CT-INV-0101'], [], $org, $customer);

    $this->actingAs($user);

    $this->visit("/invoices/{$invoice->id}")
        ->waitForText('CT-INV-0101')
        ->click('button:has-text("Send")')
        ->waitForText('Email to Sandline Pharmacy LLC')
        ->assertSee('Send To')
        ->assertNoJavaScriptErrors();
});

it('records a payment from the detail page and shows it without leaving', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'detail-payment@example.test',
    ]);
    $org = createOrganizationWithLocation(['setup_completed_at' => now()], [], $user);
    $customer = createCustomerWithLocation([], [], $org);
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'CT-INV-0102',
        'total' => 11800,
        'amount_paid' => 0,
        'status' => 'sent',
    ], [], $org, $customer);

    $this->actingAs($user);

    // The modal pre-fills the amount with the outstanding balance, so
    // submitting settles the invoice. recordPayment() returns back(), and this
    // is what proves back() means this page now.
    $this->visit("/invoices/{$invoice->id}")
        ->waitForText('CT-INV-0102')
        ->click('#main-content button:has-text("Record payment")')
        ->waitForText('Record Payment')
        ->fill('input[placeholder="e.g. TXN-12345"]', 'NEFT/TEST/4242')
        ->click('button[type="submit"]:has-text("Record Payment")')
        ->waitForText('NEFT/TEST/4242')
        ->assertPathIs("/invoices/{$invoice->id}")
        ->assertNoJavaScriptErrors();

    expect($invoice->fresh()->amount_paid)->toBe(11800);
});

it('does not push the page sideways on a phone', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'detail-mobile@example.test',
    ]);
    $org = createOrganizationWithLocation(['setup_completed_at' => now()], [], $user);
    $customer = createCustomerWithLocation(['name' => 'Riverbend Institute of Medical Sciences Limited'], [], $org);
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'CT-INV-0103',
        'currency' => 'INR',
        'issued_at' => now()->subDays(20),
        'due_at' => now()->addDays(10),
        'subtotal' => 20000000,
        'tax' => 3600000,
        'total' => 23600000,
        'amount_paid' => 9000000,
        'status' => 'partially_paid',
    ], [], $org, $customer);

    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Echocardiography system, installation and commissioning',
        'quantity' => 2,
        'unit_price' => 7500000,
        'tax_rate' => 1800,
    ]);

    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 9000000,
        'payment_date' => now()->subDays(3),
        'payment_method' => 'bank_transfer',
        'reference' => 'NEFT/2026/90551',
    ]);

    $this->actingAs($user);

    // Five columns of Rs 1,50,000.00-style figures are wider than a phone. The
    // table has to scroll inside its own box; the page itself must not move.
    $this->visit("/invoices/{$invoice->id}")
        ->resize(390, 844)
        ->waitForText('CT-INV-0103')
        ->assertNoJavaScriptErrors()
        ->assertScript(
            'function() { return (document.documentElement.scrollWidth - document.documentElement.clientWidth) <= 1; }',
        )
        ->screenshot(fullPage: true, filename: 'layout/invoice-detail-mobile');
});

/**
 * Deleting is the one action here that destroys the page it was taken from, so
 * the server redirects to the list rather than back. The icon is unlabelled on
 * screen, which is also what keeps it distinguishable from the "Delete"
 * buttons on the payment rows below.
 */
it('deletes the document and lands on the list', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'invoice-detail-delete@example.test',
    ]);

    $org = createOrganizationWithLocation([
        'name' => 'Clarity Technologies',
        'setup_completed_at' => now(),
    ], [], $user);

    $customer = createCustomerWithLocation([], [], $org);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'CT-INV-0099',
        'type' => 'invoice',
    ], [], $org, $customer);

    $this->actingAs($user);

    visit("/invoices/{$invoice->id}")
        ->assertNoJavaScriptErrors()
        ->click('button[aria-label="Delete invoice CT-INV-0099"]')
        ->waitForText('Delete Document')
        ->assertSee('Are you sure you want to delete this document')
        ->click('[role="dialog"] button:has-text("Delete")')
        ->assertPathIs('/invoices')
        ->assertDontSee('CT-INV-0099');

    $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
});

it('links to the public view of the document', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'invoice-detail-public@example.test',
    ]);

    $org = createOrganizationWithLocation(['setup_completed_at' => now()], [], $user);
    $customer = createCustomerWithLocation([], [], $org);

    $estimate = createInvoiceWithItems([
        'invoice_number' => 'CT-EST-0099',
        'type' => 'estimate',
    ], [], $org, $customer);

    $this->actingAs($user);

    visit("/invoices/{$estimate->id}")
        ->assertNoJavaScriptErrors()
        ->assertSee('View public')
        // An estimate's public view lives under /estimates, not /invoices.
        ->assertScript(
            "function() { return document.querySelector('a[href=\"/estimates/view/{$estimate->ulid}\"]') !== null; }",
        );
});
