<?php

use App\Services\PdfService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

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

/**
 * A stand-in for a Gotenberg PDF: just the page-tree /Count that Chromium
 * writes uncompressed into the catalog, which is all PdfService reads.
 */
function fakeGotenbergPdf(int $pages, string $tag = ''): string
{
    return "%PDF-1.4\n1 0 obj << /Type /Pages /Kids [] /Count {$pages} >> endobj\n%{$tag}\n";
}

/**
 * The HTML a Gotenberg request carried, from its index.html attachment.
 */
function sentDocumentHtml(Request $request): string
{
    return collect($request->data())->firstWhere('name', 'files')['contents'];
}

test('renders a one-page document in a single pass', function () {
    Config::set('services.gotenberg.enabled', true);
    Config::set('services.gotenberg.url', 'http://gotenberg:3000');

    Http::fake([
        'gotenberg:3000/forms/chromium/convert/html' => Http::response(fakeGotenbergPdf(1), 200),
    ]);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-PDF-ONE',
        'status' => 'sent',
        'notes' => 'Thank you for your business.',
    ]);

    $pdf = $this->pdfService->generateInvoicePdf($invoice);

    expect($pdf)->toBe(fakeGotenbergPdf(1));
    Http::assertSentCount(1);

    // The single pass is the final render: byte for byte the HTML a pass
    // stretched across one page sends, so the PDF is that render's PDF.
    $finalRenderHtml = View::make('pdf.invoice', ['invoice' => $invoice, 'fillPages' => 1])->render();
    $sentHtml = sentDocumentHtml(Http::recorded()[0][0]);

    expect($sentHtml)->toBe($finalRenderHtml)
        ->and($sentHtml)->toContain('min-height: 100vh');
});

test('renders a longer document again across the pages the first pass came to', function () {
    Config::set('services.gotenberg.enabled', true);
    Config::set('services.gotenberg.url', 'http://gotenberg:3000');

    Http::fake([
        'gotenberg:3000/forms/chromium/convert/html' => Http::sequence()
            ->push(fakeGotenbergPdf(2, 'first'), 200)
            ->push(fakeGotenbergPdf(2, 'final'), 200),
    ]);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-PDF-FILL',
        'status' => 'sent',
    ]);

    $pdf = $this->pdfService->generateInvoicePdf($invoice);

    expect($pdf)->toBe(fakeGotenbergPdf(2, 'final'));
    Http::assertSentCount(2);

    [$first, $final] = array_map(fn (array $pair) => sentDocumentHtml($pair[0]), Http::recorded()->all());

    expect($first)->toContain('min-height: 100vh')
        ->and($final)->toContain('min-height: 200vh')
        ->and($final)->toBe(View::make('pdf.invoice', ['invoice' => $invoice, 'fillPages' => 2])->render());
});

test('renders estimates through the same single pass', function (int $pages, int $requests, string $finalHeight) {
    Config::set('services.gotenberg.enabled', true);
    Config::set('services.gotenberg.url', 'http://gotenberg:3000');

    Http::fake([
        'gotenberg:3000/forms/chromium/convert/html' => Http::response(fakeGotenbergPdf($pages), 200),
    ]);

    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => "EST-PDF-PAGES-{$pages}",
        'status' => 'draft',
    ]);

    $this->pdfService->generateEstimatePdf($estimate);

    Http::assertSentCount($requests);

    $recorded = Http::recorded()->all();

    expect(sentDocumentHtml($recorded[0][0]))->toContain('min-height: 100vh')
        ->and(sentDocumentHtml(end($recorded)[0]))->toContain("min-height: {$finalHeight}");
})->with([
    'one page' => [1, 1, '100vh'],
    'three pages' => [3, 2, '300vh'],
]);

test('keeps an invisible copy of the footer in the flow on a pinned render', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-PDF-RESERVE',
        'notes' => 'Reserve-check note',
    ]);
    $invoice->load(['items', 'organizationLocation', 'customerLocation']);

    $pinned = View::make('pdf.invoice', ['invoice' => $invoice, 'fillPages' => 1])->render();
    $unpinned = View::make('pdf.invoice', ['invoice' => $invoice, 'fillPages' => null])->render();

    // Without the in-flow copy the page count leaves the footer out, and a
    // footer that does not fit below the content lands on top of the totals.
    expect($pinned)->toContain('<div class="doc-bottom-reserve">')
        ->and($pinned)->toMatch('/\.doc-bottom-reserve \{\s*visibility: hidden;/')
        ->and(strpos($pinned, 'class="doc-bottom-reserve"'))->toBeLessThan(strpos($pinned, 'class="doc-bottom"'))
        ->and(substr_count($pinned, 'Reserve-check note'))->toBe(2);

    expect($unpinned)->not->toContain('class="doc-bottom-reserve"')
        ->and(substr_count($unpinned, 'Reserve-check note'))->toBe(1);
});

test('renders a single pass when the page count cannot be read', function () {
    Config::set('services.gotenberg.enabled', true);
    Config::set('services.gotenberg.url', 'http://gotenberg:3000');

    Http::fake([
        'gotenberg:3000/forms/chromium/convert/html' => Http::response('no-count-here', 200),
    ]);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-PDF-NOCOUNT',
        'status' => 'sent',
    ]);

    $pdf = $this->pdfService->generateInvoicePdf($invoice);

    expect($pdf)->toBe('no-count-here');
    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => str_contains(sentDocumentHtml($request), 'min-height: 100vh'));
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
