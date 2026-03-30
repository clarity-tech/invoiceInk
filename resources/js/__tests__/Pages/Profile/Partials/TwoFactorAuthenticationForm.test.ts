import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    useForm: () => ({}),
    Head: { template: '<div />' },
    Link: { template: '<a><slot /></a>' },
    router: { post: vi.fn(), get: vi.fn(), delete: vi.fn() },
    usePage: () => ({
        props: {
            auth: { user: null },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

vi.mock('@/routes/two-factor', () => ({
    enable: { url: () => '/user/two-factor-authentication' },
    disable: { url: () => '/user/two-factor-authentication' },
    confirm: { url: () => '/user/confirmed-two-factor-authentication' },
    qrCode: { url: () => '/user/two-factor-qr-code' },
    secretKey: { url: () => '/user/two-factor-secret-key' },
    recoveryCodes: { url: () => '/user/two-factor-recovery-codes' },
    regenerateRecoveryCodes: { url: () => '/user/two-factor-recovery-codes' },
}));

vi.mock('@/Components/ConfirmationModal.vue', () => ({
    default: { template: '<div />' },
}));

import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue';

describe('TwoFactorAuthenticationForm', () => {
    it('shows Enable button when 2FA is not enabled', () => {
        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: {
                user: {
                    two_factor_secret: null,
                    two_factor_confirmed_at: null,
                },
                confirmsTwoFactorAuthentication: true,
            },
        });
        expect(wrapper.text()).toContain(
            'You have not enabled two factor authentication',
        );
        expect(wrapper.text()).toContain('Enable');
    });

    it('shows enabled message when 2FA is active', () => {
        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: {
                user: {
                    two_factor_secret: 'secret123',
                    two_factor_confirmed_at: '2026-01-01',
                },
                confirmsTwoFactorAuthentication: true,
            },
        });
        expect(wrapper.text()).toContain(
            'You have enabled two factor authentication',
        );
        expect(wrapper.text()).toContain('Disable');
    });

    it('renders section heading', () => {
        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: {
                user: {
                    two_factor_secret: null,
                    two_factor_confirmed_at: null,
                },
                confirmsTwoFactorAuthentication: false,
            },
        });
        expect(wrapper.text()).toContain('Two Factor Authentication');
    });
});
