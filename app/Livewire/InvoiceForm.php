<?php

namespace App\Livewire;

use App\Livewire\Traits\InvoiceFormLogic;
use App\Models\Invoice;
use App\Services\PdfService;
use Livewire\Component;

class InvoiceForm extends Component
{
    use InvoiceFormLogic;

    public string $mode = 'create'; // 'create' or 'edit'

    public ?Invoice $invoice = null;

    public function mount(?Invoice $invoice, string $type = 'invoice'): void
    {
        try {
            // Determine mode and type from route - check for actual persisted invoice
            $this->mode = ($invoice && $invoice->exists) ? 'edit' : 'create';
            $this->type = $type;
            $this->invoice = $invoice;

            // Set document type from route parameter for estimates
            if (request()->routeIs('estimates.create')) {
                $this->type = 'estimate';
            }

            // Initialize form defaults
            $this->initializeFormDefaults();

            // Load existing invoice data if editing
            if ($this->mode === 'edit' && $invoice) {
                $this->loadExistingInvoice($invoice);
            }
        } catch (\Exception $e) {
            // Set minimal defaults so component doesn't crash
            $this->mode = $invoice ? 'edit' : 'create';
            $this->type = $type ?: 'invoice';
            $this->currentStep = 1;
            $this->items = [['description' => '', 'quantity' => 1, 'unit_price' => 0, 'tax_rate' => 0]];
            $this->issued_at = now()->format('Y-m-d');
            $this->due_at = now()->addDays(30)->format('Y-m-d');
        }
    }

    public function save()
    {
        // Only pass existing invoice if we're in edit mode with a persisted invoice
        $existingInvoice = ($this->mode === 'edit' && $this->invoice && $this->invoice->exists) ? $this->invoice : null;
        $this->saveInvoice($existingInvoice);

        // Redirect to invoice list after save
        return redirect()->route('invoices.index');
    }

    public function cancel()
    {
        return redirect()->route('invoices.index');
    }

    public function downloadPdf(): ?object
    {
        if ($this->mode !== 'edit' || ! $this->invoice) {
            return null;
        }

        // Security check: Ensure user has access to this invoice's organization
        if (! auth()->user()->allTeams()->contains('id', $this->invoice->organization_id)) {
            abort(403, 'Unauthorized access to invoice.');
        }

        $pdfService = new PdfService;

        if ($this->invoice->type === 'invoice') {
            return $pdfService->downloadInvoicePdf($this->invoice);
        } else {
            return $pdfService->downloadEstimatePdf($this->invoice);
        }
    }

    public function getPageTitleProperty(): string
    {
        if ($this->mode === 'edit') {
            return "Edit ".ucfirst($this->type);
        }

        return "Create ".ucfirst($this->type);
    }

    public function render()
    {
        return view('livewire.invoice-form')
            ->layout('layouts.app', ['title' => $this->getPageTitleProperty()]);
    }
}
