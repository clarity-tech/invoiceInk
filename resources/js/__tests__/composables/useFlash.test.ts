import { describe, it, expect, vi, beforeEach } from 'vitest';

const mockUsePage = vi.fn();

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => mockUsePage(),
}));

import { useFlash } from '@/composables/useFlash';

describe('useFlash', () => {
    beforeEach(() => {
        mockUsePage.mockReturnValue({
            props: { flash: { success: null, error: null, message: null } },
        });
    });

    it('returns flash and hasFlash', () => {
        const { flash, hasFlash } = useFlash();
        expect(flash.value).toEqual({
            success: null,
            error: null,
            message: null,
        });
        expect(hasFlash.value).toBe(false);
    });

    it('detects success flash', () => {
        mockUsePage.mockReturnValue({
            props: { flash: { success: 'Saved!', error: null, message: null } },
        });
        const { flash, hasFlash } = useFlash();
        expect(flash.value.success).toBe('Saved!');
        expect(hasFlash.value).toBe(true);
    });

    it('detects error flash', () => {
        mockUsePage.mockReturnValue({
            props: {
                flash: { success: null, error: 'Failed!', message: null },
            },
        });
        const { flash, hasFlash } = useFlash();
        expect(flash.value.error).toBe('Failed!');
        expect(hasFlash.value).toBe(true);
    });

    it('detects message flash', () => {
        mockUsePage.mockReturnValue({
            props: { flash: { success: null, error: null, message: 'Info' } },
        });
        const { hasFlash } = useFlash();
        expect(hasFlash.value).toBe(true);
    });

    it('returns false when all flash values are null', () => {
        const { hasFlash } = useFlash();
        expect(hasFlash.value).toBe(false);
    });
});
