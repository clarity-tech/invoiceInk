<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
    ];

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
        if (!$this->tax_rate) {
            return 0;
        }

        return (int) round(($this->getLineTotal() * $this->tax_rate) / 100);
    }

    public function getLineTotalWithTax(): int
    {
        return $this->getLineTotal() + $this->getTaxAmount();
    }
}
