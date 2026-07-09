import { describe, it, expect } from 'vitest';
import { cn, isValidEmail } from '@/lib/utils';

describe('cn', () => {
    it('merges class names', () => {
        expect(cn('foo', 'bar')).toBe('foo bar');
    });

    it('handles conditional classes', () => {
        expect(cn('base', false && 'hidden', 'visible')).toBe('base visible');
    });

    it('merges conflicting tailwind classes', () => {
        expect(cn('px-4', 'px-2')).toBe('px-2');
    });
});

describe('isValidEmail', () => {
    it('accepts well-formed addresses', () => {
        expect(isValidEmail('user@example.test')).toBe(true);
        expect(isValidEmail('  user.name+tag@sub.example.co  ')).toBe(true);
    });

    it('rejects malformed addresses', () => {
        expect(isValidEmail('')).toBe(false);
        expect(isValidEmail('not-an-email')).toBe(false);
        expect(isValidEmail('missing@domain')).toBe(false);
        expect(isValidEmail('@example.test')).toBe(false);
        expect(isValidEmail('spaces in@example.test')).toBe(false);
    });
});
