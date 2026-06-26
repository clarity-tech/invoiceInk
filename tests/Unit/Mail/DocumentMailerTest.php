<?php

use App\Mail\DocumentMailer;
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

test('document mailer implements ShouldQueue', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-004',
        'status' => 'draft',
        'subtotal' => 2000,
        'tax' => 360,
        'total' => 2360,
    ]);

    $mailer = new DocumentMailer($invoice, new ContactCollection([['name' => 'Test', 'email' => 'test@example.com']]));

    expect($mailer)->toBeInstanceOf(ShouldQueue::class);
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

test('document mailer renders the platform logo and tagline in the footer branding area', function () {
    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-BRAND-001']);

    $mailer = new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']]));
    $html = $mailer->renderHtml();

    expect($html)->toContain(config('branding.logo'))             // hosted logo URL reference
        ->and($html)->toContain(config('branding.tagline'))       // centralized footer tagline
        ->and($html)->toContain('class="footer-brand"');          // footer lockup wrapper
});

test('document mailer renders the brand logo lockup image in header and footer', function () {
    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-BRAND-WM']);

    $mailer = new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']]));
    $html = $mailer->renderHtml();

    // The combined InvoiceInk logo lockup (mark + wordmark) is rendered as a
    // single image in both the header and the footer "powered by" lockup,
    // referenced by hosted URL (not attached) so it never shows as an
    // attachment in the recipient's mail client.
    expect(substr_count($html, asset(config('branding.logo'))))->toBeGreaterThanOrEqual(2);
});

test('document mailer references the brand logo by hosted url without attaching it', function () {
    config(['mail.default' => 'array', 'queue.default' => 'sync']);

    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-CID-001']);

    Mail::send(new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']])));

    $email = Mail::mailer('array')->getSymfonyTransport()->messages()->first()->getOriginalMessage();

    $attachmentTypes = collect($email->getAttachments())->map->getContentType()->all();

    // Logo is referenced by absolute URL, not embedded — so the message carries
    // no image attachment (which would otherwise surface in the client's
    // attachment list).
    expect($email->getHtmlBody())->toContain(asset(config('branding.logo')))
        ->and($attachmentTypes)->not->toContain('image/png');
});

test('document mailer falls back to the embedded icon + html wordmark when logo_external is off', function () {
    config(['branding.logo_external' => false]);

    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-EMBED-001']);

    $html = (new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']])))->renderHtml();

    // Embedded mode: CID icon mark + two-tone HTML wordmark in header and footer,
    // and no external lockup URL.
    expect(substr_count($html, 'src="cid:'.config('branding.logo_cid').'"'))->toBeGreaterThanOrEqual(2)
        ->and(substr_count($html, 'Invoice<span style="color: #7c3aed;">Ink</span>'))->toBeGreaterThanOrEqual(2)
        ->and($html)->not->toContain(asset(config('branding.logo')));
});

test('document mailer attaches the icon mark only in embedded mode', function () {
    config(['branding.logo_external' => false, 'mail.default' => 'array', 'queue.default' => 'sync']);

    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-EMBED-002']);

    Mail::send(new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']])));

    $email = Mail::mailer('array')->getSymfonyTransport()->messages()->first()->getOriginalMessage();
    $attachmentTypes = collect($email->getAttachments())->map->getContentType()->all();

    // Embedded mode re-attaches the small mark under the brand CID.
    expect($attachmentTypes)->toContain('image/png');
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
