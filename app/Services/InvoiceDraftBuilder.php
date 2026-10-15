<?php

namespace App\Services;

use App\Ai\Agents\InvoiceDrafter;
use App\Currency;
use App\Models\Customer;
use App\Models\InvoiceItem;
use App\Models\Location;
use App\Models\Organization;
use App\Models\User;
use App\ValueObjects\InvoiceDraft;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Files\Document;
use Laravel\Ai\Files\Image;

/**
 * Turns a document into a draft for the create form. Claude only reads the
 * document: each value it returns is checked here against the organization's
 * own customers, tax rates and currency, and whatever cannot be carried over
 * safely becomes a warning for the person reviewing the draft.
 */
class InvoiceDraftBuilder
{
    public function __construct(private readonly InvoiceCalculator $calculator) {}

    /**
     * Shown only to organizations that opted in, and only once a key is set.
     */
    public static function isAvailableFor(?Organization $organization): bool
    {
        return filled(config('ai.providers.anthropic.key'))
            && (bool) $organization?->ai_drafting_enabled;
    }

    /**
     * Keep a draft for the create form to pick up, under a key only the
     * person who drafted it can read.
     */
    public function remember(User $user, InvoiceDraft $draft): string
    {
        $token = (string) Str::ulid();

        Cache::put($this->cacheKey($user, $token), $draft->toArray(), now()->addMinutes(30));

        return $token;
    }

    public function recall(User $user, string $token): ?InvoiceDraft
    {
        $draft = Cache::get($this->cacheKey($user, $token));

        return is_array($draft) ? InvoiceDraft::fromArray($draft) : null;
    }

    private function cacheKey(User $user, string $token): string
    {
        return "invoice-draft:{$user->id}:{$token}";
    }

    /**
     * @param  'invoice'|'estimate'  $type
     */
    public function fromDocument(Organization $organization, string $type, ?string $text, ?UploadedFile $file): InvoiceDraft
    {
        $attachments = match (true) {
            $file === null => [],
            str_starts_with((string) $file->getMimeType(), 'image/') => [Image::fromUpload($file)],
            default => [Document::fromUpload($file)],
        };

        $prompt = filled($text)
            ? "Draft the {$type} from this:\n\n<document>\n{$text}\n</document>"
            : "Draft the {$type} from the attached document.";

        $response = (new InvoiceDrafter($organization, $type))->prompt(
            $prompt,
            attachments: $attachments,
            provider: Lab::Anthropic,
            model: config('services.invoice_drafts.model'),
            timeout: config('services.invoice_drafts.timeout'),
        );

        return $this->build(
            $organization,
            $response->toArray(),
            $file?->getClientOriginalName() ?? __('messages.invoice_draft.pasted_text'),
        );
    }

    /**
     * @param  array<string, mixed>  $extracted  as InvoiceDrafter::schema() describes it
     */
    public function build(Organization $organization, array $extracted, string $source): InvoiceDraft
    {
        $warnings = [];
        $defaults = [];

        $customer = $this->matchCustomer($organization, (array) ($extracted['customer'] ?? []), $warnings);
        $currency = $customer?->currency ?? $organization->currency ?? Currency::default();

        if ($customer) {
            $locationId = $customer->primary_location_id ?? $customer->locations->first()?->id;

            $defaults = [
                'customer_id' => $customer->id,
                'customer_location_id' => $locationId,
                'customer_shipping_location_id' => $locationId,
                'place_of_supply' => $customer->place_of_supply ?? '',
                'email_recipients' => array_map(
                    fn (string $email): array => ['email' => $email, 'checked' => true],
                    $customer->emails?->getEmails() ?? [],
                ),
                'currency' => $currency->value,
            ];
        }

        $documentCurrency = Str::upper(trim((string) ($extracted['currency'] ?? '')));

        if ($documentCurrency !== '' && $documentCurrency !== $currency->value) {
            $warnings[] = __('messages.invoice_draft.currency_mismatch', [
                'document' => $documentCurrency,
                'invoice' => $currency->value,
            ]);
        }

        // A line the document gives no rate for gets what the business usually
        // charges; an export to an overseas customer is usually zero-rated.
        $taxOptions = collect($organization->country_code?->taxOptions() ?? []);
        $taxRates = $taxOptions->pluck('rate')->unique()->all();
        $exported = $customer?->isOverseas() && in_array(0, $taxRates, true);
        $fallbackRate = $exported ? 0 : $this->usualTaxRate($organization, $taxRates);
        $fallbackLabel = $taxOptions->firstWhere('rate', $fallbackRate)['name'] ?? ($fallbackRate / 100).'%';

        $items = $this->items((array) ($extracted['items'] ?? []), $currency, $taxRates, $fallbackRate, $fallbackLabel, $warnings);

        if (collect($items)->contains(fn (array $item): bool => $item['tax_rate'] === null)) {
            $warnings[] = __($exported ? 'messages.invoice_draft.export_tax_rate' : 'messages.invoice_draft.default_tax_rate', [
                'rate' => $fallbackLabel,
            ]);
        }

        $defaults['items'] = array_map(fn (array $item): array => [...$item, 'tax_rate' => $item['tax_rate'] ?? $fallbackRate], $items);

        if ($defaults['items'] === []) {
            $warnings[] = __('messages.invoice_draft.no_items');
        } else {
            $this->checkTotal($defaults['items'], $extracted['stated_total'] ?? null, $currency, $warnings);
        }

        $defaults['subject'] = Str::limit(trim((string) ($extracted['subject'] ?? '')), 255, '');
        $defaults['order_number'] = Str::limit(trim((string) ($extracted['order_number'] ?? '')), 255, '');

        if ($dueAt = $this->dueDate($extracted, $warnings)) {
            $defaults['due_at'] = $dueAt;
        }

        return new InvoiceDraft($defaults, $warnings, $source);
    }

    /**
     * Only this organization's customers are candidates, and the first test
     * that singles out exactly one of them wins: tax number, then email, then
     * name. An ambiguous match is no match, so the person picks.
     *
     * @param  array<string, mixed>  $party
     * @param  list<string>  $warnings
     */
    private function matchCustomer(Organization $organization, array $party, array &$warnings): ?Customer
    {
        $customers = Customer::query()
            ->where('organization_id', $organization->id)
            ->with('locations')
            ->get();

        $taxNumber = $this->normalizeTaxNumber($party['tax_number'] ?? null);
        $email = Str::lower(trim((string) ($party['email'] ?? '')));
        $name = $this->normalizeName($party['name'] ?? null);

        $tests = [
            fn (Customer $customer): bool => $taxNumber !== ''
                && $customer->locations->contains(fn (Location $location): bool => $this->normalizeTaxNumber($location->tax_id) === $taxNumber),
            fn (Customer $customer): bool => $email !== ''
                && in_array($email, array_map(Str::lower(...), $customer->emails?->getEmails() ?? []), true),
            fn (Customer $customer): bool => $name !== '' && $this->normalizeName($customer->name) === $name,
        ];

        foreach ($tests as $test) {
            $matches = $customers->filter($test);

            if ($matches->count() === 1) {
                return $matches->first();
            }
        }

        $label = trim((string) ($party['name'] ?? '')) ?: trim((string) ($party['tax_number'] ?? ''));

        $warnings[] = $label === ''
            ? __('messages.invoice_draft.no_customer')
            : __('messages.invoice_draft.unknown_customer', ['name' => $label]);

        return null;
    }

    /**
     * A line whose document states no tax rate is returned with a null rate,
     * for build() to fill in and say so once.
     *
     * @param  array<int, mixed>  $lines
     * @param  array<int, int>  $taxRates  basis points the organization charges
     * @param  list<string>  $warnings
     * @return list<array{description: string, sac_code: ?string, quantity: int, unit_price: int, tax_rate: ?int}>
     */
    private function items(array $lines, Currency $currency, array $taxRates, int $fallbackRate, string $fallbackLabel, array &$warnings): array
    {
        $items = [];

        foreach ($lines as $line) {
            $line = (array) $line;
            $description = trim((string) ($line['description'] ?? ''));
            $quantity = (float) ($line['quantity'] ?? 0);
            $unitPrice = $this->toMinorUnits($line['unit_price'] ?? null);

            if ($description === '' || $quantity <= 0 || $unitPrice === null) {
                $warnings[] = __('messages.invoice_draft.line_skipped', ['line' => Str::limit($description, 60) ?: '?']);

                continue;
            }

            // Quantities are whole numbers here, so 7.5 hours becomes one line
            // at the full amount, with the hours kept in the description.
            if (floor($quantity) !== $quantity) {
                $rate = $currency->formatAmount($unitPrice);
                $hours = rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.');

                $warnings[] = __('messages.invoice_draft.fractional_quantity', [
                    'line' => Str::limit($description, 60),
                    'quantity' => $hours,
                ]);

                $description .= " ({$hours} × {$rate})";
                $unitPrice = (int) round($quantity * $unitPrice);
                $quantity = 1;
            }

            if ($unitPrice === 0) {
                $warnings[] = __('messages.invoice_draft.no_price', ['line' => Str::limit($description, 60)]);
            }

            $percent = $line['tax_rate_percent'] ?? null;
            $taxRate = $percent === null ? null : (int) round((float) $percent * 100);

            if ($taxRate !== null && ! in_array($taxRate, $taxRates, true)) {
                $warnings[] = __('messages.invoice_draft.unknown_tax_rate', [
                    'line' => Str::limit($description, 60),
                    'rate' => $percent,
                    'default' => $fallbackLabel,
                ]);
                $taxRate = $fallbackRate;
            }

            $sacCode = Str::limit(trim((string) ($line['sac_code'] ?? '')), 20, '');

            $items[] = [
                'description' => Str::limit($description, 2000, ''),
                'sac_code' => $sacCode === '' ? null : $sacCode,
                'quantity' => (int) $quantity,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
            ];
        }

        return $items;
    }

    /**
     * The rate this organization charges most often; a new organization gets
     * its country's standard rate.
     *
     * @param  array<int, int>  $taxRates
     */
    private function usualTaxRate(Organization $organization, array $taxRates): int
    {
        $usual = InvoiceItem::query()
            ->whereHas('invoice', fn (Builder $query) => $query->where('organization_id', $organization->id))
            ->groupBy('tax_rate')
            ->orderByRaw('count(*) desc')
            ->value('tax_rate');

        return $usual !== null && in_array((int) $usual, $taxRates, true)
            ? (int) $usual
            : ($organization->country_code?->standardTaxRate() ?? 0);
    }

    /**
     * The document's own total is the best check on the lines: a misread
     * price, quantity or tax rate shows up as a difference here.
     *
     * @param  list<array{description: string, sac_code: ?string, quantity: int, unit_price: int, tax_rate: int}>  $items
     * @param  list<string>  $warnings
     */
    private function checkTotal(array $items, mixed $statedTotal, Currency $currency, array &$warnings): void
    {
        $stated = $this->toMinorUnits($statedTotal);

        if ($stated === null) {
            return;
        }

        $total = $this->calculator->calculateFromItems(new Collection(array_map(
            fn (array $item): InvoiceItem => new InvoiceItem($item),
            $items,
        )))->total;

        if (abs($total - $stated) > 1) {
            $warnings[] = __('messages.invoice_draft.total_mismatch', [
                'stated' => $currency->formatAmount($stated),
                'total' => $currency->formatAmount($total),
            ]);
        }
    }

    /**
     * Invoices are issued today, so a due date is taken from the document only
     * when it is not already past; otherwise its payment terms, otherwise the
     * create form's own default.
     *
     * @param  array<string, mixed>  $extracted
     * @param  list<string>  $warnings
     */
    private function dueDate(array $extracted, array &$warnings): ?string
    {
        $today = CarbonImmutable::today();
        $stated = (string) ($extracted['due_date'] ?? '');

        $dueAt = preg_match('/^\d{4}-\d{2}-\d{2}$/', $stated)
            ? CarbonImmutable::createFromFormat('!Y-m-d', $stated)
            : null;

        if ($dueAt && $dueAt->toDateString() === $stated) {
            if ($dueAt->gte($today)) {
                return $dueAt->toDateString();
            }

            $warnings[] = __('messages.invoice_draft.past_due_date', ['date' => $dueAt->format('j M Y')]);
        }

        $days = $extracted['payment_terms_days'] ?? null;

        if (is_int($days) && $days >= 0 && $days <= 365) {
            return $today->addDays($days)->toDateString();
        }

        return null;
    }

    /**
     * A negative amount, such as a discount line, has no place on a line item
     * here, so it is left for the person to handle rather than flipped.
     */
    private function toMinorUnits(mixed $amount): ?int
    {
        if (str_contains((string) $amount, '-')) {
            return null;
        }

        $clean = preg_replace('/[^0-9.]/', '', (string) $amount);

        if ($clean === '' || ! is_numeric($clean)) {
            return null;
        }

        return (int) round((float) $clean * 100);
    }

    private function normalizeTaxNumber(?string $taxNumber): string
    {
        return Str::upper(preg_replace('/\s+/', '', (string) $taxNumber));
    }

    /**
     * "Acme Pvt. Ltd." and "ACME Private Limited" are the same customer.
     */
    private function normalizeName(?string $name): string
    {
        $words = preg_split('/[^a-z0-9]+/', Str::lower((string) $name), -1, PREG_SPLIT_NO_EMPTY);
        $legalForms = ['the', 'pvt', 'private', 'ltd', 'limited', 'llp', 'llc', 'inc', 'co', 'company', 'corp', 'corporation', 'fze', 'fzco', 'gmbh'];

        return implode(' ', array_diff($words, $legalForms));
    }
}
