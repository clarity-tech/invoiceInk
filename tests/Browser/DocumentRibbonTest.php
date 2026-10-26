<?php

use App\Enums\InvoiceStatus;
use App\Models\User;

/**
 * How far the sender's name sits clear of the band, in px; negative when the
 * band covers it. The band is a strip at -45 degrees, so a point (x, y) clears
 * it when (x + y - c) / sqrt(2) exceeds half its thickness, c being x + y at
 * the band's centre, and the name's top-left corner is its nearest point. The
 * band has pointer-events: none, so elementFromPoint cannot see it.
 */
const RIBBON_NAME_CLEARANCE_JS = <<<'JS'
function () {
    const band = document.querySelector('.doc-ribbon span');
    const b = band.getBoundingClientRect();
    const name = document.querySelector('.company-name').getBoundingClientRect();
    const centre = b.left + b.width / 2 + b.top + b.height / 2;

    return (name.left + name.top - centre) / Math.SQRT2 - band.offsetHeight / 2;
}
JS;

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
        // The band has to actually cross the corner: rotated text that stayed
        // inside its unrotated box would still pass every assertion above.
        ->assertScript(
            'function() { var s = document.querySelector(".doc-ribbon span"); var w = document.querySelector(".doc-ribbon"); var sb = s.getBoundingClientRect(); var wb = w.getBoundingClientRect(); return (sb.left < wb.left && sb.bottom > wb.top && sb.top < wb.top + wb.height) ? "crosses" : "contained"; }',
            'crosses',
        )
        ->screenshot(filename: "layout/document-ribbon-{$status}");

    // It crosses the corner without covering who the document is from.
    expect($page->script(RIBBON_NAME_CLEARANCE_JS))->toBeGreaterThan(0);
})->with(
    // Derived rather than listed: a status added to the enum gets a screenshot
    // without anyone remembering to add one.
    fn () => collect(InvoiceStatus::cases())
        ->map(fn (InvoiceStatus $status) => [$status->value, $status->label()])
        ->all()
);

/**
 * The band crosses the logo, so it is the logo's height that decides where the
 * name starts. A wordmark is short and wide, and with no logo the name would
 * start at the very top -- both used to put the name under the band.
 */
it('keeps the sender name clear of the band whatever the logo', function (?array $logo, int $width) {
    $user = User::factory()->withPersonalTeam()->create(['email' => 'ribbon-logo@example.test']);
    $org = createOrganizationWithLocation(['name' => 'Clarity Technologies'], [], $user);
    $customer = createCustomerWithLocation([], [], $org);
    // The longest label the band has to hold.
    $invoice = createInvoiceWithItems(['status' => 'partially_paid'], null, $org, $customer);

    $page = visit(sharePath($invoice))->resize($width, 900);

    if ($logo !== null) {
        // Placed where the template puts a real one, with the same class: an
        // upload needs object storage, and only the image's box matters here.
        [$logoWidth, $logoHeight] = $logo;
        $page->script(<<<JS
        async function () {
            const img = document.createElement('img');
            img.className = 'company-logo';
            img.src = 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="{$logoWidth}" height="{$logoHeight}"/>');
            document.querySelector('.doc-from').prepend(img);
            await img.decode();
        }
        JS);
    }

    expect($page->script(RIBBON_NAME_CLEARANCE_JS))->toBeGreaterThan(0);
})->with([
    'no logo' => [null],
    'full-height logo' => [[180, 52]],
    'short wide logo' => [[600, 66]],
])->with([1440, 390]);
