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

vi.mock('@/routes/two-factor/login', () => ({
    store: {
        url: () => '/two-factor-challenge',
        form: () => ({ method: 'post', action: '/two-factor-challenge' }),
    },
}));

vi.mock('@/Layouts/GuestLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import TwoFactorChallenge from '@/Pages/Auth/TwoFactorChallenge.vue';

describe('TwoFactorChallenge', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    function mountComponent() {
        return mount(TwoFactorChallenge, {
            global: { stubs: { Teleport: true } },
        });
    }

    it('renders code input by default', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('input#code').exists()).toBe(true);
        expect(wrapper.find('input#recovery_code').exists()).toBe(false);
    });

    it('renders authenticator code instructions', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('authentication code');
    });

    it('renders login button', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('button[type="submit"]').text()).toBe('Log in');
    });

    it('toggles to recovery code mode', async () => {
        const wrapper = mountComponent();
        const toggleBtn = wrapper.find('button[type="button"]');
        expect(toggleBtn.text()).toBe('Use a recovery code');

        await toggleBtn.trigger('click');
        expect(wrapper.find('input#recovery_code').exists()).toBe(true);
        expect(wrapper.find('input#code').exists()).toBe(false);
        expect(wrapper.text()).toContain('emergency recovery codes');
    });

    it('toggles back to authentication code mode', async () => {
        const wrapper = mountComponent();
        const toggleBtn = wrapper.find('button[type="button"]');

        await toggleBtn.trigger('click');
        expect(toggleBtn.text()).toBe('Use an authentication code');

        await toggleBtn.trigger('click');
        expect(wrapper.find('input#code').exists()).toBe(true);
        expect(wrapper.find('input#recovery_code').exists()).toBe(false);
    });

    it('renders form element', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('form').exists()).toBe(true);
    });
});
