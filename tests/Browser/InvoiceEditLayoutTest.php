<?php

use App\Models\EmailDelivery;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\User;

/**
 * Visual snapshot + render guard for the invoice edit form.
 *
 * The form is the largest surface in the app: it ran to roughly three thousand
 * pixels of scroll at desktop width, built from eight full-width cards of equal
 * weight, one of which (TDS/TCS) was empty on almost every document. The height
 * assertion below is the blunt instrument that keeps it from growing back.
 *
 * The fixture is a realistic Indian GST invoice with payments against it, so
 * the page under test is the busiest version of itself.
 */
function editLayoutFixture(string $email): array
{
    $screenshotDir = base_path('tests/Browser/Screenshots/layout');
    if (! is_dir($screenshotDir)) {
        mkdir($screenshotDir, 0755, true);
    }

    $user = User::factory()->withPersonalTeam()->create([
        'name' => 'Maya Sharma',
        'email' => $email,
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

    // Deliberately identical to the detail page's fixture
    // (InvoiceDetailPageTest), so the two pages can be compared at a glance
    // and the recorded heights describe the same document.
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

    return [$user, $invoice];
}

it('renders the edit form without running away vertically', function () {
    [$user, $invoice] = editLayoutFixture('invoice-edit-layout@example.test');

    $this->actingAs($user);

    $page = visit("/invoices/{$invoice->id}/edit")->resize(1440, 1200);

    $page->assertPathIs("/invoices/{$invoice->id}/edit")
        ->assertNoJavaScriptErrors()
        // The document has to identify itself: the page used to say only
        // "Edit Invoice", with the number nowhere on screen.
        ->assertSee('CT-INV-0042')
        ->assertSee('Riverbend Institute of Medical Sciences Limited')
        // Both open the document rather than change it, so they live in the
        // header now rather than in a floating row above the first card.
        ->assertSee('View Public')
        ->assertSee('Download PDF')
        // Two ways to add a line: the card header, and the strip under the
        // last row. On a document with a dozen items the header is a scroll
        // away from the row that was just finished.
        ->assertScript(
            "function() { return [...document.querySelectorAll('button')].filter(b => b.textContent.trim() === 'Add Item').length; }",
            2,
        )
        // Collapsed by default: this invoice carries no TDS or TCS, and the
        // expanded card was ~200px of empty inputs on every Indian document.
        ->assertSee('TDS / TCS')
        ->assertDontSee('TDS (deducted)')
        // The page must not overflow sideways at desktop width.
        ->assertScript(
            'function() { return (document.documentElement.scrollWidth - document.documentElement.clientWidth) <= 1; }',
        )
        // The structural guard, and the one that actually holds: the form is
        // five cards -- parties, details, items, source taxes, trimmings. It
        // was eight, each holding one or two fields at the same weight as the
        // items table. A sixth means something was unfolded back out.
        ->assertScript(
            'function() { return document.querySelectorAll(\'form > [data-slot="card"]\').length <= 5; }',
        )
        // A loose ceiling on top of it. This document measures ~2170px, down
        // from ~2990 before the merge; the limit leaves room for copy to grow
        // but not for a section to come back.
        ->assertScript(
            'function() { return document.documentElement.scrollHeight < 2700; }',
        )
        ->screenshot(fullPage: true, filename: 'layout/invoice-edit-desktop');
});

/**
 * A hidden field hides its validation error (.ai/rules/frontend.md), which is
 * exactly the risk a collapsed section introduces. The source-tax card has to
 * unfold itself for a document that already carries a rate.
 */
it('opens the source tax card for a document that already has a rate', function () {
    [$user, $invoice] = editLayoutFixture('invoice-edit-tds@example.test');

    $invoice->update(['tds_rate' => 1000, 'tds_section' => '194J']);

    $this->actingAs($user);

    visit("/invoices/{$invoice->id}/edit")
        ->resize(1440, 1200)
        ->assertNoJavaScriptErrors()
        ->assertSee('TDS (deducted)');
});

/**
 * The collapse is only safe if the fields still work once unfolded. These
 * inputs moved from a native `:value` + `@input` pair to the shared Input
 * primitive, so this walks the whole path: open the card, type a rate, save,
 * and read back what the server stored. Rates are basis points, so 10% is 1000.
 */
it('saves a source tax rate typed into the collapsed card', function () {
    [$user, $invoice] = editLayoutFixture('invoice-edit-tds-save@example.test');

    $this->actingAs($user);

    $page = visit("/invoices/{$invoice->id}/edit")->resize(1440, 1200);

    // `text-is` rather than `has-text`: "Add Item" in the items card would
    // otherwise match too, and Playwright refuses an ambiguous selector.
    $page->click('button:text-is("Add")')
        ->waitForText('TDS (deducted)')
        ->fill('input[placeholder="e.g. 10"]', '10')
        ->fill('input[placeholder="e.g. 194J"]', '194J')
        ->click('button[type="submit"]')
        ->waitForText('Invoices');

    expect($invoice->fresh()->tds_rate)->toBe(1000)
        ->and($invoice->fresh()->tds_section)->toBe('194J');
});

/**
 * Status is teleported out of the form and into the page header. A teleport
 * that cannot find its target renders nothing at all, and says so only in a
 * development build -- which is how this shipped broken once. The assertion is
 * on where the control lands, not merely that it exists.
 */
it('puts the status control in the page header, not in the form', function () {
    [$user, $invoice] = editLayoutFixture('invoice-edit-status@example.test');

    $this->actingAs($user);

    visit("/invoices/{$invoice->id}/edit")
        ->resize(1440, 1200)
        ->assertNoJavaScriptErrors()
        ->assertScript(
            "function() {
                const label = [...document.querySelectorAll('label')]
                    .find(l => l.textContent.trim().startsWith('Status'));
                if (!label) return 'no status control at all';
                const select = document.getElementById(label.getAttribute('for'));
                if (!select) return 'label points at nothing';
                return select.closest('header') ? 'header' : 'form';
            }",
            'header',
        )
        // Document Type was a disabled select here that could never do
        // anything; the type is settled by the route that opened the page.
        ->assertDontSee('Document Type');
});

it('keeps the edit form inside the viewport on a phone', function () {
    [$user, $invoice] = editLayoutFixture('invoice-edit-mobile@example.test');

    $this->actingAs($user);

    visit("/invoices/{$invoice->id}/edit")
        ->resize(390, 844)
        ->assertNoJavaScriptErrors()
        ->assertSee('CT-INV-0042')
        ->assertScript(
            'function() { return (document.documentElement.scrollWidth - document.documentElement.clientWidth) <= 1; }',
        )
        ->screenshot(fullPage: true, filename: 'layout/invoice-edit-mobile');
});

/**
 * Create renders the same component with nothing filled in, so every change to
 * the form lands here too -- and some of them only show here. With no customer
 * chosen the recipients column is hidden, which leaves Attachments alone in a
 * two-column grid.
 */
it('renders the create form with nothing selected yet', function () {
    [$user] = editLayoutFixture('invoice-create-layout@example.test');

    $this->actingAs($user);

    visit('/invoices/create')
        ->resize(1440, 1200)
        ->assertNoJavaScriptErrors()
        ->assertSee('Parties')
        ->assertSee('Invoice Details')
        ->assertSee('Line Items')
        // Hidden until a customer is picked: there is nobody to address yet.
        ->assertDontSee('Email Recipients')
        ->assertSee('Attachments')
        ->assertScript(
            'function() { return (document.documentElement.scrollWidth - document.documentElement.clientWidth) <= 1; }',
        )
        ->screenshot(fullPage: true, filename: 'layout/invoice-create-desktop');
});

/**
 * The description grows with what is typed into it, which is a thing layout
 * tests can see and unit tests cannot: it depends on scrollHeight, on the
 * border the box-sizing adds, and on a ResizeObserver.
 *
 * Both halves nearly shipped broken. The height was set to `scrollHeight`,
 * which excludes the border while `height` includes it, so the last line was
 * clipped by two pixels and `overflow-hidden` hid the evidence. And the first
 * attempt at honouring a dragged grip compared heights to tell the user's
 * change from its own, which fed back on itself and grew one box to 13558px.
 */
it('grows the line description to fit, without clipping it', function () {
    [$user, $invoice] = editLayoutFixture('invoice-edit-description@example.test');

    $this->actingAs($user);

    visit("/invoices/{$invoice->id}/edit")
        ->resize(1440, 1200)
        ->assertNoJavaScriptErrors()
        ->assertScript(
            "function() {
                const t = document.querySelector('textarea[placeholder=\"Item description\"]');
                if (!t) return 'the description is not a textarea';
                return 'clipped=' + (t.scrollHeight - t.clientHeight)
                    + ' grip=' + getComputedStyle(t).resize;
            }",
            'clipped=0 grip=vertical',
        );
});

it('hands the description height over once the grip is dragged', function () {
    [$user, $invoice] = editLayoutFixture('invoice-edit-grip@example.test');

    $this->actingAs($user);

    visit("/invoices/{$invoice->id}/edit")
        ->resize(1440, 1200)
        ->assertScript(
            "function() {
                const t = document.querySelector('textarea[placeholder=\"Item description\"]');
                const box = t.getBoundingClientRect();

                t.dispatchEvent(new PointerEvent('pointerdown', {
                    bubbles: true, clientX: box.right - 4, clientY: box.bottom - 4,
                }));
                t.style.height = (t.offsetHeight + 60) + 'px';
                const chosen = t.offsetHeight;

                t.value += ' plus a good deal more typing that would otherwise reflow the whole box.';
                t.dispatchEvent(new Event('input', { bubbles: true }));

                return 'kept=' + (t.offsetHeight === chosen)
                    + ' overflow=' + getComputedStyle(t).overflowY;
            }",
            // Kept, and scrollable: a box the user made smaller than its text
            // must not swallow the rest of it.
            'kept=true overflow=auto',
        );
});

/**
 * The per-line tax select was eight pixels too narrow for its own labels:
 * 96px wide, with 12px of left padding and 32px reserved on the right for the
 * chevron, leaving 52px of text for a "GST 18%" that needs 60 and a
 * "Custom rate…" that needs 90. Nothing said so -- the label simply ended.
 */
it('gives the line tax select room for its longest label', function () {
    [$user, $invoice] = editLayoutFixture('invoice-edit-tax-width@example.test');

    $this->actingAs($user);

    visit("/invoices/{$invoice->id}/edit")
        ->resize(1440, 1200)
        ->assertScript(
            "function() {
                const sel = document.querySelector('tbody tr select');
                const cs = getComputedStyle(sel);
                const room = sel.getBoundingClientRect().width
                    - parseInt(cs.paddingLeft) - parseInt(cs.paddingRight);

                const probe = document.createElement('span');
                probe.style.cssText = 'position:absolute;visibility:hidden;white-space:nowrap;font:' + cs.font;
                document.body.appendChild(probe);
                const longest = [...sel.options].reduce((w, o) => {
                    probe.textContent = o.text;
                    return Math.max(w, probe.getBoundingClientRect().width);
                }, 0);
                probe.remove();

                return room >= longest;
            }",
            true,
        );
});

/**
 * A figure sits inside an input that carries its own padding, so a header
 * padded to the cell edge hangs to the right of the number beneath it. Worth
 * pinning: it is a handful of pixels, invisible until the two are compared.
 */
it('lines the numeric headers up with their own figures', function () {
    [$user, $invoice] = editLayoutFixture('invoice-edit-align@example.test');

    $this->actingAs($user);

    visit("/invoices/{$invoice->id}/edit")
        ->resize(1440, 1200)
        ->assertScript(
            "function() {
                const edge = el => {
                    const cs = getComputedStyle(el);
                    return Math.round(el.getBoundingClientRect().right - parseInt(cs.paddingRight));
                };
                const heads = [...document.querySelectorAll('thead th')];
                const head = t => heads.find(h => h.textContent.trim().startsWith(t));
                const numbers = document.querySelectorAll('tbody tr input[type=number]');

                return edge(head('Qty')) === edge(numbers[0])
                    && edge(head('Unit Price')) === edge(numbers[1]);
            }",
            true,
        );
});

/**
 * Removing a line is rare, so its control stays out of the way until the row
 * is pointed at -- and only on the row being pointed at. Opacity rather than
 * `display`, so it keeps its place in the tab order and in the accessibility
 * tree, and guarded by `hover: hover` so a touch screen, which never delivers
 * the hover that would bring it back, simply keeps it visible.
 */
it('reveals a line item\'s remove control on the row being hovered', function () {
    [$user, $invoice] = editLayoutFixture('invoice-edit-row-hover@example.test');

    $this->actingAs($user);

    $page = visit("/invoices/{$invoice->id}/edit")->resize(1440, 1200);

    $page->assertScript(
        "function() {
            const buttons = document.querySelectorAll('form tbody tr button[aria-label^=\"Remove item\"]');
            return [...buttons].every(b => getComputedStyle(b).opacity === '0');
        }",
        true,
    );

    $page->hover('form tbody tr:first-child td:first-child');

    $page->assertScript(
        "function() {
            const buttons = document.querySelectorAll('form tbody tr button[aria-label^=\"Remove item\"]');
            return getComputedStyle(buttons[0]).opacity + '/' + getComputedStyle(buttons[1]).opacity;
        }",
        // Only the hovered row's.
        '1/0',
    );
});
