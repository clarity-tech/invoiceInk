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

import LogoutOtherSessionsForm from '@/Pages/Profile/Partials/LogoutOtherSessionsForm.vue';

const sessions = [
    {
        ip_address: '127.0.0.1',
        is_current_device: true,
        last_active: '1 minute ago',
        platform: 'Mac',
        browser: 'Chrome',
        is_desktop: true,
    },
    {
        ip_address: '192.168.1.1',
        is_current_device: false,
        last_active: '2 hours ago',
        platform: 'Windows',
        browser: 'Firefox',
        is_desktop: true,
    },
];

describe('LogoutOtherSessionsForm', () => {
    beforeEach(() => {
        mockDelete.mockClear();
    });

    it('renders Browser Sessions heading', () => {
        const wrapper = mount(LogoutOtherSessionsForm, {
            props: { sessions: [] },
        });
        expect(wrapper.text()).toContain('Browser Sessions');
    });

    it('renders session list', () => {
        const wrapper = mount(LogoutOtherSessionsForm, {
            props: { sessions },
        });
        expect(wrapper.text()).toContain('127.0.0.1');
        expect(wrapper.text()).toContain('This device');
        expect(wrapper.text()).toContain('192.168.1.1');
        expect(wrapper.text()).toContain('Last active');
    });

    it('renders logout button', () => {
        const wrapper = mount(LogoutOtherSessionsForm, {
            props: { sessions },
        });
        expect(wrapper.text()).toContain('Log Out Other Sessions');
    });

    it('shows modal when logout button clicked', async () => {
        const wrapper = mount(LogoutOtherSessionsForm, {
            props: { sessions },
            global: { stubs: { Teleport: true } },
        });
        await wrapper.find('button').trigger('click');
        expect(wrapper.text()).toContain('Please enter your password');
    });
});
