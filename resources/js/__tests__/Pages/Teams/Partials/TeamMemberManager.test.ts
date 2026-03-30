import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

const { mockPost, mockRouterDelete } = vi.hoisted(() => ({
    mockPost: vi.fn(),
    mockRouterDelete: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial: Record<string, unknown>) => {
        const data = { ...initial };
        return new Proxy(data, {
            get(target, prop) {
                if (prop === 'post') return mockPost;
                if (prop === 'put') return vi.fn();
                if (prop === 'delete') return vi.fn();
                if (prop === 'errors') return {};
                if (prop === 'processing') return false;
                if (prop === 'reset') return vi.fn();
                if (prop === 'clearErrors') return vi.fn();
                if (prop === 'transform') return vi.fn().mockReturnThis();
                return target[prop as string];
            },
            set(target, prop, value) {
                target[prop as string] = value;
                return true;
            },
        });
    },
    Head: { template: '<div />' },
    Link: { template: '<a><slot /></a>' },
    router: {
        get: vi.fn(),
        post: vi.fn(),
        delete: mockRouterDelete,
        put: vi.fn(),
    },
    usePage: () => ({
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Test User',
                    email: 'test@test.test',
                    profile_photo_url: '',
                },
            },
            user: {
                id: 1,
                name: 'Test User',
                email: 'test@test.test',
                profile_photo_url: '',
            },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

import TeamMemberManager from '@/Pages/Teams/Partials/TeamMemberManager.vue';

const roles = [
    {
        key: 'admin',
        name: 'Administrator',
        description: 'Can do everything',
        permissions: ['*'],
    },
    {
        key: 'editor',
        name: 'Editor',
        description: 'Can edit content',
        permissions: ['read', 'write'],
    },
];

const defaultProps = {
    team: {
        id: 1,
        name: 'Test Team',
        owner: {
            id: 2,
            name: 'Owner',
            email: 'owner@test.test',
            profile_photo_url: '',
        },
        users: [
            {
                id: 1,
                name: 'Test User',
                email: 'test@test.test',
                profile_photo_url: '',
                membership: { role: 'admin' },
            },
            {
                id: 3,
                name: 'Other Member',
                email: 'other@test.test',
                profile_photo_url: '',
                membership: { role: 'editor' },
            },
        ],
        team_invitations: [] as { id: number; email: string }[],
    },
    availableRoles: roles,
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

function mountComponent(propsOverride = {}) {
    return mount(TeamMemberManager, {
        props: { ...defaultProps, ...propsOverride },
        global: {
            stubs: {
                ConfirmationModal: {
                    template:
                        '<div v-if="show" data-testid="modal"><button data-testid="confirm-btn" @click="$emit(\'confirm\')">Confirm</button><button data-testid="cancel-btn" @click="$emit(\'cancel\')">Cancel</button></div>',
                    props: [
                        'show',
                        'title',
                        'message',
                        'confirmLabel',
                        'destructive',
                    ],
                    emits: ['confirm', 'cancel'],
                },
                Teleport: { template: '<div><slot /></div>' },
            },
        },
    });
}

describe('TeamMemberManager', () => {
    beforeEach(() => {
        mockPost.mockClear();
        mockRouterDelete.mockClear();
    });

    it('renders Add Team Member heading', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Add Team Member');
    });

    it('renders Team Members heading', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Team Members');
    });

    it('renders member list', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Test User');
        expect(wrapper.text()).toContain('Other Member');
    });

    it('renders email input for adding members', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('#member-email').exists()).toBe(true);
    });

    it('renders role options', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Administrator');
        expect(wrapper.text()).toContain('Editor');
    });

    it('submits add member form via post', async () => {
        const wrapper = mountComponent();
        await wrapper.find('form').trigger('submit');
        expect(mockPost).toHaveBeenCalledWith(
            '/teams/1/members',
            expect.any(Object),
        );
    });

    it('shows Add button', () => {
        const wrapper = mountComponent();
        const btn = wrapper.find('button[type="submit"]');
        expect(btn.text()).toContain('Add');
    });

    it('shows Leave button for current user', () => {
        const wrapper = mountComponent();
        const leaveBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Leave');
        expect(leaveBtn).toBeDefined();
    });

    it('shows Remove button for other members when permitted', () => {
        const wrapper = mountComponent();
        const removeBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Remove');
        expect(removeBtn).toBeDefined();
    });

    it('hides add member section when not permitted', () => {
        const wrapper = mountComponent({
            permissions: {
                ...defaultProps.permissions,
                canAddTeamMembers: false,
            },
        });
        expect(wrapper.text()).not.toContain('Add Team Member');
    });

    it('shows pending invitations when present', () => {
        const wrapper = mountComponent({
            team: {
                ...defaultProps.team,
                team_invitations: [{ id: 1, email: 'invited@test.test' }],
            },
        });
        expect(wrapper.text()).toContain('Pending Team Invitations');
        expect(wrapper.text()).toContain('invited@test.test');
    });

    it('calls router.delete when cancelling invitation', async () => {
        const wrapper = mountComponent({
            team: {
                ...defaultProps.team,
                team_invitations: [{ id: 5, email: 'invited@test.test' }],
            },
        });
        const cancelBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Cancel');
        await cancelBtn?.trigger('click');
        expect(mockRouterDelete).toHaveBeenCalledWith(
            '/teams/1/invitations/5',
            expect.any(Object),
        );
    });

    it('shows role name for members', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Administrator');
        expect(wrapper.text()).toContain('Editor');
    });

    it('shows no validation errors by default', () => {
        const wrapper = mountComponent();
        const errors = wrapper.findAll('.text-red-600');
        expect(errors.length).toBe(0);
    });
});
