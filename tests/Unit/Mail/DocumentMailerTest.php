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

    $recipientEmail = 'recipient@test.com';

    $mailer = new DocumentMailer($invoice, $recipientEmail);

    expect($mailer)->toBeInstanceOf(DocumentMailer::class);
});

test('document mailer builds correctly for invoice', function () {
    $invoice = Invoice::create([
        'type' => 'invoice',
        'company_location_id' => 1,
        'customer_location_id' => 2,
        'invoice_number' => 'INV-002',
        'status' => 'draft',
        'subtotal' => 5000,
        'tax' => 900,
        'total' => 5900,
    ]);

    $recipientEmail = 'test@example.com';

    $mailer = new DocumentMailer($invoice, $recipientEmail);
    $buildResult = $mailer->build();

    expect($buildResult)->toBeInstanceOf(DocumentMailer::class);
});

test('document mailer has correct subject for invoice', function () {
    $invoice = Invoice::create([
        'type' => 'invoice',
        'company_location_id' => 1,
        'customer_location_id' => 2,
        'invoice_number' => 'INV-003',
        'status' => 'draft',
        'subtotal' => 7500,
        'tax' => 1350,
        'total' => 8850,
    ]);

    $mailer = new DocumentMailer($invoice, 'test@example.com');
    $built = $mailer->build();

    // Access the subject through reflection since it's protected
    $reflection = new ReflectionClass($built);
    $subjectProperty = $reflection->getProperty('subject');
    $subjectProperty->setAccessible(true);
    $subject = $subjectProperty->getValue($built);

    expect($subject)->toBe('Invoice INV-003');
});

test('document mailer has correct subject for estimate', function () {
    $estimate = Invoice::create([
        'type' => 'estimate',
        'company_location_id' => 1,
        'customer_location_id' => 2,
        'invoice_number' => 'EST-001',
        'status' => 'draft',
        'subtotal' => 3000,
        'tax' => 540,
        'total' => 3540,
    ]);

    $mailer = new DocumentMailer($estimate, 'test@example.com');
    $built = $mailer->build();

    $reflection = new ReflectionClass($built);
    $subjectProperty = $reflection->getProperty('subject');
    $subjectProperty->setAccessible(true);
    $subject = $subjectProperty->getValue($built);

    expect($subject)->toBe('Estimate EST-001');
});

test('document mailer implements ShouldQueue', function () {
    $invoice = Invoice::create([
        'type' => 'invoice',
        'company_location_id' => 1,
        'customer_location_id' => 2,
        'invoice_number' => 'INV-004',
        'status' => 'draft',
        'subtotal' => 2000,
        'tax' => 360,
        'total' => 2360,
    ]);

    $mailer = new DocumentMailer($invoice, 'test@example.com');

    expect($mailer)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

test('document mailer uses correct view for invoice', function () {
    $invoice = Invoice::create([
        'type' => 'invoice',
        'company_location_id' => 1,
        'customer_location_id' => 2,
        'invoice_number' => 'INV-005',
        'status' => 'draft',
        'subtotal' => 4000,
        'tax' => 720,
        'total' => 4720,
    ]);

    $mailer = new DocumentMailer($invoice, 'test@example.com');
    $built = $mailer->build();

    $reflection = new ReflectionClass($built);
    $viewProperty = $reflection->getProperty('view');
    $viewProperty->setAccessible(true);
    $view = $viewProperty->getValue($built);

    expect($view)->toBe('emails.invoice');
});

test('document mailer uses correct view for estimate', function () {
    $estimate = Invoice::create([
        'type' => 'estimate',
        'company_location_id' => 1,
        'customer_location_id' => 2,
        'invoice_number' => 'EST-002',
        'status' => 'draft',
        'subtotal' => 6000,
        'tax' => 1080,
        'total' => 7080,
    ]);

    $mailer = new DocumentMailer($estimate, 'test@example.com');
    $built = $mailer->build();

    $reflection = new ReflectionClass($built);
    $viewProperty = $reflection->getProperty('view');
    $viewProperty->setAccessible(true);
    $view = $viewProperty->getValue($built);

    expect($view)->toBe('emails.estimate');
});

test('document mailer passes correct data to view', function () {
    $invoice = Invoice::create([
        'type' => 'invoice',
        'company_location_id' => 1,
        'customer_location_id' => 2,
        'invoice_number' => 'INV-006',
        'status' => 'sent',
        'subtotal' => 8000,
        'tax' => 1440,
        'total' => 9440,
    ]);

    $mailer = new DocumentMailer($invoice, 'recipient@test.com');
    $built = $mailer->build();

    $reflection = new ReflectionClass($built);
    $viewDataProperty = $reflection->getProperty('viewData');
    $viewDataProperty->setAccessible(true);
    $viewData = $viewDataProperty->getValue($built);

    expect($viewData)->toHaveKey('document');
    expect($viewData)->toHaveKey('recipientEmail');
    expect($viewData['document'])->toBe($invoice);
    expect($viewData['recipientEmail'])->toBe('recipient@test.com');
});

test('document mailer handles different recipient emails', function () {
    $invoice = Invoice::create([
        'type' => 'invoice',
        'company_location_id' => 1,
        'customer_location_id' => 2,
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
        $mailer = new DocumentMailer($invoice, $email);
        $built = $mailer->build();

        $reflection = new ReflectionClass($built);
        $viewDataProperty = $reflection->getProperty('viewData');
        $viewDataProperty->setAccessible(true);
        $viewData = $viewDataProperty->getValue($built);

        expect($viewData['recipientEmail'])->toBe($email);
    }
});