import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

const { mockPost } = vi.hoisted(() => ({
    mockPost: vi.fn(),
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
    router: { post: vi.fn(), get: vi.fn() },
    usePage: () => ({
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Test User',
                    email: 'test@test.test',
                    profile_photo_url: 'https://example.test/photo.jpg',
                },
            },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

vi.mock('@/Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /><slot name="header" /></div>' },
}));

vi.mock('@/routes/teams', () => ({
    store: { url: () => '/teams' },
}));

import Create from '@/Pages/Teams/Create.vue';

describe('Teams/Create', () => {
    beforeEach(() => {
        mockPost.mockClear();
    });

    it('renders Create Team heading', () => {
        const wrapper = mount(Create);
        expect(wrapper.text()).toContain('Create Team');
    });

    it('renders team name field', () => {
        const wrapper = mount(Create);
        expect(wrapper.find('#name').exists()).toBe(true);
    });

    it('displays team owner info', () => {
        const wrapper = mount(Create);
        expect(wrapper.text()).toContain('Test User');
        expect(wrapper.text()).toContain('test@test.test');
    });

    it('submits form via post', async () => {
        const wrapper = mount(Create);
        await wrapper.find('form').trigger('submit');
        expect(mockPost).toHaveBeenCalledWith('/teams', expect.any(Object));
    });

    it('has Create button', () => {
        const wrapper = mount(Create);
        expect(wrapper.find('button[type="submit"]').text()).toBe('Create');
    });
});
