import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    useForm: () => ({}),
    Head: { template: '<div />' },
    Form: {
        template:
            '<form @submit.prevent><slot :errors="{}" :processing="false" /></form>',
        props: ['method', 'action', 'resetOnSuccess'],
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

vi.mock('@/routes/password', () => ({
    email: {
        url: () => '/forgot-password',
        form: () => ({ method: 'post', action: '/forgot-password' }),
    },
    request: { url: () => '/forgot-password' },
    update: { url: () => '/reset-password' },
}));

vi.mock('@/Layouts/GuestLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import ForgotPassword from '@/Pages/Auth/ForgotPassword.vue';

describe('ForgotPassword', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    function mountComponent(props = {}) {
        return mount(ForgotPassword, {
            props,
            global: { stubs: { Teleport: true } },
        });
    }

    it('renders email field', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('input#email').exists()).toBe(true);
    });

    it('renders submit button', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('button[type="submit"]').text()).toBe(
            'Email Password Reset Link',
        );
    });

    it('renders instructional text', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Forgot your password?');
    });

    it('displays status message when provided', () => {
        const wrapper = mountComponent({ status: 'Reset link sent!' });
        expect(wrapper.text()).toContain('Reset link sent!');
    });

    it('renders form element', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('form').exists()).toBe(true);
    });
});
