<?php

namespace App\Http\Controllers;

use App\Contracts\Services\PdfServiceInterface;
use App\Models\Invoice;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PublicViewController extends Controller
{
    public function showInvoice(Request $request, string $ulid): View
    {
        $invoice = Invoice::withoutGlobalScope(OrganizationScope::class)
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

        $linkExpiresAt = $this->linkExpiry($request);

        return view('public.invoice', [
            'invoice' => $invoice,
            'pdfUrl' => $invoice->sharePdfUrl($linkExpiresAt),
            'linkExpiresAt' => $linkExpiresAt,
        ]);
    }

    public function showEstimate(Request $request, string $ulid): View
    {
        $estimate = Invoice::withoutGlobalScope(OrganizationScope::class)
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

        $linkExpiresAt = $this->linkExpiry($request);

        return view('public.estimate', [
            'estimate' => $estimate,
            'pdfUrl' => $estimate->sharePdfUrl($linkExpiresAt),
            'linkExpiresAt' => $linkExpiresAt,
        ]);
    }

    public function downloadInvoicePdf(string $ulid, PdfServiceInterface $pdfService): Response
    {
        $invoice = Invoice::withoutGlobalScope(OrganizationScope::class)
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
        $estimate = Invoice::withoutGlobalScope(OrganizationScope::class)
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

    /**
     * When the link this page was opened with stops working. ValidateShareLink
     * has already checked it is signed and still in date.
     */
    private function linkExpiry(Request $request): Carbon
    {
        return Carbon::createFromTimestamp((int) $request->query('expires'));
    }
}
