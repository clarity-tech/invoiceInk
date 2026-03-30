import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    useForm: () => ({}),
    Head: { template: '<div />' },
    Form: {
        template:
            '<form @submit.prevent><slot :errors="{}" :processing="false" /></form>',
        props: ['method', 'action', 'resetOnSuccess', 'transform'],
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
    update: {
        url: () => '/reset-password',
        form: () => ({ method: 'post', action: '/reset-password' }),
    },
    email: { url: () => '/forgot-password' },
    request: { url: () => '/forgot-password' },
}));

vi.mock('@/Layouts/GuestLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import ResetPassword from '@/Pages/Auth/ResetPassword.vue';

describe('ResetPassword', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    function mountComponent() {
        return mount(ResetPassword, {
            props: { token: 'test-token', email: 'user@example.test' },
            global: { stubs: { Teleport: true } },
        });
    }

    it('renders email, password, and confirm password fields', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('input#email').exists()).toBe(true);
        expect(wrapper.find('input#password').exists()).toBe(true);
        expect(wrapper.find('input#password_confirmation').exists()).toBe(true);
    });

    it('renders reset password button', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('button[type="submit"]').text()).toBe(
            'Reset Password',
        );
    });

    it('pre-fills email from props', () => {
        const wrapper = mountComponent();
        const emailInput = wrapper.find('input#email');
        expect(emailInput.element.value).toBe('user@example.test');
    });

    it('renders form element', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('form').exists()).toBe(true);
    });
});
