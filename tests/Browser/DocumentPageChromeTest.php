<?php

use App\Models\User;

/**
 * The public document page frames the sheet with the sender, the balance and
 * the actions. Those belong to the page, not to the document, so they are
 * drawn around the card rather than on it -- and they stay put while a long
 * invoice scrolls underneath.
 */
it('frames the document with chrome that stays put while the document scrolls', function () {
    $screenshotDir = base_path('tests/Browser/Screenshots/layout');
    if (! is_dir($screenshotDir)) {
        mkdir($screenshotDir, 0755, true);
    }

    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'page-chrome@example.test',
    ]);

    $org = createOrganizationWithLocation([
        'company_name' => 'Clarity Technologies',
        'currency' => 'INR',
        'country_code' => 'IN',
    ], ['tax_id' => '18AAACC1234C1Z5'], $user);

    $customer = createCustomerWithLocation(['name' => 'Sandline Pharmacy LLC'], [], $org);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'CT-INV-CHROME',
        'status' => 'sent',
        'currency' => 'INR',
        'due_at' => now()->addDays(10),
    ], null, $org, $customer);

    $page = visit("/invoices/view/{$invoice->ulid}")->resize(1280, 900);

    $page->assertNoJavaScriptErrors()
        ->assertSee('Clarity Technologies')
        ->assertSee('CT-INV-CHROME')
        // One sender, named once. The bar and the document's own header have
        // to agree, or the page shows a recipient two company names.
        ->assertScript(
            'function() { var bar = document.querySelector(".doc-brandbar .doc-chrome-inner").textContent.trim(); var doc = document.querySelector(".company-name").textContent.trim(); return bar === doc ? "agree" : bar + " != " + doc; }',
            'agree',
        )
        // The chrome shares the sheet's column, so it frames the document
        // instead of floating over the window.
        ->assertScript(
            'function() { var inner = document.querySelector(".doc-brandbar .doc-chrome-inner").getBoundingClientRect(); var wrap = document.querySelector(".page-wrapper").getBoundingClientRect(); return Math.abs(inner.left - wrap.left) < 2 ? "aligned" : "off by " + Math.round(inner.left - wrap.left); }',
            'aligned',
        )
        // The lockup rides the bottom of the window, not the bottom of the
        // document -- it is still there with a long invoice scrolled past it.
        ->assertScript(
            'function() { window.scrollTo(0, 600); var f = document.querySelector(".doc-pagefoot").getBoundingClientRect(); var pinned = Math.abs(f.bottom - window.innerHeight) < 2; window.scrollTo(0, 0); return pinned ? "pinned" : "drifted"; }',
            'pinned',
        )
        // Pinned: the whole point is that the balance and the download button
        // are still there when you have scrolled to the bank details.
        ->assertScript(
            'function() { window.scrollTo(0, 900); var top = Math.round(document.querySelector(".doc-brandbar").getBoundingClientRect().top); window.scrollTo(0, 0); return top === 0 ? "pinned" : "drifted to " + top; }',
            'pinned',
        );

    // Screenshots are taken on a viewport tall enough to hold the whole page.
    // A full-page capture of a shorter one freezes the sticky chrome at its
    // pinned position, which lands it halfway down the image.
    visit("/invoices/view/{$invoice->ulid}")
        ->resize(1280, 1500)
        ->screenshot(filename: 'layout/document-page-chrome-desktop');

    // Narrow: the figures and the actions share the width -- wrapping onto a
    // second line if they must -- rather than pushing the page sideways.
    visit("/invoices/view/{$invoice->ulid}")
        ->resize(414, 900)
        ->assertSee('Clarity Technologies')
        ->assertScript(
            'function() { return document.documentElement.scrollWidth <= window.innerWidth + 1 ? "contained" : "overflows by " + (document.documentElement.scrollWidth - window.innerWidth); }',
            'contained',
        )
        ->assertScript(
            'function() { var a = document.querySelector(".doc-summary-actions").getBoundingClientRect(); return (a.right <= window.innerWidth + 1 && a.width > 0) ? "reachable" : "clipped"; }',
            'reachable',
        )
        // Scrolls away on a phone rather than holding a fifth of the screen
        // for the whole document.
        ->assertScript(
            'function() { return getComputedStyle(document.querySelector(".doc-chrome")).position; }',
            'static',
        )
        ->assertScript(
            'function() { return getComputedStyle(document.querySelector(".doc-pagefoot")).position; }',
            'sticky',
        );

    visit("/invoices/view/{$invoice->ulid}")
        ->resize(414, 1800)
        ->screenshot(filename: 'layout/document-page-chrome-mobile');
});

it('keeps the chrome off the printed page', function () {
    // Printing the preview should give the document and nothing else: the
    // chrome is marked no-print, like the status band.
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'page-chrome-print@example.test',
    ]);

    $org = createOrganizationWithLocation(['name' => 'Clarity Technologies'], [], $user);
    $customer = createCustomerWithLocation([], [], $org);
    $invoice = createInvoiceWithItems([], null, $org, $customer);

    visit("/invoices/view/{$invoice->ulid}")
        ->assertScript(
            'function() { var chrome = document.querySelector(".doc-chrome").classList.contains("no-print"); var foot = document.querySelector(".doc-pagefoot").classList.contains("no-print"); return (chrome && foot) ? "hidden" : "prints"; }',
            'hidden',
        );
});
