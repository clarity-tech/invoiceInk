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
    AED: { symbol: 'د.إ', locale: 'ar-AE', subunitName: 'Fils' },
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

export function useFormatMoney() {
    return { formatMoney, CURRENCY_CONFIG };
}
