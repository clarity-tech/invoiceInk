import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { reactive } from 'vue';

const { mockRouterPost, mockRouterPut, pageState } = vi.hoisted(() => ({
    mockRouterPost: vi.fn(),
    mockRouterPut: vi.fn(),
    // Holds the reactive page object returned by usePage(). Reset per test so
    // mutating props mirrors how Inertia swaps shared props after navigation.
    pageState: { current: null as ReturnType<typeof reactive> | null },
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
        put: mockRouterPut,
        get: vi.fn(),
        on: vi.fn(),
        flushAll: vi.fn(),
    },
    usePage: () => pageState.current,
}));

beforeEach(() => {
    pageState.current = reactive({
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Test User',
                    email: 'test@test.test',
                    profile_photo_url: 'https://example.test/photo.jpg',
                    all_teams: [
                        { id: 1, name: "Test's Team", company_name: 'Test Co' },
                    ],
                },
                currentTeam: {
                    id: 1,
                    name: "Test's Team",
                    company_name: 'Test Co',
                    role: 'owner',
                    permissions: ['*'],
                },
            },
            flash: { success: null, error: null, message: null },
        },
        url: '/dashboard',
    });
});

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

vi.mock('@/routes/organizations', () => ({
    index: { url: () => '/organizations' },
}));

vi.mock('@/routes/profile', () => ({
    show: { url: () => '/user/profile' },
}));

vi.mock('@/routes/settings', () => ({
    index: { url: () => '/settings' },
}));

vi.mock('@/routes/current-team', () => ({
    update: { url: () => '/current-team' },
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
        expect(wrapper.text()).toContain("Test's Team");
    });

    it('reactively updates the switcher when the current team prop changes', async () => {
        const wrapper = mount(NavigationMenu);
        expect(wrapper.text()).toContain("Test's Team");

        // Simulate Inertia swapping shared auth props after switching org.
        // Inertia replaces the whole `auth` object on navigation, so a
        // non-reactive snapshot of page.props.auth would go stale here.
        pageState.current!.props.auth = {
            user: {
                id: 1,
                name: 'Test User',
                email: 'test@test.test',
                profile_photo_url: 'https://example.test/photo.jpg',
                all_teams: [
                    { id: 1, name: "Test's Team", company_name: 'Test Co' },
                    { id: 2, name: 'Second Org', company_name: 'Second Co' },
                ],
            },
            currentTeam: {
                id: 2,
                name: 'Second Org',
                company_name: 'Second Co',
                role: 'owner',
                permissions: ['*'],
            },
        };
        await wrapper.vm.$nextTick();

        // The trigger label must follow the new current team, not stay stale.
        expect(wrapper.find('[data-team-dropdown] button').text()).toContain(
            'Second Org',
        );

        // The checkmark must move to the newly active org in the dropdown.
        const teamButtons = wrapper.findAll(
            '[data-team-dropdown] .max-h-72 button',
        );
        const secondOrgButton = teamButtons.find((b) =>
            b.text().includes('Second Org'),
        )!;
        const firstOrgButton = teamButtons.find((b) =>
            b.text().includes("Test's Team"),
        )!;
        expect(secondOrgButton.find('svg').exists()).toBe(true);
        expect(firstOrgButton.find('svg').exists()).toBe(false);
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
        const logoutBtn = wrapper
            .findAll('[data-user-dropdown] button')
            .find((b) => b.text().includes('Log Out'))!;
        await logoutBtn.trigger('click');
        expect(mockRouterPost).toHaveBeenCalledWith('/logout');
    });
});
