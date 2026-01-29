<?php

namespace App\Models;

use App\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'amount',
        'currency',
        'payment_date',
        'payment_method',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'currency' => Currency::class,
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function formatMoney(int $amount): string
    {
        return $this->currency->formatAmount($amount);
    }

    public function getFormattedAmountAttribute(): string
    {
        return $this->formatMoney($this->amount);
    }
}
