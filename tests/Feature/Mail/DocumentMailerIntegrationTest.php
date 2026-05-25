<?php

use App\Contracts\Services\PdfServiceInterface;
use App\Mail\DocumentMailer;
use App\ValueObjects\ContactCollection;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;

// --- CC recipients tests ---

test('envelope includes cc recipients when provided', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-CC-001',
        'status' => 'sent',
    ]);

    $recipients = new ContactCollection([['name' => 'Main', 'email' => 'main@example.test']]);
    $ccEmails = ['cc1@example.test', 'cc2@example.test'];

    $mailer = new DocumentMailer($invoice, $recipients, null, $ccEmails);
    $envelope = $mailer->envelope();

    $ccAddresses = collect($envelope->cc)->map(fn (Address $addr) => $addr->address)->toArray();

    expect($ccAddresses)->toContain('cc1@example.test')
        ->and($ccAddresses)->toContain('cc2@example.test');
});

test('envelope has no cc when empty array provided', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-CC-002',
        'status' => 'sent',
    ]);

    $recipients = new ContactCollection([['name' => 'Main', 'email' => 'main@example.test']]);

    $mailer = new DocumentMailer($invoice, $recipients, null, []);
    $envelope = $mailer->envelope();

    expect($envelope->cc)->toBeEmpty();
});

// --- Custom subject tests ---

test('uses custom subject when provided', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-SUBJ-001',
        'status' => 'sent',
    ]);

    $recipients = new ContactCollection([['name' => 'Test', 'email' => 'test@example.test']]);

    $mailer = new DocumentMailer($invoice, $recipients, 'Payment Due: INV-SUBJ-001');
    $envelope = $mailer->envelope();

    expect($envelope->subject)->toBe('Payment Due: INV-SUBJ-001');
});

// --- Custom body / view selection tests ---

test('uses custom document view when custom body provided', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-BODY-001',
        'status' => 'sent',
    ]);

    $recipients = new ContactCollection([['name' => 'Test', 'email' => 'test@example.test']]);

    $mailer = new DocumentMailer($invoice, $recipients, null, [], '<p>Custom email body</p>');
    $content = $mailer->content();

    expect($content->view)->toBe('emails.custom-document')
        ->and($content->with['customBody'])->toBe('<p>Custom email body</p>')
        ->and($content->with)->toHaveKey('viewUrl');
});

test('uses default invoice view when no custom body', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-VIEW-001',
        'status' => 'sent',
    ]);

    $recipients = new ContactCollection([['name' => 'Test', 'email' => 'test@example.test']]);

    $mailer = new DocumentMailer($invoice, $recipients);
    $content = $mailer->content();

    expect($content->view)->toBe('emails.custom-document');
    expect($content->with)->toHaveKey('customBody');
});

test('uses custom-document view for estimates', function () {
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-VIEW-001',
        'status' => 'draft',
    ]);

    $recipients = new ContactCollection([['name' => 'Test', 'email' => 'test@example.test']]);

    $mailer = new DocumentMailer($estimate, $recipients);
    $content = $mailer->content();

    expect($content->view)->toBe('emails.custom-document');
    expect($content->with)->toHaveKey('customBody');
});

// --- Multiple recipients tests ---

test('sends to multiple recipients', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-MULTI-001',
        'status' => 'sent',
    ]);

    $recipients = new ContactCollection([
        ['name' => 'First', 'email' => 'first@example.test'],
        ['name' => 'Second', 'email' => 'second@example.test'],
    ]);

    $mailer = new DocumentMailer($invoice, $recipients);
    $envelope = $mailer->envelope();

    $toAddresses = collect($envelope->to)->map(fn (Address $addr) => $addr->address)->toArray();

    expect($toAddresses)->toContain('first@example.test')
        ->and($toAddresses)->toContain('second@example.test');
});

// --- Public view URL tests ---

test('generates correct public view url for invoice', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-URL-001',
        'status' => 'sent',
    ]);

    $recipients = new ContactCollection([['name' => 'Test', 'email' => 'test@example.test']]);

    $mailer = new DocumentMailer($invoice, $recipients);
    $content = $mailer->content();

    expect($content->with['viewUrl'])->toContain('/invoices/view/')
        ->and($content->with['viewUrl'])->toContain($invoice->ulid);
});

test('generates correct public view url for estimate', function () {
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-URL-001',
        'status' => 'draft',
    ]);

    $recipients = new ContactCollection([['name' => 'Test', 'email' => 'test@example.test']]);

    $mailer = new DocumentMailer($estimate, $recipients);
    $content = $mailer->content();

    expect($content->with['viewUrl'])->toContain('/estimates/view/')
        ->and($content->with['viewUrl'])->toContain($estimate->ulid);
});

// --- Attachments array test ---

test('attachments method returns empty array by default', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-ATTACH-001',
        'status' => 'sent',
    ]);

    $recipients = new ContactCollection([['name' => 'Test', 'email' => 'test@example.test']]);

    $mailer = new DocumentMailer($invoice, $recipients);

    expect($mailer->attachments())->toBeEmpty();
});

// --- PDF attachment ---

test('attachments includes a pdf when attachPdf is true', function () {
    $pdfBytes = "%PDF-1.4\n\xFFbinary-not-utf8\xFE";

    $this->mock(PdfServiceInterface::class, function ($mock) use ($pdfBytes) {
        $mock->shouldReceive('generateInvoicePdf')->once()->andReturn($pdfBytes);
    });

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-PDF-001',
        'status' => 'sent',
    ]);

    $recipients = new ContactCollection([['name' => 'Test', 'email' => 'test@example.test']]);

    $mailer = new DocumentMailer($invoice, $recipients, null, [], null, true);
    $attachments = $mailer->attachments();

    expect($attachments)->toHaveCount(1)
        ->and($attachments[0])->toBeInstanceOf(Attachment::class);
});

test('queued mailable with pdf attachment serializes without utf8 error', function () {
    $pdfBytes = "%PDF-1.4\n\xFFbinary-not-utf8\xFE";

    $this->mock(PdfServiceInterface::class, function ($mock) use ($pdfBytes) {
        $mock->shouldReceive('generateInvoicePdf')->andReturn($pdfBytes);
    });

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-PDF-Q-001',
        'status' => 'sent',
    ]);

    $recipients = new ContactCollection([['name' => 'Test', 'email' => 'test@example.test']]);
    $mailer = new DocumentMailer($invoice, $recipients, null, [], null, true);

    $serialized = serialize($mailer);
    $payload = json_encode(['data' => $serialized]);

    expect($payload)->not->toBeFalse()
        ->and(json_last_error())->toBe(JSON_ERROR_NONE);
});

// --- Full construction with all parameters ---

test('accepts all constructor parameters', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-FULL-001',
        'status' => 'sent',
    ]);

    $recipients = new ContactCollection([['name' => 'Main', 'email' => 'main@example.test']]);
    $ccEmails = ['cc@example.test'];
    $customBody = '<p>Please pay</p>';

    $mailer = new DocumentMailer(
        $invoice,
        $recipients,
        'Custom Subject',
        $ccEmails,
        $customBody
    );

    $envelope = $mailer->envelope();
    $content = $mailer->content();

    expect($envelope->subject)->toBe('Custom Subject')
        ->and($envelope->to[0]->address)->toBe('main@example.test')
        ->and(collect($envelope->cc)->first()->address)->toBe('cc@example.test')
        ->and($content->view)->toBe('emails.custom-document')
        ->and($content->with['customBody'])->toBe('<p>Please pay</p>');
});
