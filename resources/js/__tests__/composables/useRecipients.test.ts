import { describe, it, expect } from 'vitest';
import {
    normalizeRecipients,
    mergeRecipients,
} from '@/composables/useRecipients';

describe('normalizeRecipients', () => {
    it('returns an empty array for null/undefined', () => {
        expect(normalizeRecipients(null)).toEqual([]);
        expect(normalizeRecipients(undefined)).toEqual([]);
    });

    it('coerces legacy plain-string emails to checked recipients', () => {
        expect(normalizeRecipients(['a@x.test', 'b@x.test'])).toEqual([
            { email: 'a@x.test', checked: true },
            { email: 'b@x.test', checked: true },
        ]);
    });

    it('preserves the checked flag on object entries', () => {
        expect(
            normalizeRecipients([
                { email: 'a@x.test', checked: false },
                { email: 'b@x.test', checked: true },
            ]),
        ).toEqual([
            { email: 'a@x.test', checked: false },
            { email: 'b@x.test', checked: true },
        ]);
    });

    it('trims emails and drops blanks', () => {
        expect(
            normalizeRecipients([{ email: '  a@x.test  ', checked: true }, '']),
        ).toEqual([{ email: 'a@x.test', checked: true }]);
    });

    it('dedupes case-insensitively, keeping the first occurrence', () => {
        expect(
            normalizeRecipients([
                { email: 'Dup@x.test', checked: true },
                { email: 'dup@x.test', checked: false },
            ]),
        ).toEqual([{ email: 'Dup@x.test', checked: true }]);
    });
});

describe('mergeRecipients', () => {
    const contacts = [
        { name: 'Primary', email: 'primary@x.test' },
        { name: 'Second', email: 'second@x.test' },
    ];

    it('defaults every contact to checked when nothing is remembered', () => {
        expect(mergeRecipients(contacts, [])).toEqual([
            { email: 'primary@x.test', checked: true },
            { email: 'second@x.test', checked: true },
        ]);
    });

    it('applies the remembered checked-state to matching contacts', () => {
        expect(
            mergeRecipients(contacts, [
                { email: 'primary@x.test', checked: false },
            ]),
        ).toEqual([
            { email: 'primary@x.test', checked: false },
            { email: 'second@x.test', checked: true },
        ]);
    });

    it('keeps remembered extras after the contacts', () => {
        expect(
            mergeRecipients(contacts, [
                { email: 'primary@x.test', checked: true },
                { email: 'extra@outside.test', checked: false },
            ]),
        ).toEqual([
            { email: 'primary@x.test', checked: true },
            { email: 'second@x.test', checked: true },
            { email: 'extra@outside.test', checked: false },
        ]);
    });

    it('matches contacts to remembered entries case-insensitively', () => {
        expect(
            mergeRecipients(
                [{ name: '', email: 'Primary@x.test' }],
                [{ email: 'primary@x.test', checked: false }],
            ),
        ).toEqual([{ email: 'Primary@x.test', checked: false }]);
    });
});
