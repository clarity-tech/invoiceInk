<?php

namespace App\Services;

use App\Contracts\Services\PdfServiceInterface;
use App\Models\Invoice;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * PDF generation via Gotenberg (https://gotenberg.dev).
 *
 * Renders Blade templates to HTML, sends to Gotenberg's Chromium
 * endpoint, and returns the PDF content.
 */
class PdfService implements PdfServiceInterface
{
    /**
     * Generate PDF for an invoice
     */
    public function generateInvoicePdf(Invoice $invoice): string
    {
        $invoice->load(['items', 'organizationLocation', 'customerLocation']);

        return $this->renderDocument('pdf.invoice', ['invoice' => $invoice], "invoice-{$invoice->invoice_number}");
    }

    /**
     * Generate PDF for an estimate
     */
    public function generateEstimatePdf(Invoice $estimate): string
    {
        $estimate->load(['items', 'organizationLocation', 'customerLocation']);

        return $this->renderDocument('pdf.estimate', ['estimate' => $estimate], "estimate-{$estimate->invoice_number}");
    }

    /**
     * Render a document view to PDF.
     *
     * Chromium cannot anchor a block to the bottom of the final page on its
     * own, so we render twice: the first pass measures the document's page
     * count, and the second pass tells the template to stretch the card across
     * exactly that many pages, letting it pin the footer to the bottom of the
     * last page. The second pass is skipped when the page count can't be read.
     *
     * @param  array<string, mixed>  $data
     */
    private function renderDocument(string $view, array $data, string $filename): string
    {
        $firstPass = View::make($view, $data + ['fillPages' => null])->render();
        $pdf = $this->generatePdfFromHtml($firstPass, $filename);

        $pages = $this->countPdfPages($pdf);

        if ($pages < 1) {
            return $pdf;
        }

        $secondPass = View::make($view, $data + ['fillPages' => $pages])->render();

        return $this->generatePdfFromHtml($secondPass, $filename);
    }

    /**
     * Count the pages in a Gotenberg/Chromium-generated PDF.
     *
     * Chromium writes the page-tree /Count entry uncompressed in the document
     * catalog, so it is readable without a full PDF parser. Returns 0 when no
     * count can be found.
     */
    private function countPdfPages(string $pdf): int
    {
        if (preg_match_all('/\/Count\s+(\d+)/', $pdf, $matches)) {
            return max(array_map('intval', $matches[1]));
        }

        return 0;
    }

    /**
     * Generate PDF from HTML content
     */
    private function generatePdfFromHtml(string $html, string $filename): string
    {
        try {
            if (! $this->isEnabled()) {
                throw new \RuntimeException('PDF generation requires Gotenberg service to be enabled');
            }

            return $this->generatePdfViaGotenberg($html);
        } catch (ConnectionException $e) {
            Log::error('PDF generation connection failed', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('PDF service is unavailable. Please try again later.');
        } catch (RequestException $e) {
            Log::error('PDF generation request failed', [
                'filename' => $filename,
                'status' => $e->response?->status(),
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('PDF generation failed: '.$e->getMessage());
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Unexpected PDF generation error', [
                'filename' => $filename,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \RuntimeException('Failed to generate PDF: '.$e->getMessage());
        }
    }

    /**
     * Generate PDF via Gotenberg's Chromium HTML endpoint.
     *
     * @see https://gotenberg.dev/docs/convert-with-chromium/convert-html-to-pdf
     */
    private function generatePdfViaGotenberg(string $html): string
    {
        $url = config('services.gotenberg.url').'/forms/chromium/convert/html';
        $timeout = (int) config('services.gotenberg.timeout', 30);

        $response = Http::timeout($timeout)
            ->attach('files', $html, 'index.html')
            ->post($url, [
                'paperWidth' => '8.27',    // A4 width in inches
                'paperHeight' => '11.7',   // A4 height in inches
                'marginTop' => '0.39',     // ~10mm
                'marginBottom' => '0.39',
                'marginLeft' => '0.39',
                'marginRight' => '0.39',
                'printBackground' => 'true',
                'emulatedMediaType' => 'print',
            ]);

        if ($response->failed()) {
            throw new \Exception('PDF generation failed with status '.$response->status());
        }

        return $response->body();
    }

    /**
     * Check if Gotenberg service is enabled
     */
    private function isEnabled(): bool
    {
        return (bool) config('services.gotenberg.enabled', false);
    }

    /**
     * Download PDF for an invoice
     */
    public function downloadInvoicePdf(Invoice $invoice): Response
    {
        $pdfContent = $this->generateInvoicePdf($invoice);
        $filename = "invoice-{$invoice->invoice_number}.pdf";

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Download PDF for an estimate
     */
    public function downloadEstimatePdf(Invoice $estimate): Response
    {
        $pdfContent = $this->generateEstimatePdf($estimate);
        $filename = "estimate-{$estimate->invoice_number}.pdf";

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
