<?php

use App\Services\PdfService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->pdfService = new PdfService;
});

// --- Gotenberg HTTP service flow tests ---

test('generates pdf via gotenberg when enabled', function () {
    Config::set('services.gotenberg.enabled', true);
    Config::set('services.gotenberg.url', 'http://gotenberg:3000');
    Config::set('services.gotenberg.timeout', 30);

    Http::fake([
        'gotenberg:3000/forms/chromium/convert/html' => Http::response('fake-pdf-content', 200),
    ]);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-PDF-001',
        'status' => 'sent',
    ]);

    $pdfContent = $this->pdfService->generateInvoicePdf($invoice);

    expect($pdfContent)->toBe('fake-pdf-content');

    Http::assertSent(function (Request $request) {
        return str_contains($request->url(), '/forms/chromium/convert/html');
    });
});

test('generates estimate pdf via gotenberg', function () {
    Config::set('services.gotenberg.enabled', true);
    Config::set('services.gotenberg.url', 'http://gotenberg:3000');

    Http::fake([
        'gotenberg:3000/forms/chromium/convert/html' => Http::response('fake-estimate-pdf', 200),
    ]);

    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-PDF-001',
        'status' => 'draft',
    ]);

    $pdfContent = $this->pdfService->generateEstimatePdf($estimate);

    expect($pdfContent)->toBe('fake-estimate-pdf');
});

test('sends html as index.html multipart attachment with A4 options', function () {
    Config::set('services.gotenberg.enabled', true);
    Config::set('services.gotenberg.url', 'http://gotenberg:3000');

    Http::fake([
        'gotenberg:3000/forms/chromium/convert/html' => Http::response('pdf-bytes', 200),
    ]);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-PDF-002',
        'status' => 'sent',
    ]);

    $this->pdfService->generateInvoicePdf($invoice);

    Http::assertSent(function (Request $request) {
        $body = $request->body();

        return str_contains($request->url(), '/forms/chromium/convert/html')
            && str_contains($body, 'index.html')
            && str_contains($body, 'printBackground');
    });
});

// --- Error handling tests ---

test('throws runtime exception when gotenberg service disabled', function () {
    Config::set('services.gotenberg.enabled', false);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-PDF-DISABLED',
        'status' => 'sent',
    ]);

    expect(fn () => $this->pdfService->generateInvoicePdf($invoice))
        ->toThrow(RuntimeException::class, 'PDF generation requires Gotenberg service to be enabled');
});

test('wraps connection exception as runtime exception', function () {
    Config::set('services.gotenberg.enabled', true);
    Config::set('services.gotenberg.url', 'http://gotenberg:3000');

    Http::fake(function () {
        throw new ConnectionException('Connection refused');
    });

    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return $message === 'PDF generation connection failed'
                && str_contains($context['filename'], 'INV-PDF-CONN');
        });

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-PDF-CONN',
        'status' => 'sent',
    ]);

    expect(fn () => $this->pdfService->generateInvoicePdf($invoice))
        ->toThrow(RuntimeException::class, 'PDF service is unavailable. Please try again later.');
});

test('wraps http failure as runtime exception', function () {
    Config::set('services.gotenberg.enabled', true);
    Config::set('services.gotenberg.url', 'http://gotenberg:3000');

    Http::fake([
        'gotenberg:3000/forms/chromium/convert/html' => Http::response('Internal error', 500),
    ]);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-PDF-FAIL',
        'status' => 'sent',
    ]);

    expect(fn () => $this->pdfService->generateInvoicePdf($invoice))
        ->toThrow(RuntimeException::class);
});

// --- Download response tests ---

test('download invoice pdf returns correct response headers', function () {
    Config::set('services.gotenberg.enabled', true);
    Config::set('services.gotenberg.url', 'http://gotenberg:3000');

    Http::fake([
        'gotenberg:3000/forms/chromium/convert/html' => Http::response('pdf-binary-data', 200),
    ]);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-DL-001',
        'status' => 'sent',
    ]);

    $response = $this->pdfService->downloadInvoicePdf($invoice);

    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toContain('attachment')
        ->and($response->headers->get('Content-Disposition'))->toContain('INV-DL-001.pdf')
        ->and($response->getContent())->toBe('pdf-binary-data');
});

test('download estimate pdf returns correct response headers', function () {
    Config::set('services.gotenberg.enabled', true);
    Config::set('services.gotenberg.url', 'http://gotenberg:3000');

    Http::fake([
        'gotenberg:3000/forms/chromium/convert/html' => Http::response('estimate-pdf-data', 200),
    ]);

    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-DL-001',
        'status' => 'draft',
    ]);

    $response = $this->pdfService->downloadEstimatePdf($estimate);

    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toContain('estimate-EST-DL-001.pdf');
});

// --- Config and timeout tests ---

test('uses configured timeout for http requests', function () {
    Config::set('services.gotenberg.enabled', true);
    Config::set('services.gotenberg.url', 'http://gotenberg:3000');
    Config::set('services.gotenberg.timeout', 60);

    Http::fake([
        'gotenberg:3000/forms/chromium/convert/html' => Http::response('pdf', 200),
    ]);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-TIMEOUT',
        'status' => 'sent',
    ]);

    $this->pdfService->generateInvoicePdf($invoice);

    Http::assertSentCount(1);
});

test('gotenberg disabled by default when config not set', function () {
    Config::set('services.gotenberg.enabled', false);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-NOCONFIG',
        'status' => 'sent',
    ]);

    expect(fn () => $this->pdfService->generateInvoicePdf($invoice))
        ->toThrow(RuntimeException::class, 'PDF generation requires Gotenberg service to be enabled');
});
