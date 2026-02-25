<?php

namespace App\ValueObjects;

use App\Currency;

readonly class InvoiceTotals
{
    public function __construct(
        public int $subtotal,
        public int $tax,
        public int $total
    ) {}

    public static function zero(): self
    {
        return new self(0, 0, 0);
    }

    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'total' => $this->total,
        ];
    }

    /**
     * Format the subtotal using the specified currency
     */
    public function formatSubtotal(string $currency = 'INR'): string
    {
        return Currency::from($currency)->formatAmount($this->subtotal);
    }

    /**
     * Format the tax using the specified currency
     */
    public function formatTax(string $currency = 'INR'): string
    {
        return Currency::from($currency)->formatAmount($this->tax);
    }

    /**
     * Format the total using the specified currency
     */
    public function formatTotal(string $currency = 'INR'): string
    {
        return Currency::from($currency)->formatAmount($this->total);
    }

    /**
     * Format all amounts using the specified currency
     */
    public function formatAll(string $currency = 'INR'): array
    {
        return [
            'subtotal' => $this->formatSubtotal($currency),
            'tax' => $this->formatTax($currency),
            'total' => $this->formatTotal($currency),
        ];
    }
}
