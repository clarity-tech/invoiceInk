import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

const { mockPut } = vi.hoisted(() => ({
    mockPut: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial: Record<string, unknown>) => {
        const data = { ...initial };
        return new Proxy(data, {
            get(target, prop) {
                if (prop === 'post') return vi.fn();
                if (prop === 'put') return mockPut;
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
    router: { get: vi.fn(), post: vi.fn(), delete: vi.fn(), put: vi.fn() },
    usePage: () => ({
        props: {
            auth: { user: { id: 1, name: 'Test', email: 'test@test.test' } },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

vi.mock('@/routes/teams', () => ({
    update: { url: ({ team }: { team: number }) => `/teams/${team}` },
}));

import UpdateTeamNameForm from '@/Pages/Teams/Partials/UpdateTeamNameForm.vue';

const defaultProps = {
    team: {
        id: 1,
        name: 'Test Team',
        owner: {
            id: 2,
            name: 'Team Owner',
            email: 'owner@test.test',
            profile_photo_url: 'https://example.test/photo.jpg',
        },
    },
    canUpdate: true,
};

function mountComponent(propsOverride = {}) {
    return mount(UpdateTeamNameForm, {
        props: { ...defaultProps, ...propsOverride },
    });
}

describe('UpdateTeamNameForm', () => {
    beforeEach(() => {
        mockPut.mockClear();
    });

    it('renders Team Name heading', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Team Name');
    });

    it('renders team owner info', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Team Owner');
        expect(wrapper.text()).toContain('owner@test.test');
    });

    it('renders owner photo', () => {
        const wrapper = mountComponent();
        const img = wrapper.find('img');
        expect(img.exists()).toBe(true);
        expect(img.attributes('src')).toBe('https://example.test/photo.jpg');
    });

    it('renders name input with team name', () => {
        const wrapper = mountComponent();
        const input = wrapper.find('#name').element as HTMLInputElement;
        expect(input.value).toBe('Test Team');
    });

    it('submits form via put', async () => {
        const wrapper = mountComponent();
        await wrapper.find('form').trigger('submit');
        expect(mockPut).toHaveBeenCalledWith('/teams/1', expect.any(Object));
    });

    it('shows Save button when canUpdate is true', () => {
        const wrapper = mountComponent();
        const btn = wrapper.find('button[type="submit"]');
        expect(btn.exists()).toBe(true);
        expect(btn.text()).toContain('Save');
    });

    it('hides Save button when canUpdate is false', () => {
        const wrapper = mountComponent({ canUpdate: false });
        const btn = wrapper.find('button[type="submit"]');
        expect(btn.exists()).toBe(false);
    });

    it('disables input when canUpdate is false', () => {
        const wrapper = mountComponent({ canUpdate: false });
        const input = wrapper.find('#name').element as HTMLInputElement;
        expect(input.disabled).toBe(true);
    });

    it('enables input when canUpdate is true', () => {
        const wrapper = mountComponent();
        const input = wrapper.find('#name').element as HTMLInputElement;
        expect(input.disabled).toBe(false);
    });

    it('shows no validation errors by default', () => {
        const wrapper = mountComponent();
        const errors = wrapper.findAll('.text-red-600');
        expect(errors.length).toBe(0);
    });

    it('renders description text', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain(
            "The team's name and owner information",
        );
    });
});
