<?php

use App\Enums\InvoiceStatus;
use App\Models\User;

/**
 * The status is drawn on the preview rather than beside it, and only on the
 * preview -- the PDF the customer receives carries no band.
 *
 * Every status gets a screenshot because the only thing that can go wrong here
 * is geometry and colour: a band placed by rotation inside a self-clipping
 * square either sits across the corner or it does not, and no assertion short
 * of looking will tell you which.
 */
it('draws the status as a corner ribbon on the public document', function (string $status, string $label) {
    $screenshotDir = base_path('tests/Browser/Screenshots/layout');
    if (! is_dir($screenshotDir)) {
        mkdir($screenshotDir, 0755, true);
    }

    $user = User::factory()->withPersonalTeam()->create([
        'email' => "ribbon-{$status}@example.test",
    ]);

    $org = createOrganizationWithLocation([
        'name' => 'Clarity Technologies',
        'currency' => 'INR',
        'country_code' => 'IN',
    ], ['tax_id' => '18AAACC1234C1Z5'], $user);

    $customer = createCustomerWithLocation(['name' => 'Brightcare Health India'], [], $org);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'CT-INV-RIB-'.strtoupper($status),
        'status' => $status,
        'currency' => 'INR',
    ], null, $org, $customer);

    $page = visit(sharePath($invoice))->resize(1100, 900);

    $page->assertNoJavaScriptErrors()
        ->assertSee($label)
        // Drawn over the document's top-left corner, not in the right-hand
        // title block where the pill used to sit.
        ->assertScript(
            'function() { var all = document.querySelectorAll(".doc-ribbon"); if (all.length !== 1) { return "expected one band, found " + all.length; } var rb = all[0].getBoundingClientRect(); var cb = document.querySelector(".document-card").getBoundingClientRect(); return (Math.abs(rb.top - cb.top) < 2 && Math.abs(rb.left - cb.left) < 2) ? "anchored" : "adrift"; }',
            'anchored',
        )
        // An overlay: the header column underneath keeps the position it
        // would have without a band.
        ->assertScript(
            'function() { return getComputedStyle(document.querySelector(".doc-header > div")).paddingTop; }',
            '0px',
        )
        // The band has to actually cross the corner: rotated text that stayed
        // inside its unrotated box would still pass every assertion above.
        ->assertScript(
            'function() { var s = document.querySelector(".doc-ribbon span"); var w = document.querySelector(".doc-ribbon"); var sb = s.getBoundingClientRect(); var wb = w.getBoundingClientRect(); return (sb.left < wb.left && sb.bottom > wb.top && sb.top < wb.top + wb.height) ? "crosses" : "contained"; }',
            'crosses',
        )
        ->screenshot(filename: "layout/document-ribbon-{$status}");
})->with(
    // Derived rather than listed: a status added to the enum gets a screenshot
    // without anyone remembering to add one.
    fn () => collect(InvoiceStatus::cases())
        ->map(fn (InvoiceStatus $status) => [$status->value, $status->label()])
        ->all()
);
