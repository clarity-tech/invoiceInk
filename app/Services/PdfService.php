<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;
use Spatie\Browsershot\Exceptions\CouldNotTakeBrowsershot;

class PdfService
{
    /**
     * Generate PDF for an invoice
     */
    public function generateInvoicePdf(Invoice $invoice): string
    {
        $invoice->load(['items', 'companyLocation', 'customerLocation']);
        
        $html = View::make('pdf.invoice', compact('invoice'))->render();
        
        return $this->generatePdfFromHtml($html, "invoice-{$invoice->invoice_number}");
    }

    /**
     * Generate PDF for an estimate
     */
    public function generateEstimatePdf(Invoice $estimate): string
    {
        $estimate->load(['items', 'companyLocation', 'customerLocation']);
        
        $html = View::make('pdf.estimate', compact('estimate'))->render();
        
        return $this->generatePdfFromHtml($html, "estimate-{$estimate->invoice_number}");
    }

    /**
     * Generate PDF from HTML content
     */
    private function generatePdfFromHtml(string $html, string $filename): string
    {
        try {
            $pdfContent = Browsershot::html($html)
                ->paperSize(210, 297, 'mm') // A4 size
                ->margins(10, 10, 10, 10, 'mm')
                ->showBackground()
                ->waitUntilNetworkIdle()
                ->pdf();

            return $pdfContent;
        } catch (CouldNotTakeBrowsershot $e) {
            throw new \Exception('Failed to generate PDF: ' . $e->getMessage());
        }
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