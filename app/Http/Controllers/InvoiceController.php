<?php

namespace App\Http\Controllers;

use App\Contracts\Services\InvoiceNumberingServiceInterface;
use App\Contracts\Services\PdfServiceInterface;
use App\Enums\InvoiceStatus;
use App\Mail\DocumentMailer;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceNumberingSeries;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Payment;
use App\Services\EstimateToInvoiceConverter;
use App\Services\InvoiceCalculator;
use App\ValueObjects\ContactCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Invoice::with([
            'customer',
            'organizationLocation.locatable',
            'customerLocation.locatable',
        ]);

        if ($request->filled('type') && in_array($request->type, ['invoice', 'estimate'])) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => [
                'type' => $request->type,
                'status' => $request->status,
            ],
            'statusOptions' => InvoiceStatus::options(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Invoice::class);

        $user = $request->user();
        $organization = $user->currentTeam;
        $type = $request->routeIs('estimates.create') ? 'estimate' : 'invoice';

        $customers = $organization
            ? Customer::where('organization_id', $organization->id)->with('primaryLocation', 'locations')->get()
            : collect();

        $organizationLocations = $organization
            ? Location::where('locatable_type', Organization::class)->where('locatable_id', $organization->id)->get()
            : collect();

        $taxTemplates = $organization
            ? $organization->taxTemplates()->where('is_active', true)->get()
            : collect();

        $numberingSeries = collect();
        if ($organization && $type === 'invoice') {
            $numberingService = app(InvoiceNumberingServiceInterface::class);
            $numberingSeries = $numberingService->getSeriesForOrganization($organization);
        }

        $defaultSeriesId = $numberingSeries->where('is_default', true)->first()?->id
            ?? $numberingSeries->first()?->id;

        return Inertia::render('Invoices/Create', [
            'type' => $type,
            'customers' => $customers,
            'organizationLocations' => $organizationLocations,
            'taxTemplates' => $taxTemplates,
            'numberingSeries' => $numberingSeries,
            'statusOptions' => InvoiceStatus::options(),
            'defaults' => [
                'organization_id' => $organization?->id,
                'organization_location_id' => $organization?->primary_location_id,
                'invoice_numbering_series_id' => $defaultSeriesId,
                'issued_at' => now()->format('Y-m-d'),
                'due_at' => now()->addDays(30)->format('Y-m-d'),
                'currency' => $organization?->currency?->value ?? 'INR',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $user = $request->user();
        $organizationId = $request->input('organization_id');

        abort_unless(
            $user->allTeams()->contains('id', $organizationId),
            403,
            'Unauthorized organization access.'
        );

        $validated = $request->validate([
            'type' => ['required', 'in:invoice,estimate'],
            'organization_id' => ['required', 'exists:teams,id'],
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where('organization_id', $organizationId),
            ],
            'organization_location_id' => [
                'required',
                Rule::exists('locations', 'id')
                    ->where('locatable_type', Organization::class)
                    ->where('locatable_id', $organizationId),
            ],
            'customer_location_id' => [
                'required',
                Rule::exists('locations', 'id')
                    ->where('locatable_type', Customer::class)
                    ->where('locatable_id', $request->input('customer_id')),
            ],
            'customer_shipping_location_id' => [
                'required',
                Rule::exists('locations', 'id')
                    ->where('locatable_type', Customer::class)
                    ->where('locatable_id', $request->input('customer_id')),
            ],
            'status' => ['required', 'string', Rule::enum(InvoiceStatus::class)],
            'issued_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'invoice_numbering_series_id' => ['nullable', 'exists:invoice_numbering_series,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.sac_code' => ['nullable', 'string', 'max:20'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        $invoice = DB::transaction(function () use ($validated) {
            // Calculate totals
            $calculator = new InvoiceCalculator;
            $itemsCollection = collect($validated['items'])->map(fn ($item) => new InvoiceItem([
                'description' => $item['description'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => (int) $item['unit_price'],
                'tax_rate' => (int) ($item['tax_rate'] ?? 0),
            ]));
            $totals = $calculator->calculateFromItems($itemsCollection);

            // Generate invoice number
            $invoiceNumber = null;
            $seriesId = null;
            if ($validated['type'] === 'invoice') {
                $organization = Organization::find($validated['organization_id']);
                $location = Location::find($validated['organization_location_id']);
                $numberingService = app(InvoiceNumberingServiceInterface::class);

                try {
                    if (! empty($validated['invoice_numbering_series_id'])) {
                        $series = InvoiceNumberingSeries::find($validated['invoice_numbering_series_id']);
                        if ($series) {
                            $data = $numberingService->generateInvoiceNumber($organization, $location, $series->name);
                        } else {
                            $data = $numberingService->generateInvoiceNumber($organization, $location);
                        }
                    } else {
                        $data = $numberingService->generateInvoiceNumber($organization, $location);
                    }
                    $invoiceNumber = $data['invoice_number'];
                    $seriesId = $data['series_id'];
                } catch (InvalidArgumentException) {
                    $invoiceNumber = $this->generateFallbackNumber($validated['type'], $validated['organization_id']);
                }
            }

            if (! $invoiceNumber) {
                $invoiceNumber = $this->generateFallbackNumber($validated['type'], $validated['organization_id']);
            }

            $currency = Customer::find($validated['customer_id'])?->currency?->value;

            $invoice = Invoice::create([
                'type' => $validated['type'],
                'organization_id' => $validated['organization_id'],
                'customer_id' => $validated['customer_id'],
                'organization_location_id' => $validated['organization_location_id'],
                'customer_location_id' => $validated['customer_location_id'],
                'customer_shipping_location_id' => $validated['customer_shipping_location_id'],
                'invoice_number' => $invoiceNumber,
                'invoice_numbering_series_id' => $seriesId,
                'status' => $validated['status'],
                'issued_at' => ! empty($validated['issued_at']) ? now()->parse($validated['issued_at']) : null,
                'due_at' => ! empty($validated['due_at']) ? now()->parse($validated['due_at']) : null,
                'subtotal' => $totals->subtotal,
                'tax' => $totals->tax,
                'total' => $totals->total,
                'currency' => $currency,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'sac_code' => $item['sac_code'] ?? null,
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (int) $item['unit_price'],
                    'tax_rate' => (int) ($item['tax_rate'] ?? 0),
                ]);
            }

            return $invoice;
        });

        $documentType = ucfirst($validated['type']);

        return redirect()->route('invoices.edit', $invoice->id)
            ->with('success', "{$documentType} created successfully.");
    }

    public function edit(Request $request, Invoice $invoice): Response
    {
        $this->authorize('update', $invoice);

        $invoice->load(['items', 'organizationLocation', 'customerLocation', 'customerShippingLocation', 'customer.locations', 'organization', 'payments']);

        $user = $request->user();
        $organization = $invoice->organization ?? $user->currentTeam;

        $customers = Customer::where('organization_id', $organization->id)
            ->with('primaryLocation', 'locations')
            ->get();

        $organizationLocations = Location::where('locatable_type', Organization::class)
            ->where('locatable_id', $organization->id)
            ->get();

        $taxTemplates = $organization->taxTemplates()->where('is_active', true)->get();

        $numberingSeries = collect();
        if ($invoice->type === 'invoice') {
            $numberingService = app(InvoiceNumberingServiceInterface::class);
            $numberingSeries = $numberingService->getSeriesForOrganization($organization);
        }

        return Inertia::render('Invoices/Edit', [
            'invoice' => $invoice,
            'customers' => $customers,
            'organizationLocations' => $organizationLocations,
            'taxTemplates' => $taxTemplates,
            'numberingSeries' => $numberingSeries,
            'statusOptions' => InvoiceStatus::options(),
        ]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $organizationId = $invoice->organization_id;

        $validated = $request->validate([
            'type' => ['required', 'in:invoice,estimate'],
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where('organization_id', $organizationId),
            ],
            'organization_location_id' => [
                'required',
                Rule::exists('locations', 'id')
                    ->where('locatable_type', Organization::class)
                    ->where('locatable_id', $organizationId),
            ],
            'customer_location_id' => [
                'required',
                Rule::exists('locations', 'id')
                    ->where('locatable_type', Customer::class)
                    ->where('locatable_id', $request->input('customer_id')),
            ],
            'customer_shipping_location_id' => [
                'required',
                Rule::exists('locations', 'id')
                    ->where('locatable_type', Customer::class)
                    ->where('locatable_id', $request->input('customer_id')),
            ],
            'status' => ['required', 'string', Rule::enum(InvoiceStatus::class)],
            'issued_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.sac_code' => ['nullable', 'string', 'max:20'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        DB::transaction(function () use ($invoice, $validated) {
            $calculator = new InvoiceCalculator;
            $itemsCollection = collect($validated['items'])->map(fn ($item) => new InvoiceItem([
                'description' => $item['description'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => (int) $item['unit_price'],
                'tax_rate' => (int) ($item['tax_rate'] ?? 0),
            ]));
            $totals = $calculator->calculateFromItems($itemsCollection);

            $currency = Customer::find($validated['customer_id'])?->currency?->value;

            $invoice->update([
                'type' => $validated['type'],
                'customer_id' => $validated['customer_id'],
                'organization_location_id' => $validated['organization_location_id'],
                'customer_location_id' => $validated['customer_location_id'],
                'customer_shipping_location_id' => $validated['customer_shipping_location_id'],
                'status' => $validated['status'],
                'issued_at' => ! empty($validated['issued_at']) ? now()->parse($validated['issued_at']) : null,
                'due_at' => ! empty($validated['due_at']) ? now()->parse($validated['due_at']) : null,
                'subtotal' => $totals->subtotal,
                'tax' => $totals->tax,
                'total' => $totals->total,
                'currency' => $currency,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Sync items: delete all, recreate
            $invoice->items()->delete();

            foreach ($validated['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'sac_code' => $item['sac_code'] ?? null,
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (int) $item['unit_price'],
                    'tax_rate' => (int) ($item['tax_rate'] ?? 0),
                ]);
            }
        });

        $documentType = ucfirst($invoice->type);

        return back()->with('success', "{$documentType} updated successfully.");
    }

    public function sendEmail(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('send', $invoice);

        $validated = $request->validate([
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*' => ['required', 'email'],
            'cc' => ['nullable', 'array'],
            'cc.*' => ['nullable', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'attach_pdf' => ['boolean'],
        ]);

        $contacts = collect($validated['recipients'])->map(fn ($email) => [
            'name' => '',
            'email' => trim($email),
        ])->toArray();

        $contactCollection = new ContactCollection($contacts);
        $ccEmails = array_map('trim', $validated['cc'] ?? []);

        $mailable = new DocumentMailer(
            $invoice,
            $contactCollection,
            $validated['subject'],
            $ccEmails,
            $validated['body']
        );

        if ($validated['attach_pdf'] ?? false) {
            $pdfService = app(PdfServiceInterface::class);
            $pdfContent = $invoice->type === 'invoice'
                ? $pdfService->generateInvoicePdf($invoice)
                : $pdfService->generateEstimatePdf($invoice);

            $mailable->attachData($pdfContent, "{$invoice->invoice_number}.pdf", [
                'mime' => 'application/pdf',
            ]);
        }

        Mail::send($mailable);

        $documentType = ucfirst($invoice->type);

        return back()->with('success', "{$documentType} sent successfully.");
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        $documentType = $invoice->type;

        $invoice->items()->delete();
        $invoice->delete();

        return back()->with('success', ucfirst($documentType).' deleted successfully.');
    }

    public function duplicate(Invoice $invoice): RedirectResponse
    {
        $this->authorize('view', $invoice);

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

        return redirect()->route('invoices.edit', $newInvoice->id)
            ->with('success', ucfirst($invoice->type).' duplicated successfully.');
    }

    public function convertEstimate(Invoice $invoice, EstimateToInvoiceConverter $converter): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->type !== 'estimate') {
            return back()->with('error', 'Only estimates can be converted to invoices.');
        }

        $newInvoice = $converter->convert($invoice);

        return redirect()->route('invoices.edit', $newInvoice->id)
            ->with('success', 'Estimate converted to invoice successfully.');
    }

    public function downloadPdf(Invoice $invoice): mixed
    {
        $this->authorize('downloadPdf', $invoice);

        $pdfService = app(PdfServiceInterface::class);

        if ($invoice->type === 'invoice') {
            return $pdfService->downloadInvoicePdf($invoice);
        }

        return $pdfService->downloadEstimatePdf($invoice);
    }

    public function recordPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($invoice, $validated) {
            $invoice->payments()->create([
                'amount' => $validated['amount'],
                'currency' => $invoice->currency,
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $totalPaid = $invoice->payments()->sum('amount');
            $invoice->update([
                'amount_paid' => $totalPaid,
                'status' => $totalPaid >= $invoice->total
                    ? InvoiceStatus::PAID
                    : InvoiceStatus::PARTIALLY_PAID,
            ]);
        });

        return back()->with('success', 'Payment recorded.');
    }

    public function deletePayment(Invoice $invoice, Payment $payment): RedirectResponse
    {
        $this->authorize('update', $invoice);

        DB::transaction(function () use ($invoice, $payment) {
            $payment->delete();

            $totalPaid = $invoice->payments()->sum('amount');
            $invoice->update([
                'amount_paid' => $totalPaid,
                'status' => $totalPaid <= 0
                    ? InvoiceStatus::SENT
                    : ($totalPaid >= $invoice->total ? InvoiceStatus::PAID : InvoiceStatus::PARTIALLY_PAID),
            ]);
        });

        return back()->with('success', 'Payment deleted.');
    }

    private function generateFallbackNumber(string $type, int $organizationId): string
    {
        return DB::transaction(function () use ($type, $organizationId) {
            $prefix = $type === 'invoice' ? 'INV' : 'EST';
            $year = now()->year;
            $month = now()->format('m');

            $lastDocument = Invoice::where('type', $type)
                ->where('organization_id', $organizationId)
                ->where('invoice_number', 'like', "{$prefix}-{$year}-{$month}-%")
                ->lockForUpdate()
                ->orderBy('invoice_number', 'desc')
                ->first();

            $sequence = $lastDocument
                ? (int) last(explode('-', $lastDocument->invoice_number)) + 1
                : 1;

            return sprintf('%s-%s-%s-%04d', $prefix, $year, $month, $sequence);
        });
    }
}
