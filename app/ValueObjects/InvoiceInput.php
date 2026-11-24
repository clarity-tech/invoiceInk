<?php

namespace App\ValueObjects;

use App\Enums\InvoiceStatus;
use App\Services\InvoiceCreator;
use App\Support\EmailRecipients;
use Carbon\CarbonImmutable;

/**
 * Everything {@see InvoiceCreator} needs to create an invoice or estimate,
 * referenced by id so it never depends on whoever is signed in.
 *
 * What the creator works out itself is deliberately absent: the number, the
 * currency (always the customer's), the exchange rate and every amount.
 * Rates are basis points (1000 = 10%).
 */
readonly class InvoiceInput
{
    /**
     * Recipients are kept as submitted; {@see EmailRecipients::normalize()}
     * runs when the invoice is created.
     *
     * @param  'invoice'|'estimate'  $type
     * @param  list<InvoiceLineInput>  $lines
     * @param  array<int, array{email?: string, checked?: mixed}|string>  $emailRecipients
     */
    public function __construct(
        public string $type,
        public int $organizationId,
        public int $organizationLocationId,
        public int $customerId,
        public int $customerLocationId,
        public int $customerShippingLocationId,
        public ?int $numberingSeriesId,
        public InvoiceStatus $status,
        public CarbonImmutable $issuedAt,
        public CarbonImmutable $dueAt,
        public array $lines,
        public ?string $placeOfSupply = null,
        public ?int $tdsRate = null,
        public ?string $tdsSection = null,
        public ?int $tcsRate = null,
        public ?string $tcsSection = null,
        public ?string $subject = null,
        public ?string $orderNumber = null,
        public ?string $notes = null,
        public ?string $terms = null,
        public array $emailRecipients = [],
    ) {}

    /**
     * From the invoice form's validated input. A date-only value parses to
     * midnight UTC, which is how issue and due dates have always been stored.
     * A posted TDS or TCS rate of 0 stays 0; only a missing one is null.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            type: $validated['type'],
            organizationId: (int) $validated['organization_id'],
            organizationLocationId: (int) $validated['organization_location_id'],
            customerId: (int) $validated['customer_id'],
            customerLocationId: (int) $validated['customer_location_id'],
            customerShippingLocationId: (int) $validated['customer_shipping_location_id'],
            numberingSeriesId: ! empty($validated['invoice_numbering_series_id'])
                ? (int) $validated['invoice_numbering_series_id']
                : null,
            status: InvoiceStatus::from($validated['status']),
            issuedAt: CarbonImmutable::parse($validated['issued_at']),
            dueAt: CarbonImmutable::parse($validated['due_at']),
            lines: InvoiceLineInput::listFromValidated($validated['items']),
            placeOfSupply: $validated['place_of_supply'] ?? null,
            tdsRate: isset($validated['tds_rate']) ? (int) $validated['tds_rate'] : null,
            tdsSection: $validated['tds_section'] ?? null,
            tcsRate: isset($validated['tcs_rate']) ? (int) $validated['tcs_rate'] : null,
            tcsSection: $validated['tcs_section'] ?? null,
            subject: $validated['subject'] ?? null,
            orderNumber: $validated['order_number'] ?? null,
            notes: $validated['notes'] ?? null,
            terms: $validated['terms'] ?? null,
            emailRecipients: $validated['email_recipients'] ?? [],
        );
    }
}
