<?php

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

/**
 * A TDS rate a customer commonly deducts from what they pay us, offered as a
 * one-click fill when recording a payment. The customer picks the section;
 * these only suggest it.
 *
 * @implements Arrayable<string, string|int>
 */
readonly class TdsPreset implements Arrayable
{
    public function __construct(
        public string $section,
        public string $label,
        public int $rateBasisPoints,
    ) {}

    /**
     * Under 194C the rate depends on who is paid: 1% for an individual or HUF,
     * 2% for anyone else, read from the 4th letter of the payee's PAN. Without
     * a PAN both are offered. 194J does not depend on the payee.
     *
     * @return list<self>
     */
    public static function forPayeePan(?string $pan): array
    {
        $contract = match ($pan === null ? null : in_array($pan[3], ['P', 'H'], true)) {
            true => [new self('194C', 'Contract', 100)],
            false => [new self('194C', 'Contract', 200)],
            null => [
                new self('194C', 'Contract, individual/HUF', 100),
                new self('194C', 'Contract, company/firm', 200),
            ],
        };

        return [
            ...$contract,
            new self('194J', 'Technical', 200),
            new self('194J', 'Professional', 1000),
        ];
    }

    /**
     * @return array{section: string, label: string, rate: int}
     */
    public function toArray(): array
    {
        return [
            'section' => $this->section,
            'label' => $this->label,
            'rate' => $this->rateBasisPoints,
        ];
    }
}
