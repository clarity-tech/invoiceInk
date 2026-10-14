import { describe, it, expect } from 'vitest';
import { overdueLabel } from '@/composables/useOverdueLabel';

describe('overdueLabel', () => {
    it('counts the days a document is late', () => {
        expect(overdueLabel({ days_overdue: 87 })).toBe('Overdue by 87 days');
    });

    it('says day rather than days at one', () => {
        expect(overdueLabel({ days_overdue: 1 })).toBe('Overdue by 1 day');
    });

    // The case the detail page's own copy of this did not have: it counted the
    // days unconditionally, so a document that fell due today read "Overdue by
    // 0 days".
    it('names the day instead of counting nought of them', () => {
        expect(overdueLabel({ days_overdue: 0 })).toBe('Overdue today');
    });

    it('treats an unreported count as today rather than printing it', () => {
        expect(overdueLabel({ days_overdue: null })).toBe('Overdue today');
        expect(overdueLabel({})).toBe('Overdue today');
    });
});
