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
            // tax_rate is now stored as integer basis points (18% = 1800)
            'tax_rate' => 'integer',
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
        $taxRateBasisPoints = $this->tax_rate ?? 0;

        if (! $taxRateBasisPoints) {
            return 0;
        }

        // tax_rate is stored as basis points (e.g., 1800 for 18%)
        // So we divide by 10000 to get the decimal (1800/10000 = 0.18)
        return (int) round(($this->getLineTotal() * $taxRateBasisPoints) / 10000);
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
        $currency = $this->invoice->currency->value;

        return Money::{$currency}($amount)->format();
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
        return Money::{$this->invoice->currency->value}(0)->getCurrency()->getSymbol();
    }

    /**
     * Format tax rate as percentage for display (basis points to percentage)
     */
    public function getFormattedTaxRateAttribute(): string
    {
        if (! $this->tax_rate) {
            return '0.00%';
        }

        // Convert basis points to percentage (1800 -> 18.00%)
        $percentage = $this->tax_rate / 100;

        return number_format($percentage, 2).'%';
    }
}
