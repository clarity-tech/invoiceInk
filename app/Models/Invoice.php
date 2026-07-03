<?php

namespace App\Models;

use Akaunting\Money\Money;
use App\Casts\ExchangeRateCast;
use App\Currency;
use App\Enums\IndianState;
use App\Enums\InvoiceStatus;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Invoice extends Model implements HasMedia
{
    use HasFactory, HasUlids, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'type',
        'subject',
        'ulid',
        'organization_id',
        'organization_location_id',
        'customer_id',
        'customer_location_id',
        'customer_shipping_location_id',
        'invoice_number',
        'order_number',
        'invoice_numbering_series_id',
        'status',
        'issued_at',
        'due_at',
        'currency',
        'place_of_supply',
        'exchange_rate',
        'subtotal',
        'tax',
        'total',
        'amount_paid',
        'tax_type',
        'tax_breakdown',
        'tds_rate',
        'tds_amount',
        'tds_section',
        'tcs_rate',
        'tcs_amount',
        'tcs_section',
        'email_recipients',
        'notes',
        'terms',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'exchange_rate' => ExchangeRateCast::class,
            'currency' => Currency::class,
            'status' => InvoiceStatus::class,
            'tax_breakdown' => 'json',
            'tds_rate' => 'integer',
            'tds_amount' => 'integer',
            'tcs_rate' => 'integer',
            'tcs_amount' => 'integer',
            'email_recipients' => 'json',
        ];
    }

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function organizationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'organization_location_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function customerLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'customer_location_id')->withTrashed();
    }

    public function customerShippingLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'customer_shipping_location_id')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isInvoice(): bool
    {
        return $this->type === 'invoice';
    }

    public function isEstimate(): bool
    {
        return $this->type === 'estimate';
    }

    public function typeLabel(): string
    {
        return ucfirst($this->type);
    }

    /** @param Builder<Invoice> $query */
    public function scopeInvoices(Builder $query): Builder
    {
        return $query->where('type', 'invoice');
    }

    /** @param Builder<Invoice> $query */
    public function scopeEstimates(Builder $query): Builder
    {
        return $query->where('type', 'estimate');
    }

    /** @param Builder<Invoice> $query */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::DRAFT);
    }

    /** @param Builder<Invoice> $query */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::PAID);
    }

    /** @param Builder<Invoice> $query */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', '!=', InvoiceStatus::PAID)
            ->where('status', '!=', InvoiceStatus::VOID)
            ->where('due_at', '<', now());
    }

    /** @param Builder<Invoice> $query */
    public function scopePartiallyPaid(Builder $query): Builder
    {
        return $query->whereColumn('amount_paid', '>', 0)
            ->whereColumn('amount_paid', '<', 'total');
    }

    /** @param Builder<Invoice> $query */
    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('amount_paid', 0);
    }

    /** @param Builder<Invoice> $query */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function numberingSeries(): BelongsTo
    {
        return $this->belongsTo(InvoiceNumberingSeries::class, 'invoice_numbering_series_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new OrganizationScope);
    }

    public function formatMoney(int $amount): string
    {
        return $this->currency->formatAmount($amount);
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return $this->formatMoney($this->subtotal);
    }

    public function getFormattedTaxAttribute(): string
    {
        return $this->formatMoney($this->tax);
    }

    public function hasTds(): bool
    {
        return (int) $this->tds_amount > 0;
    }

    public function hasTcs(): bool
    {
        return (int) $this->tcs_amount > 0;
    }

    public function getFormattedTdsAmountAttribute(): string
    {
        return $this->formatMoney((int) $this->tds_amount);
    }

    public function getFormattedTcsAmountAttribute(): string
    {
        return $this->formatMoney((int) $this->tcs_amount);
    }

    /**
     * Document label such as "TDS @ 10% (194J)", or null when no TDS applies.
     */
    public function tdsLabel(): ?string
    {
        return $this->hasTds()
            ? $this->sourceTaxLabel('TDS', $this->tds_rate, $this->tds_section)
            : null;
    }

    /**
     * Document label such as "TCS @ 0.1% (206C(1H))", or null when no TCS applies.
     */
    public function tcsLabel(): ?string
    {
        return $this->hasTcs()
            ? $this->sourceTaxLabel('TCS', $this->tcs_rate, $this->tcs_section)
            : null;
    }

    private function sourceTaxLabel(string $type, ?int $rateBasisPoints, ?string $section): string
    {
        $rate = sprintf('%g', (int) $rateBasisPoints / 100);
        $label = "{$type} @ {$rate}%";

        return $section ? "{$label} ({$section})" : $label;
    }

    public function getFormattedTotalAttribute(): string
    {
        return $this->formatMoney($this->total);
    }

    public function getRemainingBalanceAttribute(): int
    {
        return max(0, $this->total - $this->amount_paid);
    }

    public function getFormattedAmountPaidAttribute(): string
    {
        return $this->formatMoney($this->amount_paid);
    }

    public function getFormattedRemainingBalanceAttribute(): string
    {
        return $this->formatMoney($this->remaining_balance);
    }

    public function isFullyPaid(): bool
    {
        return $this->amount_paid >= $this->total;
    }

    public function isPartiallyPaid(): bool
    {
        return $this->amount_paid > 0 && $this->amount_paid < $this->total;
    }

    /**
     * Recalculate amount_paid from payments and update status accordingly.
     */
    public function recalculatePaymentStatus(): void
    {
        $totalPaid = $this->payments()->sum('amount');

        $status = match (true) {
            $totalPaid >= $this->total => InvoiceStatus::PAID,
            $totalPaid > 0 => InvoiceStatus::PARTIALLY_PAID,
            default => InvoiceStatus::SENT,
        };

        $this->update([
            'amount_paid' => $totalPaid,
            'status' => $status,
        ]);
    }

    public function getPaymentPercentageAttribute(): float
    {
        if ($this->total === 0) {
            return 100.0;
        }

        return round(($this->amount_paid / $this->total) * 100, 1);
    }

    public function getCurrencySymbolAttribute(): string
    {
        return Money::{$this->currency->value}(0)->getCurrency()->getSymbol();
    }

    public function isGstInvoice(): bool
    {
        $orgLocation = $this->organizationLocation;

        return $this->isInvoice() && ! empty($orgLocation?->tax_id) && (bool) $orgLocation?->isIndian();
    }

    public function hasShippingAddress(): bool
    {
        $shipping = $this->customerShippingLocation;

        return $shipping !== null && $shipping->id !== $this->customerLocation?->id;
    }

    /**
     * The GST place of supply as "State (code)", or null when not applicable
     * (no state set, or the customer is an overseas/export supply).
     */
    public function placeOfSupplyLabel(): ?string
    {
        if ($this->customer?->isOverseas() || ! $this->place_of_supply) {
            return null;
        }

        return IndianState::tryFrom($this->place_of_supply)?->displayName()
            ?? $this->place_of_supply;
    }

    public function paymentTermsLabel(): ?string
    {
        if (! $this->issued_at || ! $this->due_at) {
            return null;
        }

        $daysDiff = (int) $this->issued_at->diffInDays($this->due_at);

        return match ($daysDiff) {
            0 => 'Due on Receipt',
            7 => 'Net 7',
            15 => 'Net 15',
            30 => 'Net 30',
            45 => 'Net 45',
            60 => 'Net 60',
            90 => 'Net 90',
            default => "Due in {$daysDiff} days",
        };
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    /**
     * Lightweight list of the invoice's attachments for the frontend. The URL
     * points at an authorized view route rather than the raw storage URL.
     *
     * @return array<int, array{id: int, name: string, size: int, mime: string, extension: string, url: string}>
     */
    public function attachmentsForDisplay(): array
    {
        return $this->getMedia('attachments')
            ->map(fn (Media $media): array => [
                'id' => $media->id,
                'name' => $media->file_name,
                'size' => $media->size,
                'mime' => $media->mime_type ?? 'application/octet-stream',
                'extension' => strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION)),
                'url' => route('invoices.attachments.show', [
                    'invoice' => $this->id,
                    'media' => $media->id,
                ]),
            ])
            ->all();
    }
}
