import { describe, it, expect } from 'vitest';
import { formatDate, useFormatDate } from '@/composables/useFormatDate';

describe('formatDate', () => {
    it('formats a valid date string to DD Mon YYYY', () => {
        const result = formatDate('2026-03-15');
        expect(result).toMatch(/15.*Mar.*2026/);
    });

    it('returns dash for null', () => {
        expect(formatDate(null)).toBe('-');
    });

    it('returns dash for undefined', () => {
        expect(formatDate(undefined)).toBe('-');
    });

    it('returns dash for empty string', () => {
        expect(formatDate('')).toBe('-');
    });

    it('handles ISO datetime strings', () => {
        const result = formatDate('2026-01-05T10:30:00.000000Z');
        expect(result).toMatch(/05.*Jan.*2026/);
    });

    it('handles date-only strings', () => {
        const result = formatDate('2025-12-25');
        expect(result).toMatch(/25.*Dec.*2025/);
    });
});

describe('useFormatDate', () => {
    it('returns formatDate function', () => {
        const { formatDate: fn } = useFormatDate();
        expect(typeof fn).toBe('function');
    });
});
