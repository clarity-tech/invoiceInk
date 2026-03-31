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
    router: { post: vi.fn(), get: vi.fn(), delete: vi.fn() },
    usePage: () => ({
        props: {
            auth: { user: { id: 1, name: 'Test', email: 'test@test.test' } },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

vi.mock('@/routes/user-profile-information', () => ({
    update: { url: () => '/user/profile-information' },
}));

import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue';

const defaultProps = {
    user: {
        id: 1,
        name: 'Test User',
        email: 'test@test.test',
        profile_photo_url: 'https://example.test/photo.jpg',
        profile_photo_path: null,
        email_verified_at: '2026-01-01',
    },
    managesProfilePhotos: false,
};

describe('UpdateProfileInformationForm', () => {
    beforeEach(() => {
        mockPost.mockClear();
    });

    it('renders name and email fields', () => {
        const wrapper = mount(UpdateProfileInformationForm, {
            props: defaultProps,
        });
        expect(wrapper.find('#name').exists()).toBe(true);
        expect(wrapper.find('#email').exists()).toBe(true);
    });

    it('renders Profile Information heading', () => {
        const wrapper = mount(UpdateProfileInformationForm, {
            props: defaultProps,
        });
        expect(wrapper.text()).toContain('Profile Information');
    });

    it('populates fields with user data', () => {
        const wrapper = mount(UpdateProfileInformationForm, {
            props: defaultProps,
        });
        const nameInput = wrapper.find('#name').element as HTMLInputElement;
        const emailInput = wrapper.find('#email').element as HTMLInputElement;
        expect(nameInput.value).toBe('Test User');
        expect(emailInput.value).toBe('test@test.test');
    });

    it('submits form via post', async () => {
        const wrapper = mount(UpdateProfileInformationForm, {
            props: defaultProps,
        });
        await wrapper.find('form').trigger('submit');
        expect(mockPost).toHaveBeenCalledWith(
            '/user/profile-information',
            expect.any(Object),
        );
    });

    it('shows unverified email notice when email_verified_at is null', () => {
        const wrapper = mount(UpdateProfileInformationForm, {
            props: {
                ...defaultProps,
                user: { ...defaultProps.user, email_verified_at: null },
            },
        });
        expect(wrapper.text()).toContain('Your email address is unverified');
    });

    it('does not show photo section when managesProfilePhotos is false', () => {
        const wrapper = mount(UpdateProfileInformationForm, {
            props: defaultProps,
        });
        expect(wrapper.text()).not.toContain('Change Photo');
    });

    it('shows photo section when managesProfilePhotos is true', () => {
        const wrapper = mount(UpdateProfileInformationForm, {
            props: { ...defaultProps, managesProfilePhotos: true },
        });
        expect(wrapper.text()).toContain('Change Photo');
    });
});
