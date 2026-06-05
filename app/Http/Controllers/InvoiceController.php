<?php

namespace App\Http\Controllers;

use App\Contracts\Services\InvoiceNumberingServiceInterface;
use App\Contracts\Services\PdfServiceInterface;
use App\Enums\Country;
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
use App\Services\ExchangeRateService;
use App\Services\InvoiceCalculator;
use App\ValueObjects\ContactCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

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
            'taxLabels' => Country::taxLabels(),
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
                    $series = ! empty($validated['invoice_numbering_series_id'])
                        ? InvoiceNumberingSeries::find($validated['invoice_numbering_series_id'])
                        : null;

                    $data = $series
                        ? $numberingService->generateInvoiceNumber($organization, $location, $series->name)
                        : $numberingService->generateInvoiceNumber($organization, $location);
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
            $issuedAt = ! empty($validated['issued_at']) ? now()->parse($validated['issued_at']) : now();
            $orgCurrency = Organization::find($validated['organization_id'])?->currency?->value;
            $exchangeRate = $currency && $orgCurrency
                ? app(ExchangeRateService::class)->getRate($currency, $orgCurrency, $issuedAt)
                : 1.0;

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
                'exchange_rate' => $exchangeRate,
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
        abort_unless($organization, 403, __('messages.authorization.no_organization_context'));

        $customers = Customer::where('organization_id', $organization->id)
            ->with('primaryLocation', 'locations')
            ->get();

        if ($invoice->customer_id && ! $customers->contains('id', $invoice->customer_id)) {
            $archivedCustomer = Customer::withTrashed()
                ->where('organization_id', $organization->id)
                ->where('id', $invoice->customer_id)
                ->with('primaryLocation', 'locations')
                ->first();

            if ($archivedCustomer) {
                $customers->push($archivedCustomer);
            }
        }

        $referencedLocationIds = array_filter([
            $invoice->customer_location_id,
            $invoice->customer_shipping_location_id,
        ]);

        if ($invoice->customer_id && $referencedLocationIds) {
            $archived = Location::onlyTrashed()
                ->whereIn('id', $referencedLocationIds)
                ->where('locatable_type', Customer::class)
                ->where('locatable_id', $invoice->customer_id)
                ->get();

            if ($archived->isNotEmpty()) {
                $invoiceCustomer = $customers->firstWhere('id', $invoice->customer_id);
                if ($invoiceCustomer) {
                    $invoiceCustomer->setRelation(
                        'locations',
                        $invoiceCustomer->locations->concat($archived)
                    );
                }
            }
        }

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
            'taxLabels' => Country::taxLabels(),
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
            $issuedAt = ! empty($validated['issued_at']) ? now()->parse($validated['issued_at']) : now();
            $orgCurrency = $invoice->organization?->currency?->value;
            $exchangeRate = $currency && $orgCurrency
                ? app(ExchangeRateService::class)->getRate($currency, $orgCurrency, $issuedAt)
                : 1.0;

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
                'exchange_rate' => $exchangeRate,
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
            'include_view_online_button' => ['boolean'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => [
                'file',
                'max:10240',
                'mimes:pdf,png,jpg,jpeg,gif,webp,doc,docx,xls,xlsx,ppt,pptx,csv,txt',
            ],
        ], [
            'attachments.max' => 'You may attach at most 10 files.',
            'attachments.*.max' => 'Each attachment must be 10MB or smaller.',
            'attachments.*.mimes' => 'Unsupported file type.',
        ]);

        $uploadedFiles = $request->file('attachments', []);
        $totalBytes = collect($uploadedFiles)->sum(fn ($file) => $file->getSize());

        if ($totalBytes > 25 * 1024 * 1024) {
            return back()->withErrors([
                'attachments' => 'Total attachments size must not exceed 25MB.',
            ])->withInput();
        }

        $extraAttachments = $this->persistEmailAttachments($uploadedFiles);

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
            $validated['body'],
            (bool) ($validated['attach_pdf'] ?? false),
            $extraAttachments,
            (bool) ($validated['include_view_online_button'] ?? true),
        );

        Mail::send($mailable);

        if ($invoice->status === InvoiceStatus::DRAFT) {
            $invoice->update(['status' => InvoiceStatus::SENT]);
        }

        $documentType = ucfirst($invoice->type);

        return back()->with('success', "{$documentType} sent successfully.");
    }

    /**
     * Persist uploaded files to a temp location so the queued mailer can read them.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array<int, array{disk: string, path: string, name: string, mime: string}>
     */
    private function persistEmailAttachments(array $files): array
    {
        if (empty($files)) {
            return [];
        }

        $disk = config('filesystems.default');
        $bucket = 'email-attachments/'.(string) Str::ulid();

        return collect($files)->map(function ($file) use ($disk, $bucket) {
            $safeName = Str::of($file->getClientOriginalName())
                ->replace(['\\', '/', "\0"], '-')
                ->limit(120, '')
                ->toString();
            $storedName = Str::ulid().'-'.$safeName;

            $path = $file->storeAs($bucket, $storedName, ['disk' => $disk]);

            return [
                'disk' => $disk,
                'path' => $path,
                'name' => $safeName !== '' ? $safeName : 'attachment',
                'mime' => $file->getMimeType() ?: 'application/octet-stream',
            ];
        })->all();
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        $documentType = $invoice->type;

        $invoice->delete();

        return back()->with('success', ucfirst($documentType).' deleted successfully.');
    }

    public function trash(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Invoice::onlyTrashed()->with([
            'customer',
            'organizationLocation.locatable',
            'customerLocation.locatable',
        ]);

        if ($request->filled('type') && in_array($request->type, ['invoice', 'estimate'])) {
            $query->where('type', $request->type);
        }

        $invoices = $query->latest('deleted_at')->paginate(10)->withQueryString();

        return Inertia::render('Invoices/Trash', [
            'invoices' => $invoices,
            'filters' => [
                'type' => $request->type,
            ],
        ]);
    }

    public function restore(Invoice $invoice): RedirectResponse
    {
        $this->authorize('restore', $invoice);

        $documentType = $invoice->type;

        $invoice->restore();

        return back()->with('success', ucfirst($documentType).' restored successfully.');
    }

    public function forceDestroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('forceDelete', $invoice);

        $documentType = $invoice->type;

        $invoice->items()->delete();
        $invoice->forceDelete();

        return back()->with('success', ucfirst($documentType).' permanently deleted.');
    }

    public function duplicate(Invoice $invoice): RedirectResponse
    {
        // Duplicating mints a brand-new invoice, so it is a create — not a read.
        // The source is already org-scoped via the global scope.
        $this->authorize('create', Invoice::class);

        $invoice->load('items');

        // The duplicate is a fresh document issued today, so recompute the rate
        // from its currency to the org currency — copying without this would let
        // the column fall back to its 1.0 default and distort cross-currency totals.
        $invoiceCurrency = $invoice->currency?->value;
        $orgCurrency = Organization::find($invoice->organization_id)?->currency?->value;
        $exchangeRate = $invoiceCurrency && $orgCurrency
            ? app(ExchangeRateService::class)->getRate($invoiceCurrency, $orgCurrency, now())
            : 1.0;

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
            'exchange_rate' => $exchangeRate,
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

        try {
            $newInvoice = $converter->convert($invoice);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', 'Unable to convert estimate: '.$e->getMessage());
        }

        return redirect()->route('invoices.edit', $newInvoice->id)
            ->with('success', 'Estimate converted to invoice successfully.');
    }

    public function downloadPdf(Invoice $invoice): mixed
    {
        $this->authorize('downloadPdf', $invoice);

        $pdfService = app(PdfServiceInterface::class);

        return $invoice->isInvoice()
            ? $pdfService->downloadInvoicePdf($invoice)
            : $pdfService->downloadEstimatePdf($invoice);
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

            $invoice->recalculatePaymentStatus();
        });

        return back()->with('success', 'Payment recorded.');
    }

    public function deletePayment(Invoice $invoice, Payment $payment): RedirectResponse
    {
        // Deleting a payment is destructive financial data removal → delete perm.
        $this->authorize('delete', $invoice);

        // Payment is not org-scoped, so ensure it actually belongs to this invoice.
        abort_unless($payment->invoice_id === $invoice->id, 404);

        DB::transaction(function () use ($invoice, $payment) {
            $payment->delete();
            $invoice->recalculatePaymentStatus();
        });

        return back()->with('success', 'Payment deleted.');
    }

    private function generateFallbackNumber(string $type, int $organizationId): string
    {
        return DB::transaction(function () use ($type, $organizationId) {
            $prefix = match ($type) {
                'invoice' => 'INV',
                default => 'EST',
            };
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
