import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';

const { mockRouterPost } = vi.hoisted(() => ({
    mockRouterPost: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    Link: {
        name: 'Link',
        props: ['href'],
        template: '<a :href="href"><slot /></a>',
    },
    Head: { template: '<div />' },
    Form: { template: '<form><slot /></form>' },
    router: {
        post: mockRouterPost,
        get: vi.fn(),
        on: vi.fn(),
    },
    usePage: () => ({
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Test User',
                    email: 'test@test.test',
                    profile_photo_url: 'https://example.test/photo.jpg',
                },
                currentTeam: {
                    id: 1,
                    name: "Test's Team",
                    company_name: 'Test Co',
                },
            },
            flash: { success: null, error: null, message: null },
        },
        url: '/dashboard',
    }),
}));

vi.mock('@/routes', () => ({
    dashboard: { url: () => '/dashboard' },
    logout: { url: () => '/logout' },
}));

vi.mock('@/routes/customers', () => ({
    index: { url: () => '/customers' },
}));

vi.mock('@/routes/invoices', () => ({
    index: { url: () => '/invoices' },
}));

vi.mock('@/routes/numbering-series', () => ({
    index: { url: () => '/numbering-series' },
}));

vi.mock('@/routes/organizations', () => ({
    index: { url: () => '/organizations' },
}));

vi.mock('@/routes/profile', () => ({
    show: { url: () => '/user/profile' },
}));

import NavigationMenu from '@/Layouts/NavigationMenu.vue';

describe('NavigationMenu', () => {
    it('renders app name', () => {
        const wrapper = mount(NavigationMenu);
        expect(wrapper.text()).toContain('InvoiceInk');
    });

    it('renders nav links', () => {
        const wrapper = mount(NavigationMenu);
        expect(wrapper.text()).toContain('Dashboard');
        expect(wrapper.text()).toContain('Invoices');
        expect(wrapper.text()).toContain('Customers');
        expect(wrapper.text()).toContain('Organizations');
        expect(wrapper.text()).toContain('Settings');
    });

    it('renders current team name', () => {
        const wrapper = mount(NavigationMenu);
        expect(wrapper.text()).toContain('Test Co');
    });

    it('renders user avatar', () => {
        const wrapper = mount(NavigationMenu);
        const img = wrapper.find('img');
        expect(img.exists()).toBe(true);
        expect(img.attributes('src')).toBe('https://example.test/photo.jpg');
    });

    it('toggles user dropdown on click', async () => {
        const wrapper = mount(NavigationMenu);
        const avatarBtn = wrapper.find('[data-user-dropdown] button');
        await avatarBtn.trigger('click');
        expect(wrapper.text()).toContain('test@test.test');
        expect(wrapper.text()).toContain('Profile');
        expect(wrapper.text()).toContain('Log Out');
    });

    it('has mobile menu toggle button', () => {
        const wrapper = mount(NavigationMenu);
        // The mobile menu button exists with hamburger icon
        const mobileBtn = wrapper.find('.sm\\:hidden button');
        expect(mobileBtn.exists()).toBe(true);
    });

    it('calls router.post on logout', async () => {
        const wrapper = mount(NavigationMenu);
        // Open dropdown
        const avatarBtn = wrapper.find('[data-user-dropdown] button');
        await avatarBtn.trigger('click');
        // Find logout button in dropdown
        const logoutBtn = wrapper.find('[data-user-dropdown] button.block');
        await logoutBtn.trigger('click');
        expect(mockRouterPost).toHaveBeenCalledWith('/logout');
    });
});
