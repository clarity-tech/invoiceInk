<?php

use App\Models\EmailDelivery;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\User;
use App\Services\GstBreakdownService;

/**
 * Where a labelled value was rendered: in the summary strip at the top of the
 * page, or down in the Details card. Both are lists of label/value pairs, so a
 * fact can move from one to the other without any `assertSee` noticing.
 */
const LABEL_HOMES = <<<'JS'
function () {
    function leafWithText(text) {
        return Array.from(document.querySelectorAll('#main-content div, #main-content dt, #main-content h2'))
            .find(function (node) {
                return node.children.length === 0 && node.textContent.trim() === text;
            }) || null;
    }

    function cardOf(node) {
        return node ? node.closest('[data-slot="card"]') : null;
    }

    const summaryCard = cardOf(leafWithText('Total'));
    const detailsCard = cardOf(leafWithText('Details'));

    function homeOf(text) {
        const node = leafWithText(text);

        if (!node) {
            return 'nowhere';
        }

        if (summaryCard && summaryCard.contains(node)) {
            return 'summary';
        }

        if (detailsCard && detailsCard.contains(node)) {
            return 'details';
        }

        return 'elsewhere';
    }

    const overdue = leafWithText('Overdue by 12 days');

    return {
        issued: homeOf('Issued'),
        terms: homeOf('Payment terms'),
        overdue: homeOf('Overdue by 12 days'),
        overdueFill: overdue ? getComputedStyle(overdue).backgroundColor : 'nowhere',
        detailsCardFound: detailsCard !== null,
    };
}
JS;

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
    ], ['tax_id' => '29ABCDE1234F1Z5'], $user);

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
        // Supplier and place of supply both Karnataka, so the tax splits
        // into CGST + SGST. The split itself is derived below rather than
        // written out here.
        'place_of_supply' => '29',
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

    // Derived with the service that writes it in production rather than typed
    // out by hand. A hand-written fixture is what let the page render a map as
    // though it were a list of objects: the test agreed with the bug.
    $invoice->load(['items', 'organizationLocation', 'customerLocation']);
    $breakdown = app(GstBreakdownService::class)->build($invoice);

    expect($breakdown)->toBe([
        'CGST @ (9%)' => 1800000,
        'SGST @ (9%)' => 1800000,
    ]);

    $invoice->update(['tax_breakdown' => $breakdown, 'tax_type' => 'GST']);

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
        // Part paid, so the figures genuinely differ and each earns a tile.
        ->assertSee('Paid')
        ->assertSee('Balance due')
        ->assertSee('Overdue by 12 days')
        ->assertSee('Echocardiography system, installation and commissioning')
        // A template edit to one part of the rail can silently take a
        // neighbour with it, so each surviving part is asserted by something
        // it uniquely contains. Details and Addresses are one card now, and
        // it no longer repeats the currency, the sender's own address, or a
        // link that belongs with the actions. Place of supply is what is left
        // that only the rail carries -- Payment terms is no longer, so it is
        // asserted by position below instead of by presence here.
        ->assertSee('Place of supply')
        ->assertSee('Billed to')
        ->assertSee('456 Customer Avenue')
        // Where it ships is said even when that is where it bills: a missing
        // row and a document shipping to the billing address look the same,
        // and only one of them is true.
        ->assertSee('Shipped to')
        ->assertSee('Same as billing address')
        // Gone from the rail: the sender's own address, repeated on every
        // document they own, and a link that belongs with the actions.
        ->assertDontSee('123 Business Street')
        ->assertDontSee('Public link')
        ->assertSee('CGST @ (9%)')
        ->assertSee('SGST @ (9%)')
        // Both halves of what a map rendered as a list produced in production:
        // a blank row reading NaN, and -- because an object has no `.length`
        // -- the aggregate Tax row printed underneath the split as well.
        //
        // Not assertDontSee('NaN'): Playwright matches case-insensitively, so
        // it also matches the "nan" inside "maintenance" and fails on a page
        // that is perfectly correct.
        ->assertScript(
            'function() { return (document.body.innerText.match(/NaN/g) || []).length; }',
            0,
        )
        ->assertScript(
            'function() { return Array.from(document.querySelectorAll("dl dt")).filter(function (dt) { return dt.textContent.trim() === "Tax"; }).length; }',
            0,
        )
        ->assertSee('NEFT/2026/90551')
        // The page must not overflow sideways at desktop width; the invoice
        // list has regressed on exactly this before (f6550132).
        ->assertScript(
            'function() { return (document.documentElement.scrollWidth - document.documentElement.clientWidth) <= 1; }',
        )
        ->screenshot(fullPage: true, filename: 'layout/invoice-detail-desktop');

    // The summary strip and the Details card are both lists of labelled
    // values, so "the page says Payment terms" stopped being evidence of
    // anything the moment a label could live in either. These three facts are
    // asserted by where they render, not by whether they render at all.
    $homes = $page->script(LABEL_HOMES);

    expect($homes['issued'])->toBe('summary', 'Issued belongs with the figures, not in Details');
    expect($homes['terms'])->toBe('summary', 'Payment terms belongs with the figures, not in Details');
    expect($homes['detailsCardFound'])->toBeTrue('the Details card disappeared entirely');

    // How late it is reads as a line under the due date it is late against.
    // It was a filled red badge in the page header, which made it the loudest
    // thing on a page whose own subject is a document.
    expect($homes['overdue'])->toBe('summary');
    expect($homes['overdueFill'])->toBe('rgba(0, 0, 0, 0)', 'the overdue notice is filled in like a badge again');
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
 * Sending lives on the edit page, which is where the document being sent is.
 * The detail page offered it too, from a composer that read the saved copy --
 * so the two controls could send different documents under one name. "Save &
 * Send" saves first, then opens the same composer.
 *
 * That composer looks its customer up by id out of a `customers` array, so
 * "Email to <name>" in the heading is the assertion that it resolves rather
 * than silently falling back to "Customer".
 */
it('sends from the edit page, and offers nothing of the sort on the detail page', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'detail-send@example.test',
    ]);
    $org = createOrganizationWithLocation(['setup_completed_at' => now()], [], $user);
    $customer = createCustomerWithLocation(['name' => 'Sandline Pharmacy LLC'], [], $org);
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'CT-INV-0101',
        'issued_at' => now()->subDays(2),
        'due_at' => now()->addDays(28),
        // A line item of its own: saving is part of this flow now, and the
        // server will not save a document with nothing on it.
    ], null, $org, $customer);

    $this->actingAs($user);

    $this->visit("/invoices/{$invoice->id}")
        ->waitForText('CT-INV-0101')
        ->assertDontSee('Send')
        ->assertNoJavaScriptErrors();

    $this->visit("/invoices/{$invoice->id}/edit")
        ->waitForText('Save & Send')
        ->click('button:has-text("Save & Send")')
        ->waitForText('Email to Sandline Pharmacy LLC')
        ->assertSee('Subject')
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
    // is what proves back() means this page now. Waiting for the reference to
    // appear also proves the payments table arrives with the first payment:
    // there is no card on the page before this click.
    $this->visit("/invoices/{$invoice->id}")
        ->waitForText('CT-INV-0102')
        // The header's button, which is the only one now -- the Payments card
        // carried a second copy and does not render on an unpaid invoice.
        ->click('button:has-text("Record payment")')
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
        // Waiting on the message, not the title: the control that opens the
        // dialog now carries the title's words too.
        ->waitForText('moves to Trash')
        ->assertSee('CT-INV-0099 moves to Trash')
        ->click('[role="dialog"] button:has-text("Delete")')
        ->assertPathIs('/invoices')
        ->assertDontSee('CT-INV-0099');

    // Soft -- which is what made "this action cannot be undone" wrong in the
    // dialog above for as long as this assertion has been passing.
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
        // An estimate's public view lives under /estimates, not /invoices, and
        // is a signed link rather than the bare ULID.
        ->assertScript(
            "function() { return document.querySelector('a[href*=\"/estimates/view/{$estimate->ulid}?expires=\"][href*=\"&signature=\"]') !== null; }",
        );
});

/**
 * An unpaid invoice is the common case while an invoice still matters, and it
 * is the one the page served worst: Balance restated the Total, Paid read
 * zero, and two cards held a row each to report that nothing had happened yet.
 */
it('says nothing twice on an invoice nobody has paid yet', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'quiet-invoice@example.test',
    ]);

    $org = createOrganizationWithLocation([
        'name' => 'Clarity Technologies',
        'currency' => 'INR',
        'country_code' => 'IN',
    ], [], $user);

    $customer = createCustomerWithLocation(['name' => 'Brightcare Health India'], [], $org);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'CT-INV-QUIET',
        'status' => 'sent',
        'amount_paid' => 0,
        'issued_at' => now()->subDays(6),
        'due_at' => now()->addDays(24),
        'payment_terms' => 'net_30',
    ], null, $org, $customer);

    $this->actingAs($user);

    visit("/invoices/{$invoice->id}")
        ->resize(1440, 1200)
        ->assertNoJavaScriptErrors()
        ->assertSee('CT-INV-QUIET')
        // The two figures that differ from one another, and the dates they
        // are read against -- issued, the term it implies, and the due date.
        ->assertSee('Total')
        ->assertSee('Due')
        ->assertSee('Issued')
        ->assertSee('Net 30')
        // Until money moves these say the same thing as Total, twice over.
        ->assertDontSee('Balance due')
        ->assertDontSee('Amount paid')
        // Absent rather than empty, the way Attachments and Notes & terms
        // already behave on this page.
        ->assertDontSee('No payments recorded yet')
        ->assertDontSee('Never sent')
        ->assertDontSee('Email history')
        // Subject is the exception to that: it holds its row on a document
        // that has none, so the card states what can be carried there rather
        // than leaving its absence to be read as the field not existing.
        ->assertSee('Subject')
        ->assertSee('Not set')
        // So does the place of supply, for the same reason and a sharper one:
        // without it the document shows a single tax total and no
        // CGST/SGST/IGST split.
        ->assertSee('Place of supply')
        // Recording the first payment is still one click, from the header.
        ->assertSee('Record payment')
        // The one control that ends the page sits below the document with a
        // word on it, not as an unlabelled icon in the row you reach for
        // daily. The layout's header is outside #main-content, so containment
        // is what tells the two apart.
        ->assertSee('Delete invoice')
        ->assertScript(
            'function() { var btn = Array.from(document.querySelectorAll("button")).find(function (b) { return b.textContent.trim().indexOf("Delete invoice") === 0; }); if (!btn) { return "missing"; } return document.querySelector("#main-content").contains(btn) ? "below the document" : "still in the header"; }',
            'below the document',
        )
        ->screenshot(fullPage: true, filename: 'layout/invoice-detail-unpaid')
        // The confirmation told the truth about a soft delete: it goes to
        // Trash and comes back. It used to say the action could not be undone,
        // which was never true of this model -- the permanent delete is a
        // separate action taken from Trash.
        ->click('button:has-text("Delete invoice")')
        ->waitForText('moves to Trash')
        ->assertSee('CT-INV-QUIET moves to Trash')
        ->assertDontSee('cannot be undone');
});
