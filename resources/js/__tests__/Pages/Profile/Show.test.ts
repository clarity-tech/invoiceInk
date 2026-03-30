import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    useForm: () => ({}),
    Head: { template: '<div />' },
    Link: { template: '<a><slot /></a>' },
    router: { post: vi.fn(), get: vi.fn() },
    usePage: () => ({
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Test',
                    email: 'test@test.test',
                    profile_photo_url: '',
                },
                currentTeam: {
                    id: 1,
                    name: 'Team',
                    company_name: 'Co',
                    currency: 'INR',
                    personal_team: false,
                },
            },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

vi.mock('@/Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /><slot name="header" /></div>' },
}));

vi.mock('@/Pages/Profile/Partials/UpdateProfileInformationForm.vue', () => ({
    default: { template: '<div data-testid="update-profile-form" />' },
}));
vi.mock('@/Pages/Profile/Partials/UpdatePasswordForm.vue', () => ({
    default: { template: '<div data-testid="update-password-form" />' },
}));
vi.mock('@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue', () => ({
    default: { template: '<div data-testid="two-factor-form" />' },
}));
vi.mock('@/Pages/Profile/Partials/LogoutOtherSessionsForm.vue', () => ({
    default: { template: '<div data-testid="logout-sessions-form" />' },
}));
vi.mock('@/Pages/Profile/Partials/DeleteUserForm.vue', () => ({
    default: { template: '<div data-testid="delete-user-form" />' },
}));

import Show from '@/Pages/Profile/Show.vue';

const defaultProps = {
    user: {
        id: 1,
        name: 'Test User',
        email: 'test@test.test',
        profile_photo_url: 'https://example.test/photo.jpg',
        profile_photo_path: null,
        email_verified_at: '2026-01-01',
        two_factor_secret: null,
        two_factor_confirmed_at: null,
    },
    sessions: [],
    confirmsTwoFactorAuthentication: false,
    canManageTwoFactorAuthentication: true,
    canUpdateProfileInformation: true,
    canUpdatePassword: true,
    hasAccountDeletionFeatures: true,
    sessionsEnabled: true,
    managesProfilePhotos: false,
};

describe('Profile/Show', () => {
    it('renders all sections when all features enabled', () => {
        const wrapper = mount(Show, { props: defaultProps });
        expect(
            wrapper.find('[data-testid="update-profile-form"]').exists(),
        ).toBe(true);
        expect(
            wrapper.find('[data-testid="update-password-form"]').exists(),
        ).toBe(true);
        expect(wrapper.find('[data-testid="two-factor-form"]').exists()).toBe(
            true,
        );
        expect(
            wrapper.find('[data-testid="logout-sessions-form"]').exists(),
        ).toBe(true);
        expect(wrapper.find('[data-testid="delete-user-form"]').exists()).toBe(
            true,
        );
    });

    it('hides profile form when canUpdateProfileInformation is false', () => {
        const wrapper = mount(Show, {
            props: { ...defaultProps, canUpdateProfileInformation: false },
        });
        expect(
            wrapper.find('[data-testid="update-profile-form"]').exists(),
        ).toBe(false);
    });

    it('hides password form when canUpdatePassword is false', () => {
        const wrapper = mount(Show, {
            props: { ...defaultProps, canUpdatePassword: false },
        });
        expect(
            wrapper.find('[data-testid="update-password-form"]').exists(),
        ).toBe(false);
    });

    it('hides 2FA form when canManageTwoFactorAuthentication is false', () => {
        const wrapper = mount(Show, {
            props: { ...defaultProps, canManageTwoFactorAuthentication: false },
        });
        expect(wrapper.find('[data-testid="two-factor-form"]').exists()).toBe(
            false,
        );
    });

    it('hides sessions form when sessionsEnabled is false', () => {
        const wrapper = mount(Show, {
            props: { ...defaultProps, sessionsEnabled: false },
        });
        expect(
            wrapper.find('[data-testid="logout-sessions-form"]').exists(),
        ).toBe(false);
    });

    it('hides delete form when hasAccountDeletionFeatures is false', () => {
        const wrapper = mount(Show, {
            props: { ...defaultProps, hasAccountDeletionFeatures: false },
        });
        expect(wrapper.find('[data-testid="delete-user-form"]').exists()).toBe(
            false,
        );
    });
});
