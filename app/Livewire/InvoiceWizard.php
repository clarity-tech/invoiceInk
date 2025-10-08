<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Location;
use App\Models\Organization;
use App\Services\InvoiceCalculator;
use App\Services\PdfService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class InvoiceWizard extends Component
{
    use WithPagination;

    public string $type = 'invoice'; // 'invoice' or 'estimate'

    public int $currentStep = 1;

    // Basic Details
    #[Rule('required|exists:teams,id')]
    public ?int $organization_id = null;

    #[Rule('required|exists:customers,id')]
    public ?int $customer_id = null;

    #[Rule('required|exists:locations,id')]
    public ?int $organization_location_id = null;

    #[Rule('required|exists:locations,id')]
    public ?int $customer_location_id = null;

    #[Rule('nullable|date')]
    public ?string $issued_at = null;

    #[Rule('nullable|date')]
    public ?string $due_at = null;

    // Items
    public array $items = [];

    // Totals (computed)
    public int $subtotal = 0;

    public int $tax = 0;

    public int $total = 0;

    public bool $showInvoices = true;

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->addItem();
        $this->issued_at = now()->format('Y-m-d');
        $this->due_at = now()->addDays(30)->format('Y-m-d');
    }

    public function addItem(): void
    {
        $this->items[] = [
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'tax_rate' => 0,
        ];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
            $this->calculateTotals();
        }
    }

    public function calculateTotals(): void
    {
        $calculator = new InvoiceCalculator;
        $itemsCollection = collect($this->items)->map(function ($item) {
            return new InvoiceItem([
                'description' => $item['description'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => (int) ($item['unit_price'] * 100), // Convert to cents
                'tax_rate' => (float) $item['tax_rate'],
            ]);
        });

        $totals = $calculator->calculateFromItems($itemsCollection);
        $this->subtotal = $totals->subtotal;
        $this->tax = $totals->tax;
        $this->total = $totals->total;
    }

    public function updatedItems(): void
    {
        $this->calculateTotals();
    }

    public function nextStep(): void
    {
        if ($this->currentStep === 1) {
            $this->validate([
                'organization_id' => 'required|exists:teams,id',
                'customer_id' => 'required|exists:customers,id',
                'organization_location_id' => 'required|exists:locations,id',
                'customer_location_id' => 'required|exists:locations,id',
            ]);
        }

        if ($this->currentStep < 3) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showInvoices = false;
        $this->currentStep = 1;
    }

    public function edit(Invoice $invoice): void
    {
        $invoice->load(['items', 'organizationLocation', 'customerLocation']);

        $this->editingId = $invoice->id;
        $this->type = $invoice->type;
        $this->organization_id = $invoice->organizationLocation->locatable_id;
        $this->customer_id = $invoice->customerLocation->locatable_id;
        $this->organization_location_id = $invoice->organization_location_id;
        $this->customer_location_id = $invoice->customer_location_id;
        $this->issued_at = $invoice->issued_at?->format('Y-m-d');
        $this->due_at = $invoice->due_at?->format('Y-m-d');

        $this->items = $invoice->items->map(function ($item) {
            return [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price / 100, // Convert from cents
                'tax_rate' => (float) $item->tax_rate,
            ];
        })->toArray();

        $this->calculateTotals();
        $this->showInvoices = false;
        $this->currentStep = 1;
    }

    public function save(): void
    {
        $this->validate([
            'organization_id' => 'required|exists:teams,id',
            'customer_id' => 'required|exists:customers,id',
            'organization_location_id' => 'required|exists:locations,id',
            'customer_location_id' => 'required|exists:locations,id',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($this->editingId) {
            $invoice = Invoice::findOrFail($this->editingId);
            $invoice->update([
                'type' => $this->type,
                'organization_id' => $this->organization_id,
                'customer_id' => $this->customer_id,
                'organization_location_id' => $this->organization_location_id,
                'customer_location_id' => $this->customer_location_id,
                'issued_at' => $this->issued_at ? now()->parse($this->issued_at) : null,
                'due_at' => $this->due_at ? now()->parse($this->due_at) : null,
                'subtotal' => $this->subtotal,
                'tax' => $this->tax,
                'total' => $this->total,
                'currency' => Organization::find($this->organization_id)?->currency?->value,
            ]);

            // Delete existing items and recreate
            $invoice->items()->delete();
        } else {
            $invoice = Invoice::create([
                'type' => $this->type,
                'organization_id' => $this->organization_id,
                'customer_id' => $this->customer_id,
                'organization_location_id' => $this->organization_location_id,
                'customer_location_id' => $this->customer_location_id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'status' => 'draft',
                'issued_at' => $this->issued_at ? now()->parse($this->issued_at) : null,
                'due_at' => $this->due_at ? now()->parse($this->due_at) : null,
                'subtotal' => $this->subtotal,
                'tax' => $this->tax,
                'total' => $this->total,
                'currency' => Organization::find($this->organization_id)?->currency?->value,
            ]);
        }

        // Create items
        foreach ($this->items as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $item['description'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => (int) ($item['unit_price'] * 100), // Convert to cents
                'tax_rate' => (float) ($item['tax_rate'] ?: 0),
            ]);
        }

        $this->resetForm();
        $this->showInvoices = true;
        $this->resetPage();

        $documentType = ucfirst($this->type);
        session()->flash('message', $this->editingId ?
            "{$documentType} updated successfully!" :
            "{$documentType} created successfully!"
        );
    }

    public function delete(Invoice $invoice): void
    {
        $invoice->items()->delete();
        $invoice->delete();

        $this->resetPage();
        session()->flash('message', ucfirst($invoice->type).' deleted successfully!');
    }

    public function downloadPdf(Invoice $invoice)
    {
        $pdfService = new PdfService;

        if ($invoice->type === 'invoice') {
            return $pdfService->downloadInvoicePdf($invoice);
        } else {
            return $pdfService->downloadEstimatePdf($invoice);
        }
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showInvoices = true;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->type = 'invoice';
        $this->currentStep = 1;
        $this->organization_id = null;
        $this->customer_id = null;
        $this->organization_location_id = null;
        $this->customer_location_id = null;
        $this->issued_at = now()->format('Y-m-d');
        $this->due_at = now()->addDays(30)->format('Y-m-d');
        $this->items = [];
        $this->addItem();
        $this->subtotal = 0;
        $this->tax = 0;
        $this->total = 0;
        $this->resetValidation();
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = $this->type === 'invoice' ? 'INV' : 'EST';
        $year = now()->year;
        $month = now()->format('m');

        $lastDocument = Invoice::where('type', $this->type)
            ->where('invoice_number', 'like', "{$prefix}-{$year}-{$month}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if (! $lastDocument) {
            $sequence = 1;
        } else {
            $lastNumber = $lastDocument->invoice_number;
            $parts = explode('-', $lastNumber);
            $sequence = (int) end($parts) + 1;
        }

        return sprintf('%s-%s-%s-%04d', $prefix, $year, $month, $sequence);
    }

    #[Computed]
    public function organizations()
    {
        return Organization::with('primaryLocation')->get();
    }

    #[Computed]
    public function customers()
    {
        return Customer::with('primaryLocation')->get();
    }

    #[Computed]
    public function organizationLocations()
    {
        if (! $this->organization_id) {
            return collect();
        }

        return Location::where('locatable_type', Organization::class)
            ->where('locatable_id', $this->organization_id)
            ->get();
    }

    #[Computed]
    public function customerLocations()
    {
        if (! $this->customer_id) {
            return collect();
        }

        return Location::where('locatable_type', Customer::class)
            ->where('locatable_id', $this->customer_id)
            ->get();
    }

    #[Computed]
    public function invoices()
    {
        return Invoice::with(['organizationLocation', 'customerLocation'])
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function currentCurrency(): string
    {
        if ($this->organization_id) {
            $organization = Organization::find($this->organization_id);

            return $organization?->currency?->value ?? 'INR';
        }

        return 'INR';
    }

    #[Computed]
    public function currencySymbol(): string
    {
        try {
            $currency = $this->currentCurrency;

            return \Akaunting\Money\Money::{$currency}(0)->getCurrency()->getSymbol();
        } catch (\Exception $e) {
            // If currency is invalid, fallback to INR symbol
            return '₹';
        }
    }

    /**
     * Format a monetary amount using the current currency
     */
    public function formatAmount(int $amount): string
    {
        try {
            $currency = $this->currentCurrency;

            return \Akaunting\Money\Money::{$currency}($amount)->format();
        } catch (\Exception $e) {
            // If currency is invalid, fallback to INR formatting
            return '₹'.number_format($amount / 100, 2);
        }
    }

    public function render()
    {
        return view('livewire.invoice-wizard')
            ->layout('layouts.app', ['title' => 'Invoices & Estimates']);
    }
}
