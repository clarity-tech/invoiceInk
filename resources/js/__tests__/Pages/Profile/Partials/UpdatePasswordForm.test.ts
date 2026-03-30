import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

const { mockPut, mockReset } = vi.hoisted(() => ({
    mockPut: vi.fn(),
    mockReset: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial: Record<string, unknown>) => {
        const data = { ...initial };
        return new Proxy(data, {
            get(target, prop) {
                if (prop === 'put') return mockPut;
                if (prop === 'post') return vi.fn();
                if (prop === 'delete') return vi.fn();
                if (prop === 'errors') return {};
                if (prop === 'processing') return false;
                if (prop === 'reset') return mockReset;
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

vi.mock('@/routes/user-password', () => ({
    update: { url: () => '/user/password' },
}));

import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';

describe('UpdatePasswordForm', () => {
    beforeEach(() => {
        mockPut.mockClear();
        mockReset.mockClear();
    });

    it('renders password fields', () => {
        const wrapper = mount(UpdatePasswordForm);
        expect(wrapper.find('#current_password').exists()).toBe(true);
        expect(wrapper.find('#password').exists()).toBe(true);
        expect(wrapper.find('#password_confirmation').exists()).toBe(true);
    });

    it('renders Update Password heading', () => {
        const wrapper = mount(UpdatePasswordForm);
        expect(wrapper.text()).toContain('Update Password');
    });

    it('submits form via put', async () => {
        const wrapper = mount(UpdatePasswordForm);
        await wrapper.find('form').trigger('submit');
        expect(mockPut).toHaveBeenCalledWith(
            '/user/password',
            expect.any(Object),
        );
    });

    it('has Save button', () => {
        const wrapper = mount(UpdatePasswordForm);
        expect(wrapper.find('button[type="submit"]').text()).toBe('Save');
    });
});
