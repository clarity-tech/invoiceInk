/**
 * Payment-term helpers shared by the invoice/estimate form.
 *
 * These mirror the server-side `Invoice::paymentTermsLabel()` PHP `match`
 * exactly, so the dropdown selection and the label rendered on the PDF/public
 * view always agree — both are derived from the day-diff between the issue and
 * due dates. Nothing here is persisted; the term is purely a UI helper that
 * drives (and reflects) the due date.
 */

/** Sentinel used when the day-diff doesn't map onto a named preset. */
export const CUSTOM_TERM = 'custom' as const;

export type PaymentTerm = number | typeof CUSTOM_TERM;

export interface PaymentTermOption {
    value: PaymentTerm;
    label: string;
}

/**
 * Selectable payment terms, ordered as shown in the dropdown. Preset day-counts
 * match the server's `Invoice::paymentTermsLabel()` set; "Custom" is appended
 * for any non-preset gap (e.g. a manually chosen due date).
 */
export const PAYMENT_TERM_OPTIONS: readonly PaymentTermOption[] = [
    { value: 0, label: 'Due on Receipt' },
    { value: 7, label: 'Net 7' },
    { value: 15, label: 'Net 15' },
    { value: 30, label: 'Net 30' },
    { value: 45, label: 'Net 45' },
    { value: 60, label: 'Net 60' },
    { value: 90, label: 'Net 90' },
    { value: CUSTOM_TERM, label: 'Custom' },
];

/** Preset day-counts that map onto a named term. */
const PRESET_DAYS: readonly number[] = PAYMENT_TERM_OPTIONS.map(
    (option) => option.value,
).filter((value): value is number => value !== CUSTOM_TERM);

const MS_PER_DAY = 86_400_000;

/**
 * Parse a `YYYY-MM-DD` date string to a UTC epoch, ignoring any time component.
 * Returns `null` for empty or unparseable input. Parsing at UTC midnight keeps
 * the day-diff free of DST/timezone drift.
 */
function parseUtcDate(date: string | null | undefined): number | null {
    if (!date) {
        return null;
    }

    const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(date);

    if (!match) {
        return null;
    }

    const [, year, month, day] = match;
    const epoch = Date.UTC(Number(year), Number(month) - 1, Number(day));

    return Number.isNaN(epoch) ? null : epoch;
}

/**
 * Whole-day difference between two `YYYY-MM-DD` dates (`due - issued`).
 * Returns `null` when either date is missing or invalid. May be negative if the
 * due date precedes the issue date.
 */
export function diffInDays(
    issued: string | null | undefined,
    due: string | null | undefined,
): number | null {
    const issuedEpoch = parseUtcDate(issued);
    const dueEpoch = parseUtcDate(due);

    if (issuedEpoch === null || dueEpoch === null) {
        return null;
    }

    return Math.round((dueEpoch - issuedEpoch) / MS_PER_DAY);
}

/**
 * Resolve the payment term implied by a pair of dates: a preset day-count when
 * the gap matches one, otherwise {@link CUSTOM_TERM}.
 */
export function termFromDates(
    issued: string | null | undefined,
    due: string | null | undefined,
): PaymentTerm {
    const days = diffInDays(issued, due);

    if (days === null || !PRESET_DAYS.includes(days)) {
        return CUSTOM_TERM;
    }

    return days;
}

/**
 * Compute the due date for a preset term as `issued + days`, returned as a
 * `YYYY-MM-DD` string. Returns `null` when the issue date is missing/invalid.
 */
export function dueFromTerm(
    issued: string | null | undefined,
    days: number,
): string | null {
    const issuedEpoch = parseUtcDate(issued);

    if (issuedEpoch === null) {
        return null;
    }

    return new Date(issuedEpoch + days * MS_PER_DAY).toISOString().slice(0, 10);
}
