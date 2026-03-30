import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

const { mockDelete } = vi.hoisted(() => ({
    mockDelete: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial: Record<string, unknown>) => {
        const data = { ...initial };
        return new Proxy(data, {
            get(target, prop) {
                if (prop === 'delete') return mockDelete;
                if (prop === 'post') return vi.fn();
                if (prop === 'put') return vi.fn();
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
            auth: { user: null },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue';

describe('DeleteUserForm', () => {
    beforeEach(() => {
        mockDelete.mockClear();
    });

    it('renders Delete Account heading', () => {
        const wrapper = mount(DeleteUserForm);
        expect(wrapper.text()).toContain('Delete Account');
        expect(wrapper.text()).toContain('Permanently delete your account');
    });

    it('renders delete button', () => {
        const wrapper = mount(DeleteUserForm);
        const btn = wrapper.find('button');
        expect(btn.text()).toBe('Delete Account');
    });

    it('shows confirmation modal when delete button clicked', async () => {
        const wrapper = mount(DeleteUserForm, {
            global: { stubs: { Teleport: true } },
        });
        await wrapper.find('button').trigger('click');
        expect(wrapper.text()).toContain(
            'Are you sure you want to delete your account',
        );
    });
});
