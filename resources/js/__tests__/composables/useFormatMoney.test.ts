import { describe, it, expect } from 'vitest';
import { formatMoney, useFormatMoney } from '@/composables/useFormatMoney';
import type { Currency } from '@/types';

describe('formatMoney', () => {
    describe('INR (Indian grouping)', () => {
        it('formats basic amount', () => {
            expect(formatMoney(10050, 'INR')).toBe('₹100.50');
        });

        it('formats with Indian grouping (lakhs)', () => {
            expect(formatMoney(10000000, 'INR')).toBe('₹1,00,000.00');
        });

        it('formats zero', () => {
            expect(formatMoney(0, 'INR')).toBe('₹0.00');
        });

        it('formats negative amount', () => {
            expect(formatMoney(-5000, 'INR')).toBe('₹-50.00');
        });

        it('formats large amount (crores)', () => {
            expect(formatMoney(1000000000, 'INR')).toBe('₹1,00,00,000.00');
        });
    });

    describe('USD', () => {
        it('formats basic amount', () => {
            expect(formatMoney(10050, 'USD')).toBe('$100.50');
        });

        it('formats with thousands separator', () => {
            expect(formatMoney(100000, 'USD')).toBe('$1,000.00');
        });

        it('formats zero', () => {
            expect(formatMoney(0, 'USD')).toBe('$0.00');
        });
    });

    describe('EUR', () => {
        it('formats basic amount', () => {
            const result = formatMoney(10050, 'EUR');
            // German locale uses period for thousands, comma for decimal
            expect(result).toBe('€100,50');
        });

        it('formats with thousands separator', () => {
            const result = formatMoney(100000, 'EUR');
            expect(result).toBe('€1.000,00');
        });
    });

    describe('GBP', () => {
        it('formats basic amount', () => {
            expect(formatMoney(10050, 'GBP')).toBe('£100.50');
        });
    });

    describe('AUD', () => {
        it('formats basic amount', () => {
            expect(formatMoney(10050, 'AUD')).toBe('A$100.50');
        });
    });

    describe('CAD', () => {
        it('formats basic amount', () => {
            expect(formatMoney(10050, 'CAD')).toBe('C$100.50');
        });
    });

    describe('SGD', () => {
        it('formats basic amount', () => {
            expect(formatMoney(10050, 'SGD')).toBe('S$100.50');
        });
    });

    describe('JPY (no decimals)', () => {
        it('formats without decimals', () => {
            expect(formatMoney(1000, 'JPY')).toBe('¥1,000');
        });

        it('formats zero', () => {
            expect(formatMoney(0, 'JPY')).toBe('¥0');
        });

        it('formats large amount', () => {
            expect(formatMoney(1000000, 'JPY')).toBe('¥1,000,000');
        });
    });

    describe('AED', () => {
        it('formats basic amount', () => {
            const result = formatMoney(10050, 'AED');
            // ar-AE locale uses Arabic-Indic numerals or Western numerals depending on env
            expect(result).toContain('د.إ');
        });
    });

    describe('unknown currency fallback', () => {
        it('falls back to CODE + plain format', () => {
            expect(formatMoney(10050, 'XYZ' as Currency)).toBe('XYZ 100.50');
        });

        it('falls back for zero', () => {
            expect(formatMoney(0, 'UNKNOWN' as Currency)).toBe('UNKNOWN 0.00');
        });
    });
});

describe('useFormatMoney', () => {
    it('returns formatMoney function and config', () => {
        const { formatMoney: fn, CURRENCY_CONFIG } = useFormatMoney();
        expect(typeof fn).toBe('function');
        expect(Object.keys(CURRENCY_CONFIG)).toHaveLength(9);
    });
});
