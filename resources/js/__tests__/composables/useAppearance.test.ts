import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { defineComponent, h } from 'vue';
import { useAppearance } from '@/composables/useAppearance';
import { resetAppearance, stubSystemTheme } from '../helpers';

function storedCookie(): string | undefined {
    return document.cookie.match(/(?:^|;\s*)appearance=([^;]*)/)?.[1];
}

/** The stored choice is read on mount, so the composable needs a component. */
function mountAppearance(): ReturnType<typeof useAppearance> {
    let result: ReturnType<typeof useAppearance> | undefined;

    mount(
        defineComponent({
            setup() {
                result = useAppearance();

                return () => h('div');
            },
        }),
    );

    return result!;
}

describe('useAppearance', () => {
    beforeEach(resetAppearance);

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('follows the system when nothing has been chosen', () => {
        const { appearance } = mountAppearance();

        expect(appearance.value).toBe('system');
    });

    it('reads the stored choice from the cookie', () => {
        document.cookie = 'appearance=dark; path=/';

        const { appearance } = mountAppearance();

        expect(appearance.value).toBe('dark');
    });

    it('treats an unknown stored value as following the system', () => {
        document.cookie = 'appearance=sepia; path=/';

        const { appearance } = mountAppearance();

        expect(appearance.value).toBe('system');
    });

    it('reads the cookie on mount, not during setup', () => {
        // A server-rendered toggle can only say `system`, and hydration does
        // not patch `checked`. Reading during setup would leave a stored
        // choice unchecked on a hydrated page.
        document.cookie = 'appearance=dark; path=/';
        let duringSetup: string | undefined;

        mount(
            defineComponent({
                setup() {
                    const { appearance } = useAppearance();
                    duringSetup = appearance.value;

                    return () => h('div');
                },
            }),
        );

        expect(duringSetup).toBe('system');
    });

    it('stores a choice in the cookie the server reads', () => {
        const { appearance, setAppearance } = mountAppearance();

        setAppearance('dark');

        expect(appearance.value).toBe('dark');
        expect(storedCookie()).toBe('dark');
    });

    it('turns dark mode on for an explicit dark choice', () => {
        const { setAppearance } = mountAppearance();

        setAppearance('dark');

        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });

    it('keeps light mode for an explicit light choice on a dark system', () => {
        stubSystemTheme(true);
        document.documentElement.classList.add('dark');
        const { setAppearance } = mountAppearance();

        setAppearance('light');

        expect(document.documentElement.classList.contains('dark')).toBe(false);
    });

    it('defers to the system setting for the system choice', () => {
        const { setAppearance } = mountAppearance();

        stubSystemTheme(true);
        setAppearance('system');
        expect(document.documentElement.classList.contains('dark')).toBe(true);

        stubSystemTheme(false);
        setAppearance('system');
        expect(document.documentElement.classList.contains('dark')).toBe(false);
    });

    it('shares one choice between every caller on the page', () => {
        const desktop = mountAppearance();
        const mobile = mountAppearance();

        desktop.setAppearance('light');

        expect(mobile.appearance.value).toBe('light');
    });
});
