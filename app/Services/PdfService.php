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

        $html = View::make('pdf.invoice', compact('invoice'))->render();

        return $this->generatePdfFromHtml($html, "invoice-{$invoice->invoice_number}");
    }

    /**
     * Generate PDF for an estimate
     */
    public function generateEstimatePdf(Invoice $estimate): string
    {
        $estimate->load(['items', 'organizationLocation', 'customerLocation']);

        $html = View::make('pdf.estimate', compact('estimate'))->render();

        return $this->generatePdfFromHtml($html, "estimate-{$estimate->invoice_number}");
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
