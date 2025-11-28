<?php

namespace App\Livewire\Traits;

use Akaunting\Money\Money;
use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceNumberingSeries;
use App\Models\Location;
use App\Models\Organization;
use App\Services\InvoiceCalculator;
use App\Services\InvoiceNumberingService;
use Illuminate\Validation\Rule as ValidationRule;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;

trait InvoiceFormLogic
{
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

    #[Rule('nullable|exists:invoice_numbering_series,id')]
    public ?int $invoice_numbering_series_id = null;

    public string $status = 'draft';

    // Items
    public array $items = [];

    // Totals (computed)
    public int $subtotal = 0;

    public int $tax = 0;

    public int $total = 0;

    protected function initializeFormDefaults(): void
    {
        // Auto-set organization to current team if user is authenticated
        if (auth()->check()) {
            $this->organization_id = auth()->user()->currentTeam?->id;
        }

        $this->addItem();
        $this->issued_at = now()->format('Y-m-d');
        $this->due_at = now()->addDays(30)->format('Y-m-d');
    }

    public function loadExistingInvoice(Invoice $invoice): void
    {
        // Security check: Ensure user has access to this invoice's organization
        if (auth()->check() && ! auth()->user()->allTeams()->contains('id', $invoice->organization_id)) {
            abort(403, 'Unauthorized access to invoice.');
        }

        $invoice->load(['items', 'organizationLocation', 'customerLocation']);

        $this->type = $invoice->type ?? 'invoice';
        $this->organization_id = $invoice->organizationLocation->locatable_id;
        $this->customer_id = $invoice->customerLocation->locatable_id;
        $this->organization_location_id = $invoice->organization_location_id;
        $this->customer_location_id = $invoice->customer_location_id;
        $this->issued_at = $invoice->issued_at?->format('Y-m-d');
        $this->due_at = $invoice->due_at?->format('Y-m-d');
        $this->status = $invoice->status?->value ?? 'draft';

        $this->items = $invoice->items->map(function ($item) {
            return [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price / 100, // Convert from cents
                'tax_rate' => $item->tax_rate / 100, // Convert from basis points to percentage
            ];
        })->toArray();

        $this->calculateTotals();
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
                'tax_rate' => (int) ($item['tax_rate'] * 100), // Convert percentage to basis points
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
            // Basic required fields
            $rules = [
                'organization_id' => 'required|exists:teams,id',
                'customer_id' => 'required|exists:customers,id',
            ];

            // Check if organizations and customers have locations
            $orgLocations = $this->organizationLocations;
            $custLocations = $this->customerLocations;

            // Add location validation only if locations exist
            if ($orgLocations->count() > 0) {
                $rules['organization_location_id'] = 'required|exists:locations,id';
            } else {
                // Clear any previously set location if no locations exist
                $this->organization_location_id = null;
            }

            if ($custLocations->count() > 0) {
                $rules['customer_location_id'] = 'required|exists:locations,id';
            } else {
                // Clear any previously set location if no locations exist
                $this->customer_location_id = null;
            }

            $this->validate($rules);

            // Additional checks for missing locations with helpful messages
            if ($this->organization_id && $orgLocations->count() === 0) {
                $orgName = Organization::find($this->organization_id)?->name ?? 'the selected organization';
                $this->addError('organization_location_id', "Please create at least one location for {$orgName} before proceeding. You can manage locations in the Organizations section.");

                return;
            }

            if ($this->customer_id && $custLocations->count() === 0) {
                $custName = Customer::find($this->customer_id)?->name ?? 'the selected customer';
                $this->addError('customer_location_id', "Please create at least one location for {$custName} before proceeding. You can manage locations in the Customers section.");

                return;
            }
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

    public function saveInvoice(?Invoice $existingInvoice = null): ?Invoice
    {
        logger('-----invoice_numbering_series_id-------- '.$this->invoice_numbering_series_id);

        $this->validate([
            'organization_id' => 'required|exists:teams,id',
            'customer_id' => 'required|exists:customers,id',
            'organization_location_id' => 'required|exists:locations,id',
            'customer_location_id' => 'required|exists:locations,id',
            'status' => ['required', 'string', ValidationRule::enum(InvoiceStatus::class)],
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($existingInvoice) {
            $existingInvoice->update([
                'type' => $this->type,
                'organization_id' => $this->organization_id,
                'customer_id' => $this->customer_id,
                'organization_location_id' => $this->organization_location_id,
                'customer_location_id' => $this->customer_location_id,
                'status' => $this->status,
                'issued_at' => $this->issued_at ? now()->parse($this->issued_at) : null,
                'due_at' => $this->due_at ? now()->parse($this->due_at) : null,
                'subtotal' => $this->subtotal,
                'tax' => $this->tax,
                'total' => $this->total,
                'currency' => Organization::find($this->organization_id)?->currency?->value,
            ]);

            // Delete existing items and recreate
            $existingInvoice->items()->delete();
            $invoice = $existingInvoice;
        } else {
            // Generate invoice number using new numbering service for invoices only
            $invoiceNumberData = null;
            if ($this->type === 'invoice') {
                $organization = Organization::find($this->organization_id);
                $location = Location::find($this->organization_location_id);
                $numberingService = new InvoiceNumberingService;

                try {
                    // Use specific series if selected, otherwise let the service choose
                    if ($this->invoice_numbering_series_id) {
                        $series = InvoiceNumberingSeries::find($this->invoice_numbering_series_id);
                        if ($series) {
                            $invoiceNumberData = $numberingService->generateInvoiceNumber($organization, $location, $series->name);
                        }
                    } else {
                        $invoiceNumberData = $numberingService->generateInvoiceNumber($organization, $location);
                    }
                } catch (InvalidArgumentException $e) {
                    $this->addError('invoice_numbering_series_id', $e->getMessage());

                    return null;
                }
            }

            $invoice = Invoice::create([
                'type' => $this->type,
                'organization_id' => $this->organization_id,
                'customer_id' => $this->customer_id,
                'organization_location_id' => $this->organization_location_id,
                'customer_location_id' => $this->customer_location_id,
                'invoice_number' => $invoiceNumberData ? $invoiceNumberData['invoice_number'] : $this->generateInvoiceNumber(),
                'invoice_numbering_series_id' => $invoiceNumberData ? $invoiceNumberData['series_id'] : null,
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
                'tax_rate' => (int) (($item['tax_rate'] ?: 0) * 100), // Convert percentage to basis points
            ]);
        }

        $documentType = ucfirst($this->type);
        session()->flash('message', $existingInvoice ?
            "{$documentType} updated successfully!" :
            "{$documentType} created successfully!"
        );

        return $invoice;
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
        // Return only organizations/teams the user has access to
        if (! auth()->check()) {
            return collect();
        }

        return auth()->user()->allTeams()->load('primaryLocation');
    }

    #[Computed]
    public function customers()
    {
        // Return only customers belonging to the current organization
        if (! $this->organization_id) {
            return collect();
        }

        return Customer::where('organization_id', $this->organization_id)
            ->with('primaryLocation')
            ->get();
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
        $currency = $this->currentCurrency;

        return Money::{$currency}(0)->getCurrency()->getSymbol();
    }

    #[Computed]
    public function availableNumberingSeries()
    {
        if (! $this->organization_id) {
            return collect();
        }

        $organization = Organization::find($this->organization_id);
        if (! $organization) {
            return collect();
        }

        $numberingService = new InvoiceNumberingService;

        return $numberingService->getSeriesForOrganization($organization);
    }

    #[Computed]
    public function selectedSeriesPreview(): string
    {
        if (! $this->invoice_numbering_series_id) {
            return '';
        }

        $series = InvoiceNumberingSeries::find($this->invoice_numbering_series_id);
        if (! $series) {
            return '';
        }

        $numberingService = new InvoiceNumberingService;

        return $numberingService->previewNextNumber($series);
    }

    /**
     * Format a monetary amount using the current currency
     */
    public function formatAmount(int $amount): string
    {
        $currency = $this->currentCurrency;

        return Money::{$currency}($amount)->format();
    }
}
