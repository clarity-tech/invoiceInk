<?php

namespace App\Models;

use Akaunting\Money\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getLineTotal(): int
    {
        return $this->quantity * $this->unit_price;
    }

    public function getTaxAmount(): int
    {
        $taxRatePercentage = $this->tax_rate ?? 0;

        if (! $taxRatePercentage) {
            return 0;
        }

        // tax_rate is stored as percentage (e.g., 18.00 for 18%)
        // So we divide by 100 to get the decimal (18.00/100 = 0.18)
        return (int) round(($this->getLineTotal() * $taxRatePercentage) / 100);
    }

    public function getLineTotalWithTax(): int
    {
        return $this->getLineTotal() + $this->getTaxAmount();
    }

    /**
     * Format a monetary amount using the invoice's currency
     */
    public function formatMoney(int $amount): string
    {
        // Get the currency, falling back to INR if invalid
        $currency = $this->invoice->currency ?? 'INR';

        // Validate the currency code and fallback to INR if invalid
        try {
            // Use the static method instead of make() to avoid potential conflicts
            return Money::{$currency}($amount)->format();
        } catch (\Exception $e) {
            // If currency is invalid, fallback to INR
            // Use manual formatting to avoid any further currency validation issues
            return '₹'.number_format($amount / 100, 2);
        }
    }

    /**
     * Format the unit price
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return $this->formatMoney($this->unit_price);
    }

    /**
     * Format the line total
     */
    public function getFormattedLineTotalAttribute(): string
    {
        return $this->formatMoney($this->getLineTotal());
    }

    /**
     * Format the tax amount for this line item
     */
    public function getFormattedTaxAmountAttribute(): string
    {
        return $this->formatMoney($this->getTaxAmount());
    }

    /**
     * Format the line total with tax
     */
    public function getFormattedLineTotalWithTaxAttribute(): string
    {
        return $this->formatMoney($this->getLineTotalWithTax());
    }

    /**
     * Get the currency symbol for this invoice item
     */
    public function getCurrencySymbolAttribute(): string
    {
        try {
            return Money::{$this->invoice->currency}(0)->getCurrency()->getSymbol();
        } catch (\Exception $e) {
            return '₹';
        }
    }
}
