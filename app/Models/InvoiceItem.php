<?php

namespace App\Models;

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
            'tax_rate' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getTaxRateAttribute($value): ?float
    {
        if ($value === null) {
            return null;
        }

        if ($value === 0) {
            return 0.0;
        }

        // Convert from basis points to percentage for display (1800 → 18.00)
        return round($value / 100.0, 2);
    }

    public function setTaxRateAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['tax_rate'] = null;

            return;
        }

        // Convert from percentage to basis points for storage (18.50 → 1850)
        $this->attributes['tax_rate'] = (int) round((float) $value * 100);
    }

    public function getLineTotal(): int
    {
        return $this->quantity * $this->unit_price;
    }

    public function getTaxAmount(): int
    {
        // Get the raw stored value (basis points) to avoid double conversion
        $taxRateBasisPoints = $this->attributes['tax_rate'] ?? 0;

        if (! $taxRateBasisPoints) {
            return 0;
        }

        // tax_rate is stored in basis points (e.g., 1800 for 18%)
        // So we divide by 10000 to get the decimal (1800/10000 = 0.18)
        return (int) round(($this->getLineTotal() * $taxRateBasisPoints) / 10000);
    }

    public function getLineTotalWithTax(): int
    {
        return $this->getLineTotal() + $this->getTaxAmount();
    }
}
