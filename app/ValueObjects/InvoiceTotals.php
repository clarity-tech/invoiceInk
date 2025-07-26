<?php

namespace App\ValueObjects;

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
}