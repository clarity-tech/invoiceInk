<?php

namespace App\ValueObjects;

use Akaunting\Money\Money;

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
        return Money::{$currency}($this->subtotal)->format();
    }

    /**
     * Format the tax using the specified currency
     */
    public function formatTax(string $currency = 'INR'): string
    {
        return Money::{$currency}($this->tax)->format();
    }

    /**
     * Format the total using the specified currency
     */
    public function formatTotal(string $currency = 'INR'): string
    {
        return Money::{$currency}($this->total)->format();
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
