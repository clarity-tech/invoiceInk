<?php

use App\Mail\DocumentMailer;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Location;
use App\ValueObjects\EmailCollection;

test('can create document mailer for invoice', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-001',
        'status' => 'draft',
        'subtotal' => 10000,
        'tax' => 1800,
        'total' => 11800,
    ]);

    $recipients = new EmailCollection(['recipient@test.com']);

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

    $recipients = new EmailCollection(['test@example.com']);

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

    $mailer = new DocumentMailer($invoice, new EmailCollection(['test@example.com']));
    $envelope = $mailer->envelope();

    expect($envelope->subject)->toBe('Invoice #INV-003');
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

    $mailer = new DocumentMailer($estimate, new EmailCollection(['test@example.com']));
    $envelope = $mailer->envelope();

    expect($envelope->subject)->toBe('Estimate #EST-001');
});

test('document mailer implements ShouldQueue', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-004',
        'status' => 'draft',
        'subtotal' => 2000,
        'tax' => 360,
        'total' => 2360,
    ]);

    $mailer = new DocumentMailer($invoice, new EmailCollection(['test@example.com']));

    expect($mailer)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

test('document mailer uses correct view for invoice', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-005',
        'status' => 'draft',
        'subtotal' => 4000,
        'tax' => 720,
        'total' => 4720,
    ]);

    $mailer = new DocumentMailer($invoice, new EmailCollection(['test@example.com']));
    $content = $mailer->content();

    expect($content->view)->toBe('emails.invoice');
});

test('document mailer uses correct view for estimate', function () {
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-002',
        'status' => 'draft',
        'subtotal' => 6000,
        'tax' => 1080,
        'total' => 7080,
    ]);

    $mailer = new DocumentMailer($estimate, new EmailCollection(['test@example.com']));
    $content = $mailer->content();

    expect($content->view)->toBe('emails.estimate');
});

test('document mailer passes correct data to view', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-006',
        'status' => 'sent',
        'subtotal' => 8000,
        'tax' => 1440,
        'total' => 9440,
    ]);

    $mailer = new DocumentMailer($invoice, new EmailCollection(['recipient@test.com']));
    $content = $mailer->content();

    expect($content->with)->toHaveKey('invoice');
    expect($content->with)->toHaveKey('viewUrl');
    expect($content->with['invoice'])->toBe($invoice);
    expect($content->with['viewUrl'])->toBeString();
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
        'billing@client.org'
    ];

    foreach ($emails as $email) {
        $recipients = new EmailCollection([$email]);
        $mailer = new DocumentMailer($invoice, $recipients);
        $envelope = $mailer->envelope();

        expect($envelope->to[0]->address)->toBe($email);
    }
});