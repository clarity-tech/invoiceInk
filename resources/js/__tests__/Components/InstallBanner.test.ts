import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { Component } from 'vue';
import {
    fireInstallPrompt,
    IPHONE_SAFARI_UA,
    loadInstallPrompt,
    stubInstallEnvironment,
} from '../helpers';

describe('InstallBanner', () => {
    let InstallBanner: Component;

    beforeEach(async () => {
        localStorage.clear();
        stubInstallEnvironment();
        (await loadInstallPrompt()).listenForInstallPrompt();
        // Imported after the reset, so it shares the fresh module above.
        InstallBanner = (await import('@/Components/InstallBanner.vue'))
            .default;
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
    });

    it('renders nothing when the app cannot be installed', () => {
        const wrapper = mount(InstallBanner);

        expect(wrapper.find('[data-testid="install-banner"]').exists()).toBe(
            false,
        );
    });

    it('offers Install and Not now when the browser can install the app', async () => {
        const { prompt } = fireInstallPrompt();

        const wrapper = mount(InstallBanner);
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('Install InvoiceInk');
        await wrapper
            .findAll('button')
            .find((b) => b.text() === 'Install')!
            .trigger('click');
        expect(prompt).toHaveBeenCalledOnce();
    });

    it('hides when Not now is pressed', async () => {
        fireInstallPrompt('dismissed');

        const wrapper = mount(InstallBanner);
        await wrapper
            .findAll('button')
            .find((b) => b.text() === 'Not now')!
            .trigger('click');

        expect(wrapper.find('[data-testid="install-banner"]').exists()).toBe(
            false,
        );
    });

    it('explains Add to Home Screen on iOS Safari, with a dismiss button', () => {
        stubInstallEnvironment({ userAgent: IPHONE_SAFARI_UA });

        const wrapper = mount(InstallBanner);

        expect(wrapper.text()).toContain('Add to Home Screen');
        expect(wrapper.find('button[aria-label="Dismiss"]').exists()).toBe(
            true,
        );
        expect(
            wrapper.findAll('button').some((b) => b.text() === 'Install'),
        ).toBe(false);
    });
});
