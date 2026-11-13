import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const listeners = new Map<string, () => unknown>();

vi.mock('@inertiajs/vue3', () => ({
    router: {
        on: (event: string, callback: () => unknown) => {
            listeners.set(event, callback);

            return () => listeners.delete(event);
        },
    },
}));

import NetworkErrorNotice from '@/Components/NetworkErrorNotice.vue';

function emit(event: string): unknown {
    return listeners.get(event)!();
}

describe('NetworkErrorNotice', () => {
    beforeEach(() => listeners.clear());

    it('shows nothing until a visit fails to reach the server', () => {
        const wrapper = mount(NetworkErrorNotice);

        expect(wrapper.find('[data-testid="network-error"]').exists()).toBe(
            false,
        );
    });

    it('tells the person to check their connection and handles the error', async () => {
        const wrapper = mount(NetworkErrorNotice);

        expect(emit('networkError')).toBe(false);
        await wrapper.vm.$nextTick();

        expect(wrapper.get('[role="alert"]').text()).toContain(
            "Couldn't reach InvoiceInk",
        );
    });

    it('clears once a visit succeeds', async () => {
        const wrapper = mount(NetworkErrorNotice);
        emit('networkError');
        await wrapper.vm.$nextTick();

        emit('success');
        await wrapper.vm.$nextTick();

        expect(wrapper.find('[data-testid="network-error"]').exists()).toBe(
            false,
        );
    });

    it('can be dismissed', async () => {
        const wrapper = mount(NetworkErrorNotice);
        emit('networkError');
        await wrapper.vm.$nextTick();

        await wrapper.get('button[aria-label="Dismiss"]').trigger('click');

        expect(wrapper.find('[data-testid="network-error"]').exists()).toBe(
            false,
        );
    });

    it('stops listening when unmounted', () => {
        const wrapper = mount(NetworkErrorNotice);

        wrapper.unmount();

        expect(listeners.size).toBe(0);
    });
});
