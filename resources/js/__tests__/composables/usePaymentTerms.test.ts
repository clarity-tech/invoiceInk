import { describe, it, expect } from 'vitest';
import {
    CUSTOM_TERM,
    PAYMENT_TERM_OPTIONS,
    diffInDays,
    dueFromTerm,
    termFromDates,
} from '@/composables/usePaymentTerms';

describe('usePaymentTerms', () => {
    describe('PAYMENT_TERM_OPTIONS', () => {
        it('mirrors the server preset set plus Custom', () => {
            expect(PAYMENT_TERM_OPTIONS.map((o) => o.value)).toEqual([
                0,
                7,
                15,
                30,
                45,
                60,
                90,
                CUSTOM_TERM,
            ]);
        });

        it('labels day zero as Due on Receipt', () => {
            expect(PAYMENT_TERM_OPTIONS[0]).toEqual({
                value: 0,
                label: 'Due on Receipt',
            });
        });
    });

    describe('diffInDays', () => {
        it('counts whole days between two dates', () => {
            expect(diffInDays('2026-03-01', '2026-03-31')).toBe(30);
        });

        it('returns 0 for the same day', () => {
            expect(diffInDays('2026-03-01', '2026-03-01')).toBe(0);
        });

        it('spans month and year boundaries correctly', () => {
            expect(diffInDays('2026-12-31', '2027-01-01')).toBe(1);
        });

        it('is negative when due precedes issue', () => {
            expect(diffInDays('2026-03-31', '2026-03-01')).toBe(-30);
        });

        it('ignores any time component', () => {
            expect(
                diffInDays('2026-03-01T09:00:00', '2026-03-08T18:30:00'),
            ).toBe(7);
        });

        it('returns null for missing or invalid input', () => {
            expect(diffInDays('', '2026-03-01')).toBeNull();
            expect(diffInDays('2026-03-01', null)).toBeNull();
            expect(diffInDays('not-a-date', '2026-03-01')).toBeNull();
        });
    });

    describe('termFromDates', () => {
        it('maps a matching gap to its preset', () => {
            expect(termFromDates('2026-03-01', '2026-03-31')).toBe(30);
            expect(termFromDates('2026-03-01', '2026-03-01')).toBe(0);
            expect(termFromDates('2026-03-01', '2026-03-08')).toBe(7);
        });

        it('falls back to Custom for a non-preset gap', () => {
            expect(termFromDates('2026-03-01', '2026-03-21')).toBe(CUSTOM_TERM);
        });

        it('falls back to Custom for a negative gap', () => {
            expect(termFromDates('2026-03-31', '2026-03-01')).toBe(CUSTOM_TERM);
        });

        it('falls back to Custom when a date is missing', () => {
            expect(termFromDates('2026-03-01', '')).toBe(CUSTOM_TERM);
        });
    });

    describe('dueFromTerm', () => {
        it('adds the preset days to the issue date', () => {
            expect(dueFromTerm('2026-03-01', 30)).toBe('2026-03-31');
            expect(dueFromTerm('2026-03-01', 0)).toBe('2026-03-01');
        });

        it('rolls across month boundaries', () => {
            expect(dueFromTerm('2026-12-20', 15)).toBe('2027-01-04');
        });

        it('returns null when the issue date is missing or invalid', () => {
            expect(dueFromTerm('', 30)).toBeNull();
            expect(dueFromTerm('nope', 30)).toBeNull();
        });
    });

    describe('round-trip', () => {
        it('term -> due -> term is stable for every preset', () => {
            const issued = '2026-03-01';

            for (const option of PAYMENT_TERM_OPTIONS) {
                if (option.value === CUSTOM_TERM) {
                    continue;
                }

                const due = dueFromTerm(issued, option.value);
                expect(due).not.toBeNull();
                expect(termFromDates(issued, due!)).toBe(option.value);
            }
        });
    });
});
