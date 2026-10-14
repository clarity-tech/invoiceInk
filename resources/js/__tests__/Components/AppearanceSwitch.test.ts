import { flushPromises, mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import AppearanceSwitch from '@/Components/AppearanceSwitch.vue';
import { resetAppearance } from '../helpers';

function pressed(wrapper: ReturnType<typeof mount>): string | undefined {
    return wrapper.get('[data-appearance-switch]').attributes('aria-pressed');
}

describe('AppearanceSwitch', () => {
    beforeEach(resetAppearance);

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('is a labelled toggle button, off on a light page', async () => {
        const wrapper = mount(AppearanceSwitch);
        await flushPromises();
        const button = wrapper.get('[data-appearance-switch]');

        expect(button.attributes('aria-label')).toBe('Dark theme');
        expect(pressed(wrapper)).toBe('false');
        expect(button.attributes('title')).toBe('Switch to dark theme');
    });

    it('turns dark on in one click and stores it', async () => {
        const wrapper = mount(AppearanceSwitch);
        await flushPromises();

        await wrapper.get('[data-appearance-switch]').trigger('click');

        expect(document.documentElement.classList.contains('dark')).toBe(true);
        expect(document.cookie).toContain('appearance=dark');
        expect(pressed(wrapper)).toBe('true');
    });

    it('gives light from a page that is dark because the system is', async () => {
        // No stored choice: the head script made the page dark from the OS.
        document.documentElement.classList.add('dark');
        const wrapper = mount(AppearanceSwitch);
        await flushPromises();

        expect(pressed(wrapper)).toBe('true');

        await wrapper.get('[data-appearance-switch]').trigger('click');

        expect(document.documentElement.classList.contains('dark')).toBe(false);
        expect(document.cookie).toContain('appearance=light');
        expect(pressed(wrapper)).toBe('false');
    });

    it('keeps up when the operating system switches the page', async () => {
        const wrapper = mount(AppearanceSwitch);
        await flushPromises();

        // What the head script does when the system theme changes.
        document.documentElement.classList.add('dark');
        await flushPromises();

        expect(pressed(wrapper)).toBe('true');
    });
});
