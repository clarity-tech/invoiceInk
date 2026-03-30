import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

const { mockRouterPost } = vi.hoisted(() => ({
    mockRouterPost: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: () => ({}),
    Head: { template: '<div />' },
    Form: {
        template:
            '<form @submit.prevent><slot :errors="{}" :processing="false" /></form>',
        props: ['method', 'action', 'resetOnSuccess'],
    },
    Link: { template: '<a><slot /></a>' },
    router: { post: mockRouterPost, get: vi.fn() },
    usePage: () => ({
        props: {
            auth: { user: null },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

vi.mock('@/routes', () => ({
    login: { url: () => '/login' },
    logout: { url: () => '/logout' },
}));

vi.mock('@/routes/verification', () => ({
    send: {
        url: () => '/email/verification-notification',
        form: () => ({
            method: 'post',
            action: '/email/verification-notification',
        }),
    },
}));

vi.mock('@/Layouts/GuestLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import VerifyEmail from '@/Pages/Auth/VerifyEmail.vue';

describe('VerifyEmail', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    function mountComponent(props = {}) {
        return mount(VerifyEmail, {
            props,
            global: { stubs: { Teleport: true } },
        });
    }

    it('renders verification instructions', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('verify your email address');
    });

    it('renders resend button', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('button[type="submit"]').text()).toBe(
            'Resend Verification Email',
        );
    });

    it('renders edit profile link', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Edit Profile');
    });

    it('renders logout button', () => {
        const wrapper = mountComponent();
        const buttons = wrapper.findAll('button[type="button"]');
        const logoutBtn = buttons.find((b) => b.text() === 'Log Out');
        expect(logoutBtn).toBeDefined();
    });

    it('displays verification link sent status', () => {
        const wrapper = mountComponent({ status: 'verification-link-sent' });
        expect(wrapper.text()).toContain(
            'A new verification link has been sent',
        );
    });

    it('does not display status message when not sent', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).not.toContain(
            'A new verification link has been sent',
        );
    });

    it('renders form element', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('form').exists()).toBe(true);
    });

    it('calls router.post on logout', async () => {
        const wrapper = mountComponent();
        const buttons = wrapper.findAll('button[type="button"]');
        const logoutBtn = buttons.find((b) => b.text() === 'Log Out');
        await logoutBtn!.trigger('click');
        expect(mockRouterPost).toHaveBeenCalledWith('/logout');
    });
});
