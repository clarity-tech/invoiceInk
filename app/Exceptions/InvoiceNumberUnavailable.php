<?php

namespace App\Exceptions;

use App\Enums\NumberingMode;
use App\Models\InvoiceNumberingSeries;
use InvalidArgumentException;
use RuntimeException;

/**
 * An invoice number could not be taken from the series it had to come from.
 *
 * Only strict numbering throws this ({@see NumberingMode::Strict}). The
 * invoice form falls back to a dated number instead, but an invoice issued
 * with nobody watching must not invent one in another format: that leaves a
 * gap in the series the organization files its GST returns against.
 *
 * Always thrown before a number is committed, so the series counter is where
 * it was and the caller can retry or hand the decision to a person without
 * cleaning anything up.
 */
class InvoiceNumberUnavailable extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $seriesId = null,
        ?InvalidArgumentException $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    public static function seriesNotInOrganization(int $seriesId, int $organizationId): self
    {
        return new self(
            "Numbering series {$seriesId} does not belong to organization {$organizationId}.",
            $seriesId,
        );
    }

    public static function seriesInactive(InvoiceNumberingSeries $series): self
    {
        return new self("Numbering series '{$series->name}' is inactive.", $series->id);
    }

    /**
     * The numbering service refused: financial year setup is missing for an FY
     * pattern, or no free number could be found. Its message says which.
     */
    public static function refused(InvalidArgumentException $reason, ?int $seriesId = null): self
    {
        return new self($reason->getMessage(), $seriesId, $reason);
    }
}
