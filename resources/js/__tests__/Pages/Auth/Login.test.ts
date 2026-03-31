import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

const mockPost = vi.fn();

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
            errors: {},
            auth: { user: null },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

vi.mock('@/routes/login', () => ({
    store: {
        url: () => '/login',
        form: () => ({ method: 'post', action: '/login' }),
    },
}));

vi.mock('@/routes/password', () => ({
    request: { url: () => '/forgot-password' },
    email: { url: () => '/forgot-password' },
    update: { url: () => '/reset-password' },
}));

vi.mock('@/Layouts/GuestLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Login from '@/Pages/Auth/Login.vue';

describe('Login', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    function mountLogin(props = {}) {
        return mount(Login, {
            props,
            global: { stubs: { Teleport: true } },
        });
    }

    it('renders email and password fields', () => {
        const wrapper = mountLogin();
        expect(wrapper.find('input#email').exists()).toBe(true);
        expect(wrapper.find('input#password').exists()).toBe(true);
    });

    it('renders login button', () => {
        const wrapper = mountLogin();
        expect(wrapper.find('button[type="submit"]').text()).toBe('Log in');
    });

    it('renders remember me checkbox', () => {
        const wrapper = mountLogin();
        expect(wrapper.find('input#remember_me').exists()).toBe(true);
        expect(wrapper.text()).toContain('Remember me');
    });

    it('renders forgot password link', () => {
        const wrapper = mountLogin();
        expect(wrapper.text()).toContain('Forgot your password?');
    });

    it('displays status message when provided', () => {
        const wrapper = mountLogin({ status: 'Password reset link sent!' });
        expect(wrapper.text()).toContain('Password reset link sent!');
    });

    it('renders form element', () => {
        const wrapper = mountLogin();
        expect(wrapper.find('form').exists()).toBe(true);
    });

    it('toggles password visibility', async () => {
        const wrapper = mountLogin();
        const passwordInput = wrapper.find('input#password');
        expect(passwordInput.attributes('type')).toBe('password');

        const toggleBtn = wrapper.find('button[type="button"]');
        await toggleBtn.trigger('click');
        expect(wrapper.find('input#password').attributes('type')).toBe('text');

        await toggleBtn.trigger('click');
        expect(wrapper.find('input#password').attributes('type')).toBe(
            'password',
        );
    });

    it('submit button is not disabled by default', () => {
        const wrapper = mountLogin();
        expect(
            wrapper.find('button[type="submit"]').attributes('disabled'),
        ).toBeUndefined();
    });
});
