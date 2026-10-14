<?php

use App\Enums\InvoiceStatus;
use App\Support\Branding;

test('public invoice template renders with all new elements', function () {
    $organization = createOrganizationWithLocation([
        'bank_details' => [
            'account_name' => 'Test Company',
            'account_number' => '1234567890',
            'bank_name' => 'Test Bank',
            'ifsc' => 'TEST0001',
            'branch' => 'Main',
            'swift' => 'TESTSWIFT',
            'pan' => 'ABCDE1234F',
        ],
    ], [
        'tax_id' => '18AAACC1234C1Z5',
    ]);

    $customer = createCustomerWithLocation([], [
        'tax_id' => '36AAACR5678R1ZQ',
        'state' => 'Telangana',
    ], $organization);

    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'notes' => 'Test invoice notes',
        'terms' => 'Payment due within 30 days',
        'tax_type' => 'IGST',
        'place_of_supply' => '36', // Telangana
        'order_number' => 'PO-2026-4471',
    ], null, $organization, $customer);

    $response = $this->get($invoice->shareUrl());

    $response->assertStatus(200);
    $response->assertSee('TAX INVOICE');
    $response->assertSee('Balance Due');
    $response->assertSee('Total In Words');
    $response->assertSee('Test invoice notes');
    $response->assertSee('Test Bank');
    $response->assertSee('Account No:');
    $response->assertSee('IFSC TEST0001');
    $response->assertSee('SWIFT Code TESTSWIFT');
    $response->assertSee('PAN ABCDE1234F');
    $response->assertSee('Terms & Conditions');
    $response->assertSee('Payment due within 30 days');
    $response->assertSee('Place of Supply');
    $response->assertSee('Telangana');
    $response->assertSee('IGST');
    $response->assertSee('Order Number');
    $response->assertSee('PO-2026-4471');
});

test('public invoice template renders INVOICE header when no GSTIN', function () {
    $organization = createOrganizationWithLocation([], [
        'tax_id' => null,
    ]);
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
    ], null, $organization, $customer);

    $response = $this->get($invoice->shareUrl());

    $response->assertStatus(200);
    $response->assertSee('INVOICE');
    $response->assertDontSee('TAX INVOICE');
});

test('public invoice renders TRN label for UAE organization location', function () {
    $organization = createOrganizationWithLocation([
        'currency' => 'AED',
        'country_code' => 'AE',
    ], [
        'country' => 'AE',
        'tax_id' => '100123456700003',
    ]);
    $customer = createCustomerWithLocation([
        'currency' => 'AED',
    ], [
        'country' => 'AE',
        'tax_id' => '100987654300003',
    ], $organization);
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
    ], null, $organization, $customer);

    $response = $this->get($invoice->shareUrl());

    $response->assertStatus(200);
    $response->assertSee('TRN 100123456700003', false);
    $response->assertSee('TRN 100987654300003', false);
    $response->assertDontSee('GSTIN');
});

test('public invoice does not show TAX INVOICE header for non-Indian organization even with a tax id', function () {
    $organization = createOrganizationWithLocation([
        'currency' => 'AED',
        'country_code' => 'AE',
    ], [
        'country' => 'AE',
        'tax_id' => '100123456700003',
    ]);
    $customer = createCustomerWithLocation([
        'currency' => 'AED',
    ], [], $organization);
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
    ], null, $organization, $customer);

    $response = $this->get($invoice->shareUrl());

    $response->assertStatus(200);
    $response->assertSee('INVOICE');
    $response->assertDontSee('TAX INVOICE');
});

test('public invoice hides Place of Supply for non-Indian customer', function () {
    $organization = createOrganizationWithLocation([
        'currency' => 'AED',
        'country_code' => 'AE',
    ], [
        'country' => 'AE',
    ]);
    $customer = createCustomerWithLocation([
        'currency' => 'AED',
    ], [
        'country' => 'AE',
        'state' => 'Dubai',
    ], $organization);
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
    ], null, $organization, $customer);

    $response = $this->get($invoice->shareUrl());

    $response->assertStatus(200);
    $response->assertDontSee('Place of Supply');
});

test('public invoice template hides bank details when not configured', function () {
    $organization = createOrganizationWithLocation([
        'bank_details' => null,
    ]);
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
    ], null, $organization, $customer);

    $response = $this->get($invoice->shareUrl());

    $response->assertStatus(200);
    $response->assertDontSee('Account No:');
});

test('public invoice template renders tax breakdown when available', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'tax_type' => 'GST',
        'tax_breakdown' => ['CGST (9%)' => 900, 'SGST (9%)' => 900],
    ], null, $organization, $customer);

    $response = $this->get($invoice->shareUrl());

    $response->assertStatus(200);
    $response->assertSee('CGST (9%)');
    $response->assertSee('SGST (9%)');
});

test('public estimate template renders with Zoho style', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'notes' => 'Estimate notes',
        'terms' => 'Valid for 30 days',
    ], null, $organization, $customer);

    $response = $this->get($estimate->shareUrl());

    $response->assertStatus(200);
    $response->assertSee('ESTIMATE');
    $response->assertSee('Estimated Total');
    $response->assertSee('Total In Words');
    $response->assertSee('Estimate notes');
    $response->assertSee('Terms & Conditions');
    $response->assertSee('Valid for 30 days');
});

test('total in words displays correctly for INR invoice', function () {
    $organization = createOrganizationWithLocation([
        'currency' => 'INR',
    ]);
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'total' => 4720000,
        'currency' => 'INR',
    ], null, $organization, $customer);

    $response = $this->get($invoice->shareUrl());

    $response->assertStatus(200);
    $response->assertSee('Indian Rupee Forty-Seven Thousand Two Hundred Only');
});

test('the band overlays the document without moving it', function () {
    // It is an overlay. Nothing reserves room for it, so the document sits
    // exactly where it would without a band -- the band crosses the logo
    // rather than pushing it down, as the reference does. Earlier revisions
    // reserved header space and the gap was visible above the logo.
    $organization = createOrganizationWithLocation(['setup_completed_at' => now()]);
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems(['status' => 'draft'], null, $organization, $customer);

    expect(view('public.invoice', ['invoice' => $invoice, 'pdfUrl' => $invoice->sharePdfUrl(now()->addDay()), 'linkExpiresAt' => now()->addDay()])->render())
        ->toContain('<div class="doc-ribbon no-print')
        ->not->toContain('.doc-header > div:first-child');
});

test('every status is drawn as a band on the preview, coloured by what the status means', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems([], null, $organization, $customer);

    foreach (InvoiceStatus::cases() as $status) {
        // Reassigned rather than re-created: only the rendered status varies.
        $invoice->status = $status;

        expect(view('public.invoice', ['invoice' => $invoice, 'pdfUrl' => $invoice->sharePdfUrl(now()->addDay()), 'linkExpiresAt' => now()->addDay()])->render())
            ->toContain('<div class="doc-ribbon no-print doc-ribbon-'.$status->color().'">')
            ->toContain('<span>'.$status->label().'</span>');
    }
});

test('the pdf carries no status band at all', function () {
    // The band belongs to the preview. What the customer receives says nothing
    // about whether the document is a draft, paid or void -- and the PDF keeps
    // the layout it had before the band existed, with nothing reserved for it.
    // One status is enough: the PDF branch discards the status before anything
    // reads it, so no status can produce a different answer.
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems(['status' => 'paid'], null, $organization, $customer);

    expect(view('pdf.invoice', ['invoice' => $invoice])->render())
        ->not->toContain('doc-ribbon');
});

test('the public page frames the document with the sender, the balance and the actions', function () {
    // The card is a facsimile of the PDF, so the things a recipient needs that
    // are not part of the document itself -- who sent it, what is outstanding,
    // and how to print or save it -- belong to the page around it.
    $organization = createOrganizationWithLocation(['company_name' => 'Clarity Technologies']);
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-25219',
        'due_at' => now()->addDays(10),
    ], null, $organization, $customer);

    $response = $this->get($invoice->shareUrl());
    $response->assertStatus(200);

    // Scoped to the chrome: the number, the balance and the date all appear in
    // the document too, so asserting against the whole page would pass just as
    // happily with the chrome deleted.
    preg_match('/<div class="doc-chrome.*?(?=<div class="page-wrapper")/s', $response->getContent(), $matches);
    $chrome = $matches[0] ?? '';

    expect($chrome)
        ->toContain('Clarity Technologies')
        ->toContain('INV-25219')
        ->toContain($invoice->due_at->format('d/m/Y'))
        ->toContain($invoice->formatted_remaining_balance)
        // Icon-only buttons, so the label has to reach a screen reader some
        // other way.
        ->toContain('aria-label="'.__('actions.buttons.download_pdf').'"')
        ->toContain('aria-label="'.__('actions.buttons.print_invoice').'"');
});

test('the chrome names the sender exactly as the document does', function () {
    // Two different company names on one page is a reason for a recipient to
    // doubt the invoice. The bar used to render the organization's short name
    // while the document rendered its registered one.
    $organization = createOrganizationWithLocation([
        'name' => 'Clarity Tech',
        'company_name' => 'Clarity Technologies Private Limited',
    ]);
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems([], null, $organization, $customer);

    $html = view('public.invoice', ['invoice' => $invoice, 'pdfUrl' => $invoice->sharePdfUrl(now()->addDay()), 'linkExpiresAt' => now()->addDay()])->render();

    expect($html)
        ->toContain('<span class="doc-sender">Clarity Technologies Private Limited</span>')
        ->toContain('<div class="company-name">Clarity Technologies Private Limited</div>');
});

test('the pdf carries none of the page chrome', function () {
    // Same template, so the chrome has to be fenced off from the PDF branch as
    // firmly as the status band is -- a stray brand bar would print.
    $organization = createOrganizationWithLocation(['name' => 'Clarity Technologies']);
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems([], null, $organization, $customer);

    expect(view('pdf.invoice', ['invoice' => $invoice])->render())
        ->not->toContain('doc-chrome')
        ->not->toContain('doc-brandbar')
        ->not->toContain('doc-summary')
        ->not->toContain('doc-pagefoot')
        ->not->toContain('icon-btn');
});

test('the page keeps a linked platform lockup along the bottom of the window', function () {
    // Drawn from the same places the document's own footer draws from -- the
    // translation, the branding config and the attribution helper -- so none
    // of it is written out twice and the link can be told apart from the one
    // printed inside the document.
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems([], null, $organization, $customer);

    $html = view('public.invoice', ['invoice' => $invoice, 'pdfUrl' => $invoice->sharePdfUrl(now()->addDay()), 'linkExpiresAt' => now()->addDay()])->render();

    preg_match('/<div class="doc-pagefoot.*?<\/div>\s*<\/div>/s', $html, $matches);
    $footer = $matches[0] ?? '';

    expect($footer)
        ->toContain(__('messages.email.powered_by'))
        ->toContain(config('branding.tagline'))
        ->toContain('href="'.e(Branding::attributedUrl('web', 'page_footer')).'"')
        // A placement of its own: the document's footer link is tagged
        // powered_by, so referrals from the two are distinguishable.
        ->not->toContain('utm_content=powered_by');
});
