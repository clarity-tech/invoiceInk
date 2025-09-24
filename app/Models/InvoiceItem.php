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
}
