<?php

namespace App\Exceptions;

use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * No usable rate could be found for a conversion between two different
 * currencies -- not for the requested date, and not within the lookback window
 * either.
 *
 * Deliberately fatal rather than defaulted. Returning 1.0 here is what let a
 * broken upstream understate every AED invoice by ~26x for seven weeks, and the
 * stored result was indistinguishable afterwards from a legitimate
 * same-currency invoice.
 */
class ExchangeRateUnavailable extends RuntimeException
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
        public readonly string $date,
        public readonly int $lookbackDays,
    ) {
        parent::__construct("No exchange rate available for {$from} to {$to} on {$date}.");
    }

    /**
     * Laravel calls this when the exception goes unhandled, so the reporting
     * lives with the exception rather than being duplicated at each throw site.
     *
     * The structured context is the whole diagnostic here -- which pair, which
     * date, how far back we looked. The stack trace is always the same path and
     * would add nothing, so the default handler is not also invoked.
     */
    public function report(): void
    {
        Log::error('No exchange rate available', [
            'from' => $this->from,
            'to' => $this->to,
            'date' => $this->date,
            'lookback_days' => $this->lookbackDays,
        ]);
    }

    // Deliberately no render(). The only useful response is form-specific --
    // the invoice form attaches this to `issued_at` so the message lands beside
    // the field the user can actually change. A generic render() would give a
    // worse message and would quietly paper over a caller that forgot to handle
    // this, which is exactly the kind of silence that caused the original bug.
}
