import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    Head: {
        name: 'Head',
        props: ['title'],
        template: '<div data-testid="head" />',
    },
    Link: { template: '<a><slot /></a>' },
    router: { post: vi.fn(), get: vi.fn() },
    usePage: () => ({
        props: {
            auth: { user: null },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

import GuestLayout from '@/Layouts/GuestLayout.vue';

describe('GuestLayout', () => {
    it('renders app name', () => {
        const wrapper = mount(GuestLayout);
        expect(wrapper.text()).toContain('InvoiceInk');
    });

    it('renders slot content', () => {
        const wrapper = mount(GuestLayout, {
            slots: { default: '<p>Login form here</p>' },
        });
        expect(wrapper.text()).toContain('Login form here');
    });

    it('app name links to /', () => {
        const wrapper = mount(GuestLayout);
        const link = wrapper.find('a');
        expect(link.attributes('href')).toBe('/');
    });

    it('accepts title prop', () => {
        const wrapper = mount(GuestLayout, {
            props: { title: 'Login' },
        });
        expect(wrapper.find('[data-testid="head"]').exists()).toBe(true);
    });
});
