<?php

namespace App\ValueObjects;

use App\Models\InvoiceItem;

/**
 * One line of an invoice about to be created, in the units {@see InvoiceItem}
 * stores: money in integer cents, the GST rate in basis points (1800 = 18%).
 */
readonly class InvoiceLineInput
{
    public function __construct(
        public string $description,
        public ?string $sacCode,
        public int $quantity,
        public int $unitPrice,
        public int $taxRate,
    ) {}

    /**
     * Form input arrives as strings; a blank tax rate means no tax.
     *
     * @param  array{description: string, sac_code?: string|null, quantity: int|string, unit_price: int|string, tax_rate?: int|string|null}  $item
     */
    public static function fromValidated(array $item): self
    {
        return new self(
            description: $item['description'],
            sacCode: $item['sac_code'] ?? null,
            quantity: (int) $item['quantity'],
            unitPrice: (int) $item['unit_price'],
            taxRate: (int) ($item['tax_rate'] ?? 0),
        );
    }

    /**
     * @param  array<int, array{description: string, sac_code?: string|null, quantity: int|string, unit_price: int|string, tax_rate?: int|string|null}>  $items
     * @return list<self>
     */
    public static function listFromValidated(array $items): array
    {
        return array_values(array_map(self::fromValidated(...), $items));
    }
}
