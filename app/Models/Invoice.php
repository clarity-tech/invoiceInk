<?php

namespace App\Models;

use Akaunting\Money\Money;
use App\Casts\ExchangeRateCast;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'type',
        'ulid',
        'organization_id',
        'organization_location_id',
        'customer_id',
        'customer_location_id',
        'invoice_number',
        'status',
        'issued_at',
        'due_at',
        'currency',
        'exchange_rate',
        'subtotal',
        'tax',
        'total',
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
            'currency' => \App\Currency::class,
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

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function isInvoice(): bool
    {
        return $this->type === 'invoice';
    }

    public function isEstimate(): bool
    {
        return $this->type === 'estimate';
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new OrganizationScope);
    }

    /**
     * Format a monetary amount using the invoice's currency
     */
    public function formatMoney(int $amount): string
    {
        $currency = $this->currency->value;

        return Money::{$currency}($amount)->format();
    }

    /**
     * Format the invoice subtotal
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return $this->formatMoney($this->subtotal);
    }

    /**
     * Format the invoice tax amount
     */
    public function getFormattedTaxAttribute(): string
    {
        return $this->formatMoney($this->tax);
    }

    /**
     * Format the invoice total
     */
    public function getFormattedTotalAttribute(): string
    {
        return $this->formatMoney($this->total);
    }

    /**
     * Get the currency symbol for this invoice
     */
    public function getCurrencySymbolAttribute(): string
    {
        return Money::{$this->currency->value}(0)->getCurrency()->getSymbol();
    }
}
