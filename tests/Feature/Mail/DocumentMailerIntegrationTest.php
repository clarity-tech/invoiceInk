<?php

use App\Contracts\Services\PdfServiceInterface;
use App\Mail\DocumentMailer;
use App\ValueObjects\ContactCollection;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Storage;

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

    expect($envelope->subject)->toBe('Payment Due: INV-SUBJ-001 - Sent Using '.config('app.name'));
});

// --- Custom body / view selection tests ---

test('renders custom body into the email html when provided', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-BODY-001',
        'status' => 'sent',
    ]);

    $recipients = new ContactCollection([['name' => 'Test', 'email' => 'test@example.test']]);

    $mailer = new DocumentMailer($invoice, $recipients, null, [], '<p>Custom email body</p>');
    $html = $mailer->renderHtml();

    expect($html)->toContain('Custom email body')
        ->and($html)->toContain('/invoices/view/');
});

test('renders the default invoice body when no custom body', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-VIEW-001',
        'status' => 'sent',
    ]);

    $recipients = new ContactCollection([['name' => 'Test', 'email' => 'test@example.test']]);

    $mailer = new DocumentMailer($invoice, $recipients);
    $html = $mailer->renderHtml();

    expect($html)->toContain('INV-VIEW-001')
        ->and($html)->toContain('Thank you for your business');
});

test('renders the default estimate body for estimates', function () {
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-VIEW-001',
        'status' => 'draft',
    ]);

    $recipients = new ContactCollection([['name' => 'Test', 'email' => 'test@example.test']]);

    $mailer = new DocumentMailer($estimate, $recipients);
    $html = $mailer->renderHtml();

    expect($html)->toContain('EST-VIEW-001')
        ->and($html)->toContain('Thank you for considering our services');
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
    $html = $mailer->renderHtml();

    expect($html)->toContain('/invoices/view/')
        ->and($html)->toContain($invoice->ulid);
});

test('generates correct public view url for estimate', function () {
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-URL-001',
        'status' => 'draft',
    ]);

    $recipients = new ContactCollection([['name' => 'Test', 'email' => 'test@example.test']]);

    $mailer = new DocumentMailer($estimate, $recipients);
    $html = $mailer->renderHtml();

    expect($html)->toContain('/estimates/view/')
        ->and($html)->toContain($estimate->ulid);
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

test('attachments include extra files from storage when provided', function () {
    Storage::fake('local');
    Storage::disk('local')->put('email-attachments/test/contract.pdf', 'fake-contract');

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-EXTRA-001',
        'status' => 'sent',
    ]);

    $recipients = new ContactCollection([['name' => 'Test', 'email' => 'test@example.test']]);

    $mailer = new DocumentMailer(
        $invoice,
        $recipients,
        null,
        [],
        null,
        false,
        [[
            'disk' => 'local',
            'path' => 'email-attachments/test/contract.pdf',
            'name' => 'contract.pdf',
            'mime' => 'application/pdf',
        ]],
    );

    $attachments = $mailer->attachments();

    expect($attachments)->toHaveCount(1)
        ->and($attachments[0])->toBeInstanceOf(Attachment::class);
});

test('attachments include both pdf and extra files when both are set', function () {
    Storage::fake('local');
    Storage::disk('local')->put('email-attachments/test/notes.txt', 'notes');

    $this->mock(PdfServiceInterface::class, function ($mock) {
        $mock->shouldReceive('generateInvoicePdf')->once()->andReturn('pdf-bytes');
    });

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-BOTH-001',
        'status' => 'sent',
    ]);

    $recipients = new ContactCollection([['name' => 'Test', 'email' => 'test@example.test']]);

    $mailer = new DocumentMailer(
        $invoice,
        $recipients,
        null,
        [],
        null,
        true,
        [[
            'disk' => 'local',
            'path' => 'email-attachments/test/notes.txt',
            'name' => 'notes.txt',
            'mime' => 'text/plain',
        ]],
    );

    expect($mailer->attachments())->toHaveCount(2);
});

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
    $html = $mailer->renderHtml();

    expect($envelope->subject)->toBe('Custom Subject - Sent Using '.config('app.name'))
        ->and($envelope->to[0]->address)->toBe('main@example.test')
        ->and(collect($envelope->cc)->first()->address)->toBe('cc@example.test')
        ->and($html)->toContain('Please pay');
});
