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

vi.mock('@/routes/password/confirm', () => ({
    store: {
        url: () => '/user/confirm-password',
        form: () => ({ method: 'post', action: '/user/confirm-password' }),
    },
}));

vi.mock('@/Layouts/GuestLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import ConfirmPassword from '@/Pages/Auth/ConfirmPassword.vue';

describe('ConfirmPassword', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    function mountComponent() {
        return mount(ConfirmPassword, {
            global: { stubs: { Teleport: true } },
        });
    }

    it('renders password field', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('input#password').exists()).toBe(true);
    });

    it('renders confirm button', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('button[type="submit"]').text()).toBe('Confirm');
    });

    it('renders secure area message', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('secure area of the application');
    });

    it('renders form element', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('form').exists()).toBe(true);
    });
});
