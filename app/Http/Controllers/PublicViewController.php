<?php

namespace App\Http\Controllers;

use App\Contracts\Services\PdfServiceInterface;
use App\Models\Invoice;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PublicViewController extends Controller
{
    public function showInvoice(string $ulid): View
    {
        $invoice = Invoice::withoutGlobalScopes()
            ->with([
                'items',
                'organization',
                'customer' => fn ($q) => $q->withoutGlobalScopes(),
                'organizationLocation',
                'customerLocation', 'customerShippingLocation',
                'payments',
            ])
            ->where('ulid', $ulid)
            ->where('type', 'invoice')
            ->firstOrFail();

        return view('public.invoice', compact('invoice'));
    }

    public function showEstimate(string $ulid): View
    {
        $estimate = Invoice::withoutGlobalScopes()
            ->with([
                'items',
                'organization',
                'customer' => fn ($q) => $q->withoutGlobalScopes(),
                'organizationLocation',
                'customerLocation', 'customerShippingLocation',
                'payments',
            ])
            ->where('ulid', $ulid)
            ->where('type', 'estimate')
            ->firstOrFail();

        return view('public.estimate', compact('estimate'));
    }

    public function downloadInvoicePdf(string $ulid, PdfServiceInterface $pdfService): Response
    {
        $invoice = Invoice::withoutGlobalScopes()
            ->with([
                'items',
                'organization',
                'customer' => fn ($q) => $q->withoutGlobalScopes(),
                'organizationLocation',
                'customerLocation', 'customerShippingLocation',
                'payments',
            ])
            ->where('ulid', $ulid)
            ->where('type', 'invoice')
            ->firstOrFail();

        return $pdfService->downloadInvoicePdf($invoice);
    }

    public function downloadEstimatePdf(string $ulid, PdfServiceInterface $pdfService): Response
    {
        $estimate = Invoice::withoutGlobalScopes()
            ->with([
                'items',
                'organization',
                'customer' => fn ($q) => $q->withoutGlobalScopes(),
                'organizationLocation',
                'customerLocation', 'customerShippingLocation',
                'payments',
            ])
            ->where('ulid', $ulid)
            ->where('type', 'estimate')
            ->firstOrFail();

        return $pdfService->downloadEstimatePdf($estimate);
    }
}
