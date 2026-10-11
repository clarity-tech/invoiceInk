import type { Currency } from '@/types';

const CURRENCY_CONFIG: Record<
    Currency,
    { symbol: string; locale: string; subunitName: string }
> = {
    INR: { symbol: '₹', locale: 'en-IN', subunitName: 'Paise' },
    USD: { symbol: '$', locale: 'en-US', subunitName: 'Cents' },
    EUR: { symbol: '€', locale: 'de-DE', subunitName: 'Cents' },
    GBP: { symbol: '£', locale: 'en-GB', subunitName: 'Pence' },
    AUD: { symbol: 'A$', locale: 'en-AU', subunitName: 'Cents' },
    CAD: { symbol: 'C$', locale: 'en-CA', subunitName: 'Cents' },
    SGD: { symbol: 'S$', locale: 'en-SG', subunitName: 'Cents' },
    JPY: { symbol: '¥', locale: 'ja-JP', subunitName: '' },
    AED: { symbol: 'AED ', locale: 'ar-AE', subunitName: 'Fils' },
};

/**
 * Format an amount in cents to a display string with currency symbol.
 */
export function formatMoney(amountInCents: number, currency: Currency): string {
    const config = CURRENCY_CONFIG[currency];

    if (!config) {
        return `${currency} ${(amountInCents / 100).toFixed(2)}`;
    }

    // JPY has no subunits
    if (currency === 'JPY') {
        return `${config.symbol}${amountInCents.toLocaleString(config.locale)}`;
    }

    const amount = amountInCents / 100;
    const formatted = amount.toLocaleString(config.locale, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    return `${config.symbol}${formatted}`;
}

/**
 * The same figure without its symbol.
 *
 * A column of amounts does not need the symbol on every line: the document has
 * one currency, and repeating it turns a column of numbers into a column of
 * strings that no longer line up on the decimal point. The currency is named
 * once, beside the total those figures add up to.
 */
export function formatAmount(
    amountInCents: number,
    currency: Currency,
): string {
    const config = CURRENCY_CONFIG[currency];

    if (!config) {
        return (amountInCents / 100).toFixed(2);
    }

    if (currency === 'JPY') {
        return amountInCents.toLocaleString(config.locale);
    }

    return (amountInCents / 100).toLocaleString(config.locale, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

/** Trimmed: AED carries a trailing space so it reads as a prefix. */
export function currencySymbol(currency: Currency): string {
    return (CURRENCY_CONFIG[currency]?.symbol ?? currency).trim();
}

export function useFormatMoney() {
    return { formatMoney, formatAmount, currencySymbol, CURRENCY_CONFIG };
}
