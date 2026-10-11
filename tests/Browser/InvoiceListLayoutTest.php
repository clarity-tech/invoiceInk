<?php

use App\Models\User;

/**
 * Visual snapshot + overflow regression guard for the invoice/estimate list table.
 *
 * The worst case used to be an estimate row carrying six actions beside a long,
 * un-wrappable customer name: that overflowed the `max-w-7xl` container and the
 * Actions column was clipped off the right edge (`overflow-x-auto` kept it in
 * the DOM but scrolled out of view).
 *
 * A row is down to two actions now -- opening, editing, sending, converting and
 * deleting moved to the document's own page -- so the pressure comes from the
 * customer name alone. The guard stays either way: it is the property that
 * actually broke, and nothing stops a column being added back.
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

    // An estimate row: the type badge now shares the Number column with the
    // number itself, which is the narrowest the first column ever gets.
    createInvoiceWithItems([
        'invoice_number' => 'CT-EST-001',
        'type' => 'estimate',
        'status' => 'draft',
        'currency' => 'INR',
        // The customer column now carries the subject on a second line. A
        // fixture without one never renders that line, so the layout screenshot
        // would not show the real shape of the row.
        'subject' => 'Comprehensive diagnostic equipment supply, installation and annual maintenance contract for the cardiology wing',
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
        ->assertSee('PDF')
        ->assertSee('Duplicate')
        // Moved to the document's own page, reached from the number.
        ->assertDontSee('Convert')
        ->assertDontSee('Delete')
        ->assertSee('Riverbend Institute of Medical Sciences Limited')
        // The real regression guard: the table's scroll container must not
        // overflow horizontally at desktop width (1px tolerance for rounding).
        ->assertScript(
            "function() { const el = document.querySelector('.overflow-x-auto'); return el ? (el.scrollWidth - el.clientWidth) <= 1 : false; }",
        )
        ->screenshot(fullPage: true, filename: 'layout/invoice-list-desktop');
});

/**
 * The type badge and the status badge sit one column apart and are the same
 * shape, so they can only be told apart by colour. StatusBadge spends gray,
 * brand, yellow, amber, green and red; a type badge that picks any of those
 * makes an estimate look like a status -- which is what a green "Estimate"
 * next to a green "Paid" did.
 *
 * Comparing resolved colours rather than class names, because the collision is
 * between two files that do not know about each other, and Tailwind v4 emits
 * oklch() anyway.
 */
it('gives the type badge a colour no status uses', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'invoice-list-badge@example.test',
    ]);

    $org = createOrganizationWithLocation([
        'currency' => 'INR',
        'setup_completed_at' => now(),
    ], [], $user);

    $customer = createCustomerWithLocation([], [], $org);

    // One document per status, so every colour StatusBadge can emit is on the
    // page at once and the comparison below has something to fail against.
    foreach (['draft', 'sent', 'accepted', 'partially_paid', 'paid', 'void'] as $i => $status) {
        createInvoiceWithItems([
            'invoice_number' => sprintf('CT-INV-%03d', $i + 1),
            'type' => 'invoice',
            'status' => $status,
            'currency' => 'INR',
            'issued_at' => now()->subDays($i),
        ], null, $org, $customer);
    }

    createInvoiceWithItems([
        'invoice_number' => 'CT-EST-001',
        'type' => 'estimate',
        'status' => 'draft',
        'currency' => 'INR',
        'issued_at' => now(),
    ], null, $org, $customer);

    $this->actingAs($user);

    $colours = visit('/invoices')->resize(1440, 900)->script(<<<'JS'
    function () {
        const pills = Array.from(document.querySelectorAll('tbody span'))
            .filter(el => /^(Invoice|Estimate|Draft|Sent|Accepted|Partially Paid|Paid|Void)$/.test(el.textContent.trim()));

        const seen = {};
        for (const el of pills) {
            const label = el.textContent.trim();
            seen[label] = getComputedStyle(el).backgroundColor;
        }

        return seen;
    }
    JS);

    expect($colours)->toHaveKeys(['Invoice', 'Estimate', 'Paid', 'Sent']);

    $statuses = collect($colours)->except(['Invoice', 'Estimate']);

    expect($statuses)->not->toContain($colours['Estimate']);
    // Invoice is deliberately the brand tint and "Sent" shares it: both mean
    // "the ordinary case" and the pair has never been reported as confusing.
    // Pinned so the overlap stays a decision rather than an accident.
    expect($colours['Invoice'])->toBe($colours['Sent']);
});
