<?php

use App\Jobs\SendDocumentEmail;
use App\Mail\DocumentMailer;
use App\Support\Branding;
use App\ValueObjects\ContactCollection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

test('can create document mailer for invoice', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-001',
        'status' => 'draft',
        'subtotal' => 10000,
        'tax' => 1800,
        'total' => 11800,
    ]);

    $recipients = new ContactCollection([['name' => 'Recipient', 'email' => 'recipient@test.com']]);

    $mailer = new DocumentMailer($invoice, $recipients);

    expect($mailer)->toBeInstanceOf(DocumentMailer::class);
});

test('document mailer builds correctly for invoice', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-002',
        'status' => 'draft',
        'subtotal' => 5000,
        'tax' => 900,
        'total' => 5900,
    ]);

    $recipients = new ContactCollection([['name' => 'Test', 'email' => 'test@example.com']]);

    $mailer = new DocumentMailer($invoice, $recipients);
    $envelope = $mailer->envelope();

    expect($envelope->to[0]->address)->toBe('test@example.com');
});

test('document mailer has correct subject for invoice', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-003',
        'status' => 'draft',
        'subtotal' => 7500,
        'tax' => 1350,
        'total' => 8850,
    ]);

    $mailer = new DocumentMailer($invoice, new ContactCollection([['name' => 'Test', 'email' => 'test@example.com']]));
    $envelope = $mailer->envelope();

    expect($envelope->subject)->toBe('Invoice #INV-003 - Sent Using '.config('app.name'));
});

test('document mailer has correct subject for estimate', function () {
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-001',
        'status' => 'draft',
        'subtotal' => 3000,
        'tax' => 540,
        'total' => 3540,
    ]);

    $mailer = new DocumentMailer($estimate, new ContactCollection([['name' => 'Test', 'email' => 'test@example.com']]));
    $envelope = $mailer->envelope();

    expect($envelope->subject)->toBe('Estimate #EST-001 - Sent Using '.config('app.name'));
});

test('document mailer omits the subject attribution tag when disabled', function () {
    config(['branding.subject_tag' => false]);

    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-NOTAG']);

    $envelope = (new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']])))->envelope();

    expect($envelope->subject)->toBe('Invoice #INV-NOTAG')
        ->and($envelope->subject)->not->toContain('Sent Using');
});

test('document mailer subject tag honours custom attribution text', function () {
    config(['branding.subject_tag' => true, 'branding.subject_tag_text' => 'via Acme Billing']);

    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-CUSTOMTAG']);

    $envelope = (new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']])))->envelope();

    expect($envelope->subject)->toBe('Invoice #INV-CUSTOMTAG - via Acme Billing');
});

test('document mailer is not queued itself, so the job owns the outcome', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-004',
        'status' => 'draft',
        'subtotal' => 2000,
        'tax' => 360,
        'total' => 2360,
    ]);

    $mailer = new DocumentMailer($invoice, new ContactCollection([['name' => 'Test', 'email' => 'test@example.com']]));

    // Queueing moved to SendDocumentEmail so a single place records whether the
    // send succeeded. If the Mailable were queued again, Mail::send() inside the
    // job would re-queue instead of sending, and no outcome would ever be written.
    expect($mailer)->not->toBeInstanceOf(ShouldQueue::class)
        ->and(SendDocumentEmail::class)->toImplement(ShouldQueue::class);
});

test('document mailer content is pre-rendered html, not a view reference', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-005',
        'status' => 'draft',
        'subtotal' => 4000,
        'tax' => 720,
        'total' => 4720,
    ]);

    $mailer = new DocumentMailer($invoice, new ContactCollection([['name' => 'Test', 'email' => 'test@example.com']]));
    $content = $mailer->content();

    expect($content->view)->toBeNull()
        ->and($content->htmlString)->toBeString()
        ->and($content->htmlString)->toContain('INV-005');
});

test('document mailer renders inlined html for invoice', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-005',
        'status' => 'draft',
        'subtotal' => 4000,
        'tax' => 720,
        'total' => 4720,
    ]);

    $mailer = new DocumentMailer($invoice, new ContactCollection([['name' => 'Test', 'email' => 'test@example.com']]));
    $html = $mailer->renderHtml();

    expect($html)->toContain('INV-005')
        ->and($html)->toContain('class="email-body"');
});

test('document mailer renders inlined html for estimate', function () {
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-002',
        'status' => 'draft',
        'subtotal' => 6000,
        'tax' => 1080,
        'total' => 7080,
    ]);

    $mailer = new DocumentMailer($estimate, new ContactCollection([['name' => 'Test', 'email' => 'test@example.com']]));
    $html = $mailer->renderHtml();

    expect($html)->toContain('EST-002');
});

test('document mailer inlines stylesheet rules as inline styles', function () {
    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-INLINE-001']);

    $mailer = new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']]));
    $html = $mailer->renderHtml();

    // The CTA button colour lives only in the <style> block; after inlining it
    // must appear as an inline style attribute (what Gmail actually honours).
    expect($html)->toMatch('/style="[^"]*background-color:\s*#7c3aed/i')
        // First-child margin reset is inlined onto the leading body element.
        ->and($html)->toMatch('/style="[^"]*margin-top:\s*0/i')
        // bgcolor attribute is kept for clients that ignore CSS backgrounds.
        ->and($html)->toContain('bgcolor="#f4f4f4"');
});

test('document mailer renders invoice number and public url into html', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-006',
        'status' => 'sent',
        'subtotal' => 8000,
        'tax' => 1440,
        'total' => 9440,
    ]);

    $mailer = new DocumentMailer($invoice, new ContactCollection([['name' => 'Recipient', 'email' => 'recipient@test.com']]));
    $html = $mailer->renderHtml();

    expect($html)->toContain('INV-006')
        ->and($html)->toContain('/invoices/view/')
        ->and($html)->toContain($invoice->ulid);
});

test('document mailer includes the view-online button by default', function () {
    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-CTA-DEFAULT']);
    $mailer = new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']]));

    // class="cta-button" only appears on the anchor element; the `.cta-button`
    // rule in the retained <style> block must not be mistaken for it.
    expect($mailer->renderHtml())->toContain('class="cta-button"');
});

test('document mailer omits the view-online button when disabled', function () {
    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-CTA-OFF']);
    $mailer = new DocumentMailer(
        $invoice,
        new ContactCollection([['name' => 'R', 'email' => 'r@test.com']]),
        null,
        [],
        null,
        false,
        [],
        false,
    );

    expect($mailer->renderHtml())->not->toContain('class="cta-button"');
});

test('document mailer renders the platform wordmark and tagline in the footer branding area', function () {
    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-BRAND-001']);

    $mailer = new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']]));
    $html = $mailer->renderHtml();

    // Footer lockup is the embedded ink-drop mark (CID image) + the "InvoiceInk"
    // HTML wordmark, alongside the centralized tagline.
    expect($html)->toContain('src="cid:'.config('branding.logo_cid').'"') // embedded mark
        ->and($html)->toContain(config('branding.tagline'))               // centralized footer tagline
        ->and($html)->toContain('class="footer-brand"');                  // footer lockup wrapper
});

test('document mailer footer renders the organization country name and second address line, mirroring the PDF', function () {
    $organization = createOrganizationWithLocation([], [
        'address_line_1' => '221B Baker Street',
        'address_line_2' => 'Suite 4',
        'city' => 'London Town',
        'state' => 'Greater Region',
        'country' => 'IN',
        'postal_code' => 'AB1 2CD',
    ]);
    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-FOOTER-ADDR'], null, $organization);

    $html = (new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']])))->renderHtml();

    // The footer address mirrors the PDF/web view: the ISO country code is
    // expanded to its full name (IN -> India) and address_line_2 is included.
    expect($html)->toContain('India')
        ->and($html)->toContain('Suite 4');
});

test('document mailer makes the header brand logo a clickable utm-tagged link', function () {
    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-BRAND-LINK']);

    $html = (new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']])))->renderHtml();

    // The header lockup is a clickable, UTM-tagged platform link. The email
    // channel is the utm_source and it carries its own placement-specific
    // utm_content (brand_header) rather than the footer's "powered_by".
    $headerUrl = e(Branding::attributedUrl('email', 'brand_header'));

    expect($html)->toContain('href="'.$headerUrl.'" class="header-brand"')
        ->and($headerUrl)->toContain('utm_source=email')
        ->and($headerUrl)->toContain('utm_content=brand_header')
        ->and($headerUrl)->not->toContain('powered_by');
});

test('document mailer renders the embedded icon + wordmark lockup in header and footer', function () {
    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-BRAND-WM']);

    $mailer = new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']]));
    $html = $mailer->renderHtml();

    // The lockup is the CID ink-drop mark + a live-text "InvoiceInk" wordmark in
    // both the header and the footer "powered by" lockup. The wordmark is HTML
    // text (not a baked PNG), so the dark "Invoice" half stays legible in Gmail
    // dark mode instead of dropping out as flat-image pixels would.
    expect(substr_count($html, 'src="cid:'.config('branding.logo_cid').'"'))->toBeGreaterThanOrEqual(2)
        ->and(substr_count($html, 'class="brand-ink"'))->toBeGreaterThanOrEqual(2);
});

test('document mailer embeds the brand mark inline and never references the hosted lockup', function () {
    config(['mail.default' => 'array', 'queue.default' => 'sync']);

    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-CID-001']);

    Mail::send(new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']])));

    $email = Mail::mailer('array')->getSymfonyTransport()->messages()->first()->getOriginalMessage();

    $attachmentTypes = collect($email->getAttachments())->map->getContentType()->all();

    // The invoice email always inlines the ink-drop mark under the brand CID and
    // renders the wordmark as HTML text; it never falls back to the hosted PNG
    // lockup, which is not dark-mode safe.
    expect($email->getHtmlBody())->toContain('cid:'.config('branding.logo_cid'))
        ->and($email->getHtmlBody())->not->toContain(asset(config('branding.logo')))
        ->and($attachmentTypes)->toContain('image/png');
});

test('document mailer always renders the embedded lockup even when logo_external is on', function () {
    // The invoice document email is intentionally decoupled from the global
    // logo_external switch: it always uses the dark-mode-safe embedded lockup so
    // it stays legible in Gmail dark mode, regardless of the platform default.
    config(['branding.logo_external' => true]);

    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-EMBED-001']);

    $html = (new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']])))->renderHtml();

    expect(substr_count($html, 'src="cid:'.config('branding.logo_cid').'"'))->toBeGreaterThanOrEqual(2)
        ->and(substr_count($html, 'class="brand-ink"'))->toBeGreaterThanOrEqual(2)
        ->and($html)->not->toContain(asset(config('branding.logo')));
});

test('document mailer always attaches the icon mark as an inline image', function () {
    config(['branding.logo_external' => true, 'mail.default' => 'array', 'queue.default' => 'sync']);

    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-EMBED-002']);

    Mail::send(new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']])));

    $email = Mail::mailer('array')->getSymfonyTransport()->messages()->first()->getOriginalMessage();
    $attachmentTypes = collect($email->getAttachments())->map->getContentType()->all();

    // The mark rides along as an inline CID image even with logo_external on.
    expect($attachmentTypes)->toContain('image/png');
});

test('document mailer ships a dark-mode stylesheet without leaking dark styles into the light render', function () {
    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-DARK-001']);

    $html = (new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']])))->renderHtml();

    // Dark support is declared: the color-scheme meta and the prefers-color-scheme
    // media block survive CSS inlining as a <style> block.
    expect($html)->toContain('name="color-scheme" content="light dark"')
        ->and($html)->toContain('prefers-color-scheme: dark');

    // The dark card color must live ONLY inside that media block. The inliner
    // strips @media rules before inlining, so if it appeared more than once it
    // would mean a dark style leaked onto an element and darkened light mode too.
    expect(substr_count($html, '#1e1e1e'))->toBe(1);
});

test('document mailer surfaces the brand slogan in the preheader and header', function () {
    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-BRAND-002']);

    $mailer = new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']]));
    $html = $mailer->renderHtml();

    // Slogan appears in both the hidden preheader and the slim platform header.
    expect(substr_count($html, (string) config('branding.slogan')))->toBeGreaterThanOrEqual(2);
});

test('platform header is rendered outside the customer body block', function () {
    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-BRAND-003']);

    $mailer = new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']]));
    $html = $mailer->renderHtml();

    // The platform header must precede .email-body so it never shifts the
    // positional selectors that style the org name / invoice banner.
    expect(strpos($html, 'class="email-header"'))->toBeLessThan(strpos($html, 'class="email-body"'));
});

test('document mailer handles different recipient emails', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-007',
        'status' => 'draft',
        'subtotal' => 1500,
        'tax' => 270,
        'total' => 1770,
    ]);

    $emails = [
        'user1@test.com',
        'admin@company.com',
        'billing@client.org',
    ];

    foreach ($emails as $email) {
        $recipients = new ContactCollection([['name' => 'Test User', 'email' => $email]]);
        $mailer = new DocumentMailer($invoice, $recipients);
        $envelope = $mailer->envelope();

        expect($envelope->to[0]->address)->toBe($email);
    }
});
