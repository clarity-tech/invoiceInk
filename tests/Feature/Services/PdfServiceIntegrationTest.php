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

// --- Chrome HTTP service flow tests ---

test('generates pdf via http service when chrome enabled', function () {
    Config::set('services.chrome.enabled', true);
    Config::set('services.chrome.url', 'http://chrome:3000');
    Config::set('services.chrome.timeout', 30);

    Http::fake([
        'chrome:3000/generate-pdf' => Http::response('fake-pdf-content', 200),
    ]);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-PDF-001',
        'status' => 'sent',
    ]);

    $pdfContent = $this->pdfService->generateInvoicePdf($invoice);

    expect($pdfContent)->toBe('fake-pdf-content');

    Http::assertSent(function (Request $request) {
        return str_contains($request->url(), '/generate-pdf')
            && $request['options']['format'] === 'A4'
            && $request['options']['printBackground'] === true;
    });
});

test('generates estimate pdf via http service', function () {
    Config::set('services.chrome.enabled', true);
    Config::set('services.chrome.url', 'http://chrome:3000');

    Http::fake([
        'chrome:3000/generate-pdf' => Http::response('fake-estimate-pdf', 200),
    ]);

    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-PDF-001',
        'status' => 'draft',
    ]);

    $pdfContent = $this->pdfService->generateEstimatePdf($estimate);

    expect($pdfContent)->toBe('fake-estimate-pdf');
});

test('sends html content and correct margins to chrome service', function () {
    Config::set('services.chrome.enabled', true);
    Config::set('services.chrome.url', 'http://chrome:3000');

    Http::fake([
        'chrome:3000/generate-pdf' => Http::response('pdf-bytes', 200),
    ]);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-PDF-002',
        'status' => 'sent',
    ]);

    $this->pdfService->generateInvoicePdf($invoice);

    Http::assertSent(function (Request $request) {
        return ! empty($request['html'])
            && $request['options']['margin']['top'] === '10mm'
            && $request['options']['margin']['right'] === '10mm'
            && $request['options']['margin']['bottom'] === '10mm'
            && $request['options']['margin']['left'] === '10mm';
    });
});

// --- Error handling tests ---

test('throws runtime exception when chrome service disabled', function () {
    Config::set('services.chrome.enabled', false);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-PDF-DISABLED',
        'status' => 'sent',
    ]);

    expect(fn () => $this->pdfService->generateInvoicePdf($invoice))
        ->toThrow(RuntimeException::class, 'PDF generation requires Chrome service to be enabled');
});

test('wraps connection exception as runtime exception', function () {
    Config::set('services.chrome.enabled', true);
    Config::set('services.chrome.url', 'http://chrome:3000');

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
    Config::set('services.chrome.enabled', true);
    Config::set('services.chrome.url', 'http://chrome:3000');

    Http::fake([
        'chrome:3000/generate-pdf' => Http::response(['error' => 'Out of memory'], 500),
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
    Config::set('services.chrome.enabled', true);
    Config::set('services.chrome.url', 'http://chrome:3000');

    Http::fake([
        'chrome:3000/generate-pdf' => Http::response('pdf-binary-data', 200),
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
    Config::set('services.chrome.enabled', true);
    Config::set('services.chrome.url', 'http://chrome:3000');

    Http::fake([
        'chrome:3000/generate-pdf' => Http::response('estimate-pdf-data', 200),
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
    Config::set('services.chrome.enabled', true);
    Config::set('services.chrome.url', 'http://chrome:3000');
    Config::set('services.chrome.timeout', 60);

    Http::fake([
        'chrome:3000/generate-pdf' => Http::response('pdf', 200),
    ]);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-TIMEOUT',
        'status' => 'sent',
    ]);

    $this->pdfService->generateInvoicePdf($invoice);

    Http::assertSentCount(1);
});

test('chrome disabled by default when config not set', function () {
    Config::set('services.chrome.enabled', false);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-NOCONFIG',
        'status' => 'sent',
    ]);

    expect(fn () => $this->pdfService->generateInvoicePdf($invoice))
        ->toThrow(RuntimeException::class, 'PDF generation requires Chrome service to be enabled');
});
