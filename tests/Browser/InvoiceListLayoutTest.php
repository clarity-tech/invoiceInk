<?php

use App\Models\User;

/**
 * Visual snapshot + overflow regression guard for the invoice/estimate list table.
 *
 * The worst case is an estimate row (6 actions: View / Edit / PDF / Duplicate /
 * Convert / Delete) combined with a long, un-wrappable customer name. Before the
 * fix this overflowed the `max-w-7xl` container and the Actions column was clipped
 * off the right edge (`overflow-x-auto` kept it in the DOM but scrolled out of view).
 *
 * The real guard is the `scrollWidth <= clientWidth` assertion on the scroll
 * container — that is the property that actually broke. `assertSee` alone would
 * pass on the broken layout too, since clipped content stays in the DOM.
 */
it('renders the invoice list actions without clipping at desktop width', function () {
    $screenshotDir = base_path('tests/Browser/Screenshots/layout');
    if (! is_dir($screenshotDir)) {
        mkdir($screenshotDir, 0755, true);
    }

    $user = User::factory()->withPersonalTeam()->create([
        'name' => 'Maya Sharma',
        'email' => 'invoice-list-layout@example.test',
    ]);

    $org = createOrganizationWithLocation([
        'name' => 'Clarity Technologies',
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ], [], $user);

    // Longest name: the worst case for the un-bounded customer column.
    $riverbend = createCustomerWithLocation([
        'name' => 'Riverbend Institute of Medical Sciences Limited',
    ], [], $org);

    $sandline = createCustomerWithLocation([
        'name' => 'Sandline Pharmacy LLC',
    ], [], $org);

    $brightcare = createCustomerWithLocation([
        'name' => 'BRIGHTCARE HEALTH INDIA PRIVATE LIMITED',
    ], [], $org);

    // Estimate = 6 actions (View / Edit / PDF / Duplicate / Convert / Delete).
    createInvoiceWithItems([
        'invoice_number' => 'CT-EST-001',
        'type' => 'estimate',
        'status' => 'draft',
        'currency' => 'INR',
        'issued_at' => now(),
        'subtotal' => 20000000,
        'tax' => 3600000,
        'total' => 23600000,
    ], null, $org, $riverbend);

    createInvoiceWithItems([
        'invoice_number' => 'CT-INV-001',
        'type' => 'invoice',
        'status' => 'sent',
        'currency' => 'INR',
        'issued_at' => now()->subDays(5),
        'subtotal' => 32500000,
        'tax' => 5850000,
        'total' => 38350000,
    ], null, $org, $sandline);

    createInvoiceWithItems([
        'invoice_number' => 'CT-INV-002',
        'type' => 'invoice',
        'status' => 'draft',
        'currency' => 'INR',
        'issued_at' => now()->subDays(10),
        'subtotal' => 12500000,
        'tax' => 2250000,
        'total' => 14750000,
    ], null, $org, $brightcare);

    $this->actingAs($user);

    $page = visit('/invoices')->resize(1440, 900);

    $page->assertPathIs('/invoices')
        ->assertNoJavaScriptErrors()
        // Confirms the full action set renders (permissions + row wiring). Note:
        // presence in the DOM does NOT prove they are un-clipped — the scrollWidth
        // guard below is what actually enforces that.
        ->assertSee('Actions')
        ->assertSee('Convert')
        ->assertSee('Delete')
        ->assertSee('Riverbend Institute of Medical Sciences Limited')
        // The real regression guard: the table's scroll container must not
        // overflow horizontally at desktop width (1px tolerance for rounding).
        ->assertScript(
            "function() { const el = document.querySelector('.overflow-x-auto'); return el ? (el.scrollWidth - el.clientWidth) <= 1 : false; }",
        )
        ->screenshot(fullPage: true, filename: 'layout/invoice-list-desktop');
});
