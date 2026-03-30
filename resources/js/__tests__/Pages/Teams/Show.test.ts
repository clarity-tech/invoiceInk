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

vi.mock('@/Pages/Teams/Partials/UpdateTeamNameForm.vue', () => ({
    default: { template: '<div data-testid="update-team-name-form" />' },
}));
vi.mock('@/Pages/Teams/Partials/TeamMemberManager.vue', () => ({
    default: { template: '<div data-testid="team-member-manager" />' },
}));
vi.mock('@/Pages/Teams/Partials/DeleteTeamForm.vue', () => ({
    default: { template: '<div data-testid="delete-team-form" />' },
}));

import Show from '@/Pages/Teams/Show.vue';

const team = {
    id: 1,
    name: 'Test Team',
    personal_team: false,
    owner: {
        id: 1,
        name: 'Owner',
        email: 'owner@test.test',
        profile_photo_url: '',
    },
    users: [],
    team_invitations: [],
};

const defaultProps = {
    team,
    availableRoles: [],
    permissions: {
        canAddTeamMembers: true,
        canDeleteTeam: true,
        canRemoveTeamMembers: true,
        canUpdateTeam: true,
        canUpdateTeamMembers: true,
    },
    hasRoles: true,
    sendsTeamInvitations: true,
};

describe('Teams/Show', () => {
    it('renders Organization Settings heading', () => {
        const wrapper = mount(Show, { props: defaultProps });
        expect(wrapper.text()).toContain('Organization Settings');
    });

    it('renders UpdateTeamNameForm and TeamMemberManager', () => {
        const wrapper = mount(Show, { props: defaultProps });
        expect(
            wrapper.find('[data-testid="update-team-name-form"]').exists(),
        ).toBe(true);
        expect(
            wrapper.find('[data-testid="team-member-manager"]').exists(),
        ).toBe(true);
    });

    it('shows delete form when canDeleteTeam and not personal team', () => {
        const wrapper = mount(Show, { props: defaultProps });
        expect(wrapper.find('[data-testid="delete-team-form"]').exists()).toBe(
            true,
        );
    });

    it('hides delete form when canDeleteTeam is false', () => {
        const wrapper = mount(Show, {
            props: {
                ...defaultProps,
                permissions: {
                    ...defaultProps.permissions,
                    canDeleteTeam: false,
                },
            },
        });
        expect(wrapper.find('[data-testid="delete-team-form"]').exists()).toBe(
            false,
        );
    });

    it('hides delete form for personal team', () => {
        const wrapper = mount(Show, {
            props: {
                ...defaultProps,
                team: { ...team, personal_team: true },
            },
        });
        expect(wrapper.find('[data-testid="delete-team-form"]').exists()).toBe(
            false,
        );
    });
});
