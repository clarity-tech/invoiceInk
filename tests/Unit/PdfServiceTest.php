<?php

use App\Models\Invoice;
use App\Services\PdfService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    Config::set('services.gotenberg.enabled', true);
    Config::set('services.gotenberg.url', 'http://gotenberg:3000');
    Config::set('services.gotenberg.timeout', 30);

    Http::fake([
        'gotenberg:3000/forms/chromium/convert/html' => Http::response('%PDF-1.4 fake content', 200),
    ]);
});

test('can generate PDF for invoice', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'TEST-001',
    ], [
        [
            'description' => 'Test Service',
            'quantity' => 1,
            'unit_price' => 1000,
            'tax_rate' => 18,
        ],
    ]);

    $pdfService = new PdfService;
    $pdfContent = $pdfService->generateInvoicePdf($invoice);

    expect($pdfContent)->toBeString()
        ->and(substr($pdfContent, 0, 4))->toBe('%PDF');
});

test('can generate download response for invoice', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'TEST-002',
    ], [
        [
            'description' => 'Test Service',
            'quantity' => 1,
            'unit_price' => 1000,
            'tax_rate' => 18,
        ],
    ]);

    $pdfService = new PdfService;
    $response = $pdfService->downloadInvoicePdf($invoice);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment; filename="invoice-TEST-002.pdf"');
});

test('can generate PDF for estimate', function () {
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-001',
    ], [
        [
            'description' => 'Test Service',
            'quantity' => 2,
            'unit_price' => 1500,
            'tax_rate' => 1250,
        ],
    ]);

    $pdfService = new PdfService;
    $pdfContent = $pdfService->generateEstimatePdf($estimate);

    expect($pdfContent)->toBeString()
        ->and(substr($pdfContent, 0, 4))->toBe('%PDF');
});

test('can generate download response for estimate', function () {
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-002',
    ], [
        [
            'description' => 'Test Service',
            'quantity' => 1,
            'unit_price' => 2000,
            'tax_rate' => 18,
        ],
    ]);

    $pdfService = new PdfService;
    $response = $pdfService->downloadEstimatePdf($estimate);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment; filename="estimate-EST-002.pdf"');
});

test('pdf service handles invoice without items gracefully', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-NO-ITEMS',
    ], []);

    $pdfService = new PdfService;
    $pdfContent = $pdfService->generateInvoicePdf($invoice);

    expect($pdfContent)->toBeString();
});

test('pdf service handles invoice with complex items', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-COMPLEX',
    ], [
        [
            'description' => 'Website Development with very long description that might wrap to multiple lines',
            'quantity' => 1,
            'unit_price' => 50000,
            'tax_rate' => 18,
        ],
        [
            'description' => 'Maintenance',
            'quantity' => 12,
            'unit_price' => 5000,
            'tax_rate' => 18,
        ],
        [
            'description' => 'Tax-free consultation',
            'quantity' => 5,
            'unit_price' => 2000,
            'tax_rate' => 0,
        ],
    ]);

    $pdfService = new PdfService;
    $pdfContent = $pdfService->generateInvoicePdf($invoice);

    expect($pdfContent)->toBeString();
});

test('pdf service validates invoice model type', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-VALIDATE',
    ]);

    expect($invoice)->toBeInstanceOf(Invoice::class);
    expect($invoice->type)->toBe('invoice');
    expect($invoice->invoice_number)->toBe('INV-VALIDATE');
});

test('pdf service validates estimate model type', function () {
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-VALIDATE',
    ]);

    expect($estimate)->toBeInstanceOf(Invoice::class);
    expect($estimate->type)->toBe('estimate');
    expect($estimate->invoice_number)->toBe('EST-VALIDATE');
});
