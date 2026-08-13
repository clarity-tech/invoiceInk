<?php

namespace App\Http\Controllers;

use App\Contracts\Services\InvoiceNumberingServiceInterface;
use App\Contracts\Services\PdfServiceInterface;
use App\Enums\Country;
use App\Enums\EmailDeliveryStatus;
use App\Enums\IndianState;
use App\Enums\InvoiceStatus;
use App\Jobs\SendDocumentEmail;
use App\Mail\DocumentMailer;
use App\Models\Customer;
use App\Models\EmailDelivery;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceNumberingSeries;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Payment;
use App\Services\EstimateToInvoiceConverter;
use App\Services\ExchangeRateService;
use App\Services\GstBreakdownService;
use App\Services\InvoiceCalculator;
use App\Services\TdsTcsCalculator;
use App\ValueObjects\ContactCollection;
use App\ValueObjects\InvoiceTotals;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceCalculator $calculator,
        private readonly ExchangeRateService $exchangeRateService,
        private readonly InvoiceNumberingServiceInterface $numberingService,
        private readonly GstBreakdownService $gstBreakdownService,
        private readonly TdsTcsCalculator $tdsTcsCalculator,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Invoice::with([
            'customer',
            'organizationLocation.locatable',
            'customerLocation.locatable',
            'latestEmailDelivery',
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

        // "Duplicate" lands here with ?from={invoice}. The source is only a
        // template for the form — nothing is persisted until the user saves,
        // so the number is minted by store() from the numbering series like
        // any other new document. The global scope keeps this org-scoped.
        $source = $request->filled('from')
            ? Invoice::with('items')->findOrFail($request->integer('from'))
            : null;

        $customers = $organization
            ? Customer::where('organization_id', $organization->id)->with('primaryLocation', 'locations')->get()
            : collect();

        $organizationLocations = $organization
            ? Location::where('locatable_type', Organization::class)->where('locatable_id', $organization->id)->get()
            : collect();

        $taxOptions = $organization?->country_code?->taxOptions() ?? [];

        $numberingSeries = collect();
        if ($organization && $type === 'invoice') {
            $numberingSeries = $this->numberingService->getSeriesForOrganization($organization);
        }

        $defaultSeriesId = $numberingSeries->where('is_default', true)->first()?->id
            ?? $numberingSeries->first()?->id;

        $defaults = [
            'organization_id' => $organization?->id,
            'organization_location_id' => $organization?->primary_location_id,
            'invoice_numbering_series_id' => $defaultSeriesId,
            'issued_at' => now()->format('Y-m-d'),
            'due_at' => now()->addDays(30)->format('Y-m-d'),
            'currency' => $organization?->currency?->value ?? 'INR',
        ];

        if ($source) {
            $defaults = array_merge($defaults, $this->buildDuplicateDefaults(
                $source,
                $customers,
                $organizationLocations,
                $numberingSeries,
            ));
        }

        return Inertia::render('Invoices/Create', [
            'type' => $type,
            'attachments' => [],
            'customers' => $customers,
            'organizationLocations' => $organizationLocations,
            'taxOptions' => $taxOptions,
            'numberingSeries' => $numberingSeries,
            'statusOptions' => InvoiceStatus::options(),
            'taxLabels' => Country::taxLabels(),
            'indianStates' => $organization?->country_code === Country::IN
                ? IndianState::options()
                : [],
            'defaults' => $defaults,
        ]);
    }

    /**
     * Pre-fill values for a create form seeded from an existing document.
     *
     * Only the parts of the document a user would retype are carried over.
     * Deliberately excluded: the invoice number and its series sequence (minted
     * on save), the status (a copy starts as a draft), the issue and due dates
     * (a copy is a new document issued today), and the totals, currency and
     * exchange rate (all recomputed by store() from the items, the customer and
     * the save date).
     *
     * References to archived customers and locations are dropped rather than
     * pre-filled, because the form's selects only offer live records.
     *
     * @param  Collection<int, Customer>  $customers
     * @param  Collection<int, Location>  $organizationLocations
     * @param  Collection<int, InvoiceNumberingSeries>  $numberingSeries
     * @return array<string, mixed>
     */
    private function buildDuplicateDefaults(
        Invoice $source,
        Collection $customers,
        Collection $organizationLocations,
        Collection $numberingSeries,
    ): array {
        $customer = $customers->firstWhere('id', $source->customer_id);
        $customerLocationIds = $customer?->locations->pluck('id') ?? collect();

        $keepLocation = fn (?int $id) => $id !== null && $customerLocationIds->contains($id)
            ? $id
            : null;

        $defaults = [
            'customer_id' => $customer?->id,
            'customer_location_id' => $keepLocation($source->customer_location_id),
            'customer_shipping_location_id' => $keepLocation($source->customer_shipping_location_id),
            'subject' => $source->subject ?? '',
            'order_number' => $source->order_number ?? '',
            'place_of_supply' => $source->place_of_supply ?? '',
            'tds_rate' => $source->tds_rate,
            'tds_section' => $source->tds_section ?? '',
            'tcs_rate' => $source->tcs_rate,
            'tcs_section' => $source->tcs_section ?? '',
            'notes' => $source->notes ?? '',
            'terms' => $source->terms ?? '',
            'email_recipients' => $this->normalizeEmailRecipients($source->email_recipients ?? []),
            'items' => $source->items->map(fn (InvoiceItem $item) => [
                'description' => $item->description,
                'sac_code' => $item->sac_code,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_rate' => $item->tax_rate,
            ])->all(),
        ];

        if ($organizationLocations->contains('id', $source->organization_location_id)) {
            $defaults['organization_location_id'] = $source->organization_location_id;
        }

        if ($numberingSeries->contains('id', $source->invoice_numbering_series_id)) {
            $defaults['invoice_numbering_series_id'] = $source->invoice_numbering_series_id;
        }

        return $defaults;
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

        $validated = $request->validate(array_merge($this->sharedInvoiceRules(), [
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
            'invoice_numbering_series_id' => ['nullable', 'exists:invoice_numbering_series,id'],
        ]));

        $this->guardAttachmentsTotalSize($request->file('attachments', []));

        $invoice = DB::transaction(function () use ($validated) {
            $totals = $this->calculateTotals($validated['items']);

            // Generate invoice number
            $invoiceNumber = null;
            $seriesId = null;
            if ($validated['type'] === 'invoice') {
                $organization = Organization::find($validated['organization_id']);
                $location = Location::find($validated['organization_location_id']);

                try {
                    $series = ! empty($validated['invoice_numbering_series_id'])
                        ? InvoiceNumberingSeries::find($validated['invoice_numbering_series_id'])
                        : null;

                    $data = $series
                        ? $this->numberingService->generateInvoiceNumber($organization, $location, $series->name)
                        : $this->numberingService->generateInvoiceNumber($organization, $location);
                    $invoiceNumber = $data['invoice_number'];
                    $seriesId = $data['series_id'];
                } catch (InvalidArgumentException) {
                    $invoiceNumber = $this->generateFallbackNumber($validated['type'], $validated['organization_id']);
                }
            }

            if (! $invoiceNumber) {
                $invoiceNumber = $this->generateFallbackNumber($validated['type'], $validated['organization_id']);
            }

            $customer = Customer::find($validated['customer_id']);
            $currency = $customer?->currency?->value;
            $issuedAt = ! empty($validated['issued_at']) ? now()->parse($validated['issued_at']) : now();
            $orgCurrency = Organization::find($validated['organization_id'])?->currency?->value;
            $exchangeRate = $this->resolveExchangeRate($currency, $orgCurrency, $issuedAt);

            $sourceTaxes = $this->tdsTcsCalculator->compute(
                $totals->subtotal,
                $totals->tax,
                $validated['tds_rate'] ?? null,
                $validated['tcs_rate'] ?? null,
            );

            $invoice = Invoice::create([
                'type' => $validated['type'],
                'organization_id' => $validated['organization_id'],
                'customer_id' => $validated['customer_id'],
                'organization_location_id' => $validated['organization_location_id'],
                'customer_location_id' => $validated['customer_location_id'],
                'customer_shipping_location_id' => $validated['customer_shipping_location_id'],
                'invoice_number' => $invoiceNumber,
                'order_number' => $validated['order_number'] ?? null,
                'invoice_numbering_series_id' => $seriesId,
                'status' => $validated['status'],
                'issued_at' => ! empty($validated['issued_at']) ? now()->parse($validated['issued_at']) : null,
                'due_at' => ! empty($validated['due_at']) ? now()->parse($validated['due_at']) : null,
                'subtotal' => $totals->subtotal,
                'tax' => $totals->tax,
                'total' => $sourceTaxes['total'],
                'currency' => $currency,
                'place_of_supply' => $customer?->isOverseas() ? null : ($validated['place_of_supply'] ?? null),
                'tds_rate' => $validated['tds_rate'] ?? null,
                'tds_amount' => $sourceTaxes['tds_amount'],
                'tds_section' => $validated['tds_section'] ?? null,
                'tcs_rate' => $validated['tcs_rate'] ?? null,
                'tcs_amount' => $sourceTaxes['tcs_amount'],
                'tcs_section' => $validated['tcs_section'] ?? null,
                'exchange_rate' => $exchangeRate,
                'subject' => $validated['subject'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'email_recipients' => $this->normalizeEmailRecipients($validated['email_recipients'] ?? []),
            ]);

            $this->persistItems($invoice, $validated['items']);
            $this->applyGstBreakdown($invoice);

            return $invoice;
        });

        $this->syncInvoiceAttachments($invoice, $request);

        // "Save & Send" from the create form posts with ?send=1. Carry the flag
        // onto the edit redirect so the form reopens the email composer there.
        return redirect()->route('invoices.edit', array_merge(
            ['invoice' => $invoice->id],
            $request->boolean('send') ? ['send' => 1] : [],
        ))->with('success', "{$invoice->typeLabel()} created successfully.");
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

        $taxOptions = $organization->country_code?->taxOptions() ?? [];

        $numberingSeries = collect();
        if ($invoice->type === 'invoice') {
            $numberingSeries = $this->numberingService->getSeriesForOrganization($organization);
        }

        return Inertia::render('Invoices/Edit', [
            'invoice' => $invoice,
            'attachments' => $invoice->attachmentsForDisplay(),
            'customers' => $customers,
            'organizationLocations' => $organizationLocations,
            'taxOptions' => $taxOptions,
            'numberingSeries' => $numberingSeries,
            'statusOptions' => InvoiceStatus::options(),
            'taxLabels' => Country::taxLabels(),
            'indianStates' => $organization?->country_code === Country::IN
                ? IndianState::options()
                : [],
        ]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $organizationId = $invoice->organization_id;

        $validated = $request->validate(array_merge($this->sharedInvoiceRules(), [
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
        ]));

        $this->guardAttachmentsTotalSize($request->file('attachments', []));

        DB::transaction(function () use ($invoice, $validated) {
            $totals = $this->calculateTotals($validated['items']);

            $customer = Customer::find($validated['customer_id']);
            $currency = $customer?->currency?->value;
            $issuedAt = ! empty($validated['issued_at']) ? now()->parse($validated['issued_at']) : now();
            $orgCurrency = $invoice->organization?->currency?->value;
            $exchangeRate = $this->resolveExchangeRate($currency, $orgCurrency, $issuedAt);

            $sourceTaxes = $this->tdsTcsCalculator->compute(
                $totals->subtotal,
                $totals->tax,
                $validated['tds_rate'] ?? null,
                $validated['tcs_rate'] ?? null,
            );

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
                'total' => $sourceTaxes['total'],
                'currency' => $currency,
                'place_of_supply' => $customer?->isOverseas() ? null : ($validated['place_of_supply'] ?? null),
                'tds_rate' => $validated['tds_rate'] ?? null,
                'tds_amount' => $sourceTaxes['tds_amount'],
                'tds_section' => $validated['tds_section'] ?? null,
                'tcs_rate' => $validated['tcs_rate'] ?? null,
                'tcs_amount' => $sourceTaxes['tcs_amount'],
                'tcs_section' => $validated['tcs_section'] ?? null,
                'exchange_rate' => $exchangeRate,
                'subject' => $validated['subject'] ?? null,
                'order_number' => $validated['order_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'email_recipients' => $this->normalizeEmailRecipients($validated['email_recipients'] ?? []),
            ]);

            // Sync items: delete all, recreate
            $invoice->items()->delete();

            $this->persistItems($invoice, $validated['items']);
            $this->applyGstBreakdown($invoice);
        });

        $this->syncInvoiceAttachments($invoice, $request);

        return back()->with('success', "{$invoice->typeLabel()} updated successfully.");
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
            'attachment_ids' => ['nullable', 'array'],
            'attachment_ids.*' => ['integer'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => self::ATTACHMENT_FILE_RULES,
        ], [
            'attachments.max' => 'You may attach at most 10 files.',
            'attachments.*.max' => 'Each attachment must be 10MB or smaller.',
            'attachments.*.mimes' => 'Unsupported file type.',
        ]);

        $this->guardAttachmentsTotalSize($request->file('attachments', []));

        $extraAttachments = $this->resolveEmailAttachments($invoice, $request, $validated['attachment_ids'] ?? []);

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
            (bool) ($validated['attach_pdf'] ?? config('app.invoice_email.attach_pdf')),
            $extraAttachments,
            (bool) ($validated['include_view_online_button'] ?? config('app.invoice_email.include_view_online_button')),
        );

        // The send is queued, so nothing has actually gone out yet. Record the
        // attempt now and let SendDocumentEmail write the outcome; the invoice
        // is promoted to SENT there, on success only.
        $delivery = EmailDelivery::create([
            'organization_id' => $invoice->organization_id,
            'invoice_id' => $invoice->id,
            'mailable_type' => DocumentMailer::class,
            'mode' => 'platform',
            'status' => EmailDeliveryStatus::QUEUED,
            'recipients' => $contactCollection->getEmails(),
            'queued_at' => now(),
        ]);

        SendDocumentEmail::dispatch($delivery, $mailable);

        // Merge the sent addresses into the invoice's remembered recipients:
        // everything sent becomes a checked entry (new addresses appended), and
        // anything remembered but not sent this time is kept but unchecked — so
        // the next send and the invoice form both pre-select who this went to.
        $invoice->email_recipients = $this->mergeSentRecipients(
            $invoice->email_recipients ?? [],
            $validated['recipients'],
        );

        $invoice->save();

        return back()->with('success', "{$invoice->typeLabel()} queued for sending.");
    }

    /**
     * Persist any newly uploaded files onto the invoice, then resolve the set of
     * attachments to send: the kept invoice attachments (by id) plus the new
     * uploads. Excluding an existing attachment in the email editor only omits
     * it from this send — it stays on the invoice.
     *
     * @param  array<int, int|string>  $keepIds
     * @return array<int, array{disk: string, path: string, name: string, mime: string}>
     */
    private function resolveEmailAttachments(Invoice $invoice, Request $request, array $keepIds): array
    {
        $keepIds = array_map('intval', $keepIds);

        $kept = $invoice->getMedia('attachments')
            ->whereIn('id', $keepIds);

        $added = collect($request->file('attachments', []))
            ->map(fn (UploadedFile $file) => $invoice->addMedia($file)->toMediaCollection('attachments'));

        return $kept->concat($added)
            ->map(fn (Media $media) => [
                'disk' => $media->disk,
                'path' => $media->getPathRelativeToRoot(),
                'name' => $media->file_name,
                'mime' => $media->mime_type ?? 'application/octet-stream',
            ])
            ->values()
            ->all();
    }

    /**
     * Stream/redirect to an invoice attachment for viewing, guarding that the
     * media belongs to this invoice's attachments collection.
     */
    public function showAttachment(Invoice $invoice, Media $media): RedirectResponse
    {
        $this->authorize('view', $invoice);

        abort_unless(
            $media->model_type === Invoice::class
                && (int) $media->model_id === $invoice->id
                && $media->collection_name === 'attachments',
            404
        );

        $disk = Storage::disk($media->disk);

        $url = $disk->providesTemporaryUrls()
            ? $media->getTemporaryUrl(now()->addMinutes(5))
            : $media->getUrl();

        return redirect($url);
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        $label = $invoice->typeLabel();

        $invoice->delete();

        return back()->with('success', "{$label} deleted successfully.");
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

        $label = $invoice->typeLabel();

        $invoice->restore();

        return back()->with('success', "{$label} restored successfully.");
    }

    public function forceDestroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('forceDelete', $invoice);

        $label = $invoice->typeLabel();

        $invoice->items()->delete();
        $invoice->forceDelete();

        return back()->with('success', "{$label} permanently deleted.");
    }

    /**
     * Open a create form seeded from an existing document.
     *
     * Duplicating deliberately persists nothing: it hands the user an unsaved
     * copy to review. The number is therefore minted by store() from the
     * numbering series when they save, so abandoning the form neither leaves a
     * stray draft behind nor burns a sequence number.
     */
    public function duplicate(Invoice $invoice): RedirectResponse
    {
        // Duplicating leads to a new document, so it is a create — not a read.
        // The source is already org-scoped via the global scope.
        $this->authorize('create', Invoice::class);

        $route = $invoice->isEstimate() ? 'estimates.create' : 'invoices.create';

        // The create form looks identical whether it is blank or seeded, so say
        // which document is being copied and that it is not saved yet.
        return redirect()->route($route, ['from' => $invoice->id])
            ->with('info', "Reviewing a copy of {$invoice->invoice_number}. Save to create it.");
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

    public function downloadPdf(Invoice $invoice, PdfServiceInterface $pdfService): mixed
    {
        $this->authorize('downloadPdf', $invoice);

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

    /** @param array<int, array{description: string, quantity: int|string, unit_price: int|string, tax_rate?: int|string|null}> $items */
    private function calculateTotals(array $items): InvoiceTotals
    {
        $collection = collect($items)->map(fn (array $item) => new InvoiceItem([
            'description' => $item['description'],
            'quantity' => (int) $item['quantity'],
            'unit_price' => (int) $item['unit_price'],
            'tax_rate' => (int) ($item['tax_rate'] ?? 0),
        ]));

        return $this->calculator->calculateFromItems($collection);
    }

    /**
     * Derive the CGST/SGST/UTGST/IGST split from the place of supply and store
     * it on the invoice. Leaves both fields null for non-GST invoices so the
     * document falls back to a single aggregate tax line.
     */
    private function applyGstBreakdown(Invoice $invoice): void
    {
        $invoice->load(['items', 'organizationLocation', 'customerLocation']);

        $breakdown = $this->gstBreakdownService->build($invoice);

        $invoice->update([
            'tax_breakdown' => $breakdown ?: null,
            'tax_type' => $breakdown ? 'GST' : null,
        ]);
    }

    private function resolveExchangeRate(?string $from, ?string $to, Carbon $date): float
    {
        return $from && $to
            ? $this->exchangeRateService->getRate($from, $to, $date)
            : 1.0;
    }

    /** @param array<int, array{description: string, sac_code?: string|null, quantity: int|string, unit_price: int|string, tax_rate?: int|string|null}> $items */
    private function persistItems(Invoice $invoice, array $items): void
    {
        foreach ($items as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $item['description'],
                'sac_code' => $item['sac_code'] ?? null,
                'quantity' => (int) $item['quantity'],
                'unit_price' => (int) $item['unit_price'],
                'tax_rate' => (int) ($item['tax_rate'] ?? 0),
            ]);
        }
    }

    /** @return array<string, array<int, mixed>> */
    private function sharedInvoiceRules(): array
    {
        return [
            'type' => ['required', 'in:invoice,estimate'],
            'status' => ['required', 'string', Rule::enum(InvoiceStatus::class)],
            'issued_at' => ['required', 'date'],
            'due_at' => ['required', 'date', 'after_or_equal:issued_at'],
            'place_of_supply' => ['nullable', Rule::enum(IndianState::class)],
            'tds_rate' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'tds_section' => ['nullable', 'string', 'max:20'],
            'tcs_rate' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'tcs_section' => ['nullable', 'string', 'max:20'],
            'subject' => ['nullable', 'string', 'max:255'],
            'order_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'email_recipients' => ['nullable', 'array'],
            'email_recipients.*.email' => ['required', 'email'],
            'email_recipients.*.checked' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:2000'],
            'items.*.sac_code' => ['nullable', 'string', 'max:20'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => self::ATTACHMENT_FILE_RULES,
            'removed_attachment_ids' => ['nullable', 'array'],
            'removed_attachment_ids.*' => ['integer'],
        ];
    }

    /**
     * Normalize a submitted recipient list into a deduped array of
     * `['email' => ..., 'checked' => bool]`, unique by lowercased email. A
     * missing `checked` defaults to true. Legacy plain-string email entries
     * (older invoices/seed data) are treated as checked.
     *
     * @param  array<int, array{email?: string, checked?: mixed}|string>  $recipients
     * @return array<int, array{email: string, checked: bool}>
     */
    private function normalizeEmailRecipients(array $recipients): array
    {
        $seen = [];
        $normalized = [];

        foreach ($recipients as $recipient) {
            if (is_string($recipient)) {
                $recipient = ['email' => $recipient, 'checked' => true];
            }

            if (! is_array($recipient)) {
                continue;
            }

            $email = trim($recipient['email'] ?? '');

            if ($email === '') {
                continue;
            }

            $key = strtolower($email);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $normalized[] = [
                'email' => $email,
                'checked' => filter_var($recipient['checked'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        return $normalized;
    }

    /**
     * Merge the addresses a document was just sent to into its remembered
     * recipient list. Sent addresses become checked (new ones appended); any
     * previously remembered address that was not sent this time is kept but
     * marked unchecked.
     *
     * @param  array<int, array{email?: string, checked?: mixed}|string>  $existing
     * @param  array<int, string>  $sent
     * @return array<int, array{email: string, checked: bool}>
     */
    private function mergeSentRecipients(array $existing, array $sent): array
    {
        $sentKeys = collect($sent)
            ->map(fn (string $email): string => strtolower(trim($email)))
            ->filter()
            ->flip();

        $result = [];
        $seen = [];

        foreach ($this->normalizeEmailRecipients($existing) as $entry) {
            $key = strtolower($entry['email']);
            $seen[$key] = true;
            $result[] = ['email' => $entry['email'], 'checked' => $sentKeys->has($key)];
        }

        foreach ($sent as $email) {
            $email = trim($email);
            $key = strtolower($email);

            if ($email === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = ['email' => $email, 'checked' => true];
        }

        return $result;
    }

    /**
     * Validation rules for a single uploaded attachment, shared by the form
     * (store/update) and the email sender.
     *
     * @var array<int, string>
     */
    private const ATTACHMENT_FILE_RULES = [
        'file',
        'max:10240',
        'mimes:pdf,png,jpg,jpeg,gif,webp,doc,docx,xls,xlsx,ppt,pptx,csv,txt',
    ];

    private const ATTACHMENT_MAX_TOTAL_BYTES = 25 * 1024 * 1024;

    /**
     * Reject a batch of uploaded files whose combined size exceeds the limit.
     *
     * @param  array<int, UploadedFile>  $files
     *
     * @throws ValidationException
     */
    private function guardAttachmentsTotalSize(array $files): void
    {
        $totalBytes = collect($files)->sum(fn (UploadedFile $file) => $file->getSize());

        if ($totalBytes > self::ATTACHMENT_MAX_TOTAL_BYTES) {
            throw ValidationException::withMessages([
                'attachments' => 'Total attachments size must not exceed 25MB.',
            ]);
        }
    }

    /**
     * Persist newly uploaded files to the invoice and delete any the user
     * removed. Removals are scoped to this invoice's attachments collection.
     */
    private function syncInvoiceAttachments(Invoice $invoice, Request $request): void
    {
        $removedIds = array_filter((array) $request->input('removed_attachment_ids', []));

        if ($removedIds) {
            $invoice->getMedia('attachments')
                ->whereIn('id', $removedIds)
                ->each(fn (Media $media) => $media->delete());
        }

        foreach ($request->file('attachments', []) as $file) {
            $invoice->addMedia($file)->toMediaCollection('attachments');
        }
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
