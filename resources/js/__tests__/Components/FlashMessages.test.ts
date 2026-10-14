import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';

const mockUsePage = vi.fn();

// The component subscribes to Inertia's visit events, so the mock supplies a
// router whose `on` hands back an unsubscribe the way the real one does.
const mockRouterOn = vi.fn((_event: string, _callback: () => void) => vi.fn());

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => mockUsePage(),
    // Called rather than passed: `vi.mock` is hoisted above the const above,
    // so the factory must not read it until it runs.
    router: {
        on: (event: string, callback: () => void) =>
            mockRouterOn(event, callback),
    },
}));

import FlashMessages from '@/Components/FlashMessages.vue';

describe('FlashMessages', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        mockUsePage.mockReturnValue({
            props: { flash: { success: null, error: null, message: null } },
        });
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('renders nothing when no flash messages', () => {
        const wrapper = mount(FlashMessages);
        expect(wrapper.find('.rounded-md').exists()).toBe(false);
    });

    it('renders success flash with green styling', () => {
        mockUsePage.mockReturnValue({
            props: { flash: { success: 'Saved!', error: null, message: null } },
        });
        const wrapper = mount(FlashMessages);
        expect(wrapper.text()).toContain('Saved!');
        expect(wrapper.find('.bg-green-50').exists()).toBe(true);
    });

    it('renders error flash with red styling', () => {
        mockUsePage.mockReturnValue({
            props: {
                flash: { success: null, error: 'Failed!', message: null },
            },
        });
        const wrapper = mount(FlashMessages);
        expect(wrapper.text()).toContain('Failed!');
        expect(wrapper.find('.bg-red-50').exists()).toBe(true);
    });

    it('renders info message with blue styling', () => {
        mockUsePage.mockReturnValue({
            props: { flash: { success: null, error: null, message: 'FYI' } },
        });
        const wrapper = mount(FlashMessages);
        expect(wrapper.text()).toContain('FYI');
        expect(wrapper.find('.bg-blue-50').exists()).toBe(true);
    });

    it('auto-hides after 4 seconds', async () => {
        mockUsePage.mockReturnValue({
            props: { flash: { success: 'Done', error: null, message: null } },
        });
        const wrapper = mount(FlashMessages);
        expect(wrapper.find('.bg-green-50').exists()).toBe(true);

        vi.advanceTimersByTime(4000);
        await wrapper.vm.$nextTick();

        expect(wrapper.find('.bg-green-50').exists()).toBe(false);
    });

    /**
     * Saving twice on an edit page sends the same words back twice. Inertia
     * hands the component the previous object whenever a prop comes back
     * deep-equal, so nothing about the value changes and the second save used
     * to confirm itself silently. The visit event is what it listens to.
     */
    it('says it again when the next response repeats the message', async () => {
        mockUsePage.mockReturnValue({
            props: {
                flash: { success: 'Saved!', error: null, message: null },
            },
        });
        const wrapper = mount(FlashMessages);
        expect(wrapper.find('.bg-green-50').exists()).toBe(true);

        vi.advanceTimersByTime(4000);
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.bg-green-50').exists()).toBe(false);

        const [event, announce] = mockRouterOn.mock.calls[0];
        expect(event).toBe('success');

        announce();
        await wrapper.vm.$nextTick();

        expect(wrapper.find('.bg-green-50').exists()).toBe(true);
    });
});
