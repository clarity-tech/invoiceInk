<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Http;

class PdfService
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
            // Use Chrome HTTP service when enabled
            if ($this->shouldUseRemoteChrome()) {
                return $this->generatePdfViaHttpService($html);
            }

            // Fallback to local generation (if needed for local development)
            throw new \Exception('PDF generation requires Chrome service to be enabled');
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate PDF: '.$e->getMessage());
        }
    }

    /**
     * Generate PDF via Chrome HTTP service
     */
    private function generatePdfViaHttpService(string $html): string
    {
        $response = Http::timeout(config('services.chrome.timeout', 30))
            ->post(config('services.chrome.url') . '/generate-pdf', [
                'html' => $html,
                'options' => [
                    'format' => 'A4',
                    'margin' => [
                        'top' => '10mm',
                        'right' => '10mm',
                        'bottom' => '10mm',
                        'left' => '10mm'
                    ],
                    'printBackground' => true
                ]
            ]);

        if ($response->failed()) {
            $errorMessage = $response->json('error') ?? 'PDF generation failed';
            throw new \Exception("PDF generation failed: {$errorMessage}");
        }

        return $response->body();
    }

    /**
     * Check if we should use remote Chrome instance
     */
    private function shouldUseRemoteChrome(): bool
    {
        return config('services.chrome.enabled', false);
    }


    /**
     * Download PDF for an invoice
     */
    public function downloadInvoicePdf(Invoice $invoice): \Symfony\Component\HttpFoundation\Response
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
    public function downloadEstimatePdf(Invoice $estimate): \Symfony\Component\HttpFoundation\Response
    {
        $pdfContent = $this->generateEstimatePdf($estimate);
        $filename = "estimate-{$estimate->invoice_number}.pdf";

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
