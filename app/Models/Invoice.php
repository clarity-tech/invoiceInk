<?php

namespace App\Models;

use Akaunting\Money\Money;
use App\Casts\ExchangeRateCast;
use App\Currency;
use App\Enums\InvoiceStatus;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Invoice extends Model implements HasMedia
{
    use HasFactory, HasUlids, InteractsWithMedia;

    protected $fillable = [
        'type',
        'ulid',
        'organization_id',
        'organization_location_id',
        'customer_id',
        'customer_location_id',
        'customer_shipping_location_id',
        'invoice_number',
        'invoice_numbering_series_id',
        'status',
        'issued_at',
        'due_at',
        'currency',
        'exchange_rate',
        'subtotal',
        'tax',
        'total',
        'amount_paid',
        'tax_type',
        'tax_breakdown',
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
        return $this->belongsTo(Customer::class);
    }

    public function customerLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'customer_location_id');
    }

    public function customerShippingLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'customer_shipping_location_id');
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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }
}
