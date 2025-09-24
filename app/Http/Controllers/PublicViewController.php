<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\PdfService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PublicViewController extends Controller
{
    public function showInvoice(string $ulid): View
    {
        $invoice = Invoice::withoutGlobalScopes()
            ->with(['items', 'organizationLocation.locatable', 'customerLocation.locatable'])
            ->where('ulid', $ulid)
            ->where('type', 'invoice')
            ->firstOrFail();

        return view('public.invoice', compact('invoice'));
    }

    public function showEstimate(string $ulid): View
    {
        $estimate = Invoice::withoutGlobalScopes()
            ->with(['items', 'organizationLocation.locatable', 'customerLocation.locatable'])
            ->where('ulid', $ulid)
            ->where('type', 'estimate')
            ->firstOrFail();

        return view('public.estimate', compact('estimate'));
    }

    public function downloadInvoicePdf(string $ulid, PdfService $pdfService): Response
    {
        $invoice = Invoice::withoutGlobalScopes()
            ->with(['items', 'organizationLocation.locatable', 'customerLocation.locatable'])
            ->where('ulid', $ulid)
            ->where('type', 'invoice')
            ->firstOrFail();

        return $pdfService->downloadInvoicePdf($invoice);
    }

    public function downloadEstimatePdf(string $ulid, PdfService $pdfService): Response
    {
        $estimate = Invoice::withoutGlobalScopes()
            ->with(['items', 'organizationLocation.locatable', 'customerLocation.locatable'])
            ->where('ulid', $ulid)
            ->where('type', 'estimate')
            ->firstOrFail();

        return $pdfService->downloadEstimatePdf($estimate);
    }
}
