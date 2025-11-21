<?php

namespace App\Livewire;

use App\Models\Invoice;
use App\Services\PdfService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class InvoiceList extends Component
{
    use WithPagination;

    public function delete(Invoice $invoice): void
    {
        // Security check: Ensure user has access to this invoice's organization
        if (! auth()->check() || ! auth()->user()->allTeams()->contains('id', $invoice->organization_id)) {
            abort(403, 'Unauthorized access to invoice.');
        }

        $documentType = $invoice->type;

        $invoice->items()->delete();
        $invoice->delete();

        $this->resetPage();
        session()->flash('message', ucfirst($documentType).' deleted successfully!');
    }

    public function downloadPdf(Invoice $invoice)
    {
        // Security check: Ensure user has access to this invoice's organization
        if (! auth()->check() || ! auth()->user()->allTeams()->contains('id', $invoice->organization_id)) {
            abort(403, 'Unauthorized access to invoice.');
        }

        $pdfService = new PdfService;

        if ($invoice->type === 'invoice') {
            return $pdfService->downloadInvoicePdf($invoice);
        } else {
            return $pdfService->downloadEstimatePdf($invoice);
        }
    }

    #[Computed]
    public function invoices()
    {
        // OrganizationScope automatically filters by current user's team
        return Invoice::with(['organizationLocation', 'customerLocation'])
            ->latest()
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.invoice-list')
            ->layout('layouts.app', ['title' => 'Invoices & Estimates']);
    }
}