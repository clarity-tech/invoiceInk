<?php

namespace App\Livewire;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\EstimateToInvoiceConverter;
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
            abort(403, __('messages.authorization.unauthorized_invoice'));
        }

        $documentType = $invoice->type;

        $invoice->items()->delete();
        $invoice->delete();

        $this->resetPage();
        session()->flash('message', __('messages.notifications.document_type_deleted', ['type' => ucfirst($documentType)]));
    }

    public function downloadPdf(Invoice $invoice)
    {
        // Security check: Ensure user has access to this invoice's organization
        if (! auth()->check() || ! auth()->user()->allTeams()->contains('id', $invoice->organization_id)) {
            abort(403, __('messages.authorization.unauthorized_invoice'));
        }

        $pdfService = app(PdfService::class);

        if ($invoice->type === 'invoice') {
            return $pdfService->downloadInvoicePdf($invoice);
        } else {
            return $pdfService->downloadEstimatePdf($invoice);
        }
    }

    public function convertToInvoice(Invoice $estimate)
    {
        // Security check
        if (! auth()->check() || ! auth()->user()->allTeams()->contains('id', $estimate->organization_id)) {
            abort(403, __('messages.authorization.unauthorized_invoice'));
        }

        if ($estimate->type !== 'estimate') {
            session()->flash('error', __('forms.validation.only_estimates_convertible'));

            return null;
        }

        $converter = app(EstimateToInvoiceConverter::class);
        $invoice = $converter->convert($estimate);

        session()->flash('message', __('messages.notifications.estimate_converted'));

        return redirect()->route('invoices.edit', $invoice->id);
    }

    public function duplicate(Invoice $invoice)
    {
        // Security check
        if (! auth()->check() || ! auth()->user()->allTeams()->contains('id', $invoice->organization_id)) {
            abort(403, __('messages.authorization.unauthorized_invoice'));
        }

        $invoice->load('items');

        $newInvoice = Invoice::create([
            'type' => $invoice->type,
            'organization_id' => $invoice->organization_id,
            'customer_id' => $invoice->customer_id,
            'organization_location_id' => $invoice->organization_location_id,
            'customer_location_id' => $invoice->customer_location_id,
            'customer_shipping_location_id' => $invoice->customer_shipping_location_id,
            'invoice_number' => 'DRAFT-'.now()->format('YmdHis'),
            'status' => 'draft',
            'issued_at' => now(),
            'due_at' => now()->addDays(30),
            'subtotal' => $invoice->subtotal,
            'tax' => $invoice->tax,
            'total' => $invoice->total,
            'currency' => $invoice->currency,
            'notes' => $invoice->notes,
        ]);

        foreach ($invoice->items as $item) {
            InvoiceItem::create([
                'invoice_id' => $newInvoice->id,
                'description' => $item->description,
                'sac_code' => $item->sac_code,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_rate' => $item->tax_rate,
            ]);
        }

        session()->flash('message', __('messages.notifications.document_type_duplicated', ['type' => ucfirst($invoice->type)]));

        return redirect()->route('invoices.edit', $newInvoice->id);
    }

    #[Computed]
    public function invoices()
    {
        // OrganizationScope automatically filters by current user's team
        return Invoice::with([
            'organizationLocation.locatable',
            'customerLocation.locatable',
        ])
            ->latest()
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.invoice-list')
            ->layout('layouts.app', ['title' => 'Invoices & Estimates']);
    }
}
