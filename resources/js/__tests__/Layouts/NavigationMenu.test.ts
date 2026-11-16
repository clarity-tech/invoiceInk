import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { nextTick, reactive } from 'vue';

const { mockRouterPost, mockRouterPut, pageState } = vi.hoisted(() => ({
    mockRouterPost: vi.fn(),
    mockRouterPut: vi.fn(),
    // Holds the reactive page object returned by usePage(). Reset per test so
    // mutating props mirrors how Inertia swaps shared props after navigation.
    // `ReturnType<typeof reactive>` without a type argument widens to `object`,
    // which has no `props` -- so the mock page could not be reached by type.
    pageState: {
        current: null as {
            props: Record<string, unknown>;
            url?: string;
        } | null,
    },
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
    create: { url: () => '/invoices/create' },
}));

vi.mock('@/routes/estimates', () => ({
    create: { url: () => '/estimates/create' },
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

        // Organization switching moved into the user menu (#57), so there is no
        // longer a separate trigger showing the current org name. What must
        // still hold is that the section marks the active organization, and
        // that the marking follows a switch rather than going stale.
        const teamButtons = wrapper.findAll(
            '[data-team-dropdown] .max-h-56 button',
        );
        expect(teamButtons.length).toBe(2);
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

    it('has mobile menu toggle button', async () => {
        const wrapper = mount(NavigationMenu);

        // Found by accessible name, not by breakpoint class: this test used to
        // select '.sm\\:hidden button' and broke when #57 moved the nav's
        // breakpoints, even though the button itself was unchanged.
        const mobileBtn = wrapper.find('[aria-label="Toggle navigation menu"]');
        expect(mobileBtn.exists()).toBe(true);

        // The panel it controls must exist and be wired to it.
        expect(mobileBtn.attributes('aria-controls')).toBe('mobile-nav-panel');
        expect(mobileBtn.attributes('aria-expanded')).toBe('false');

        await mobileBtn.trigger('click');
        expect(mobileBtn.attributes('aria-expanded')).toBe('true');
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

    it('puts the one-click theme switch in the bar, on desktop and on a phone', () => {
        const wrapper = mount(NavigationMenu);

        // One beside the user menu, one beside the menu button; neither
        // needs a menu opened first.
        expect(wrapper.findAll('[data-appearance-switch]')).toHaveLength(2);
        expect(
            wrapper.find('#mobile-nav-panel [data-appearance-switch]').exists(),
        ).toBe(false);
        expect(
            wrapper
                .find('[data-user-dropdown] [data-appearance-switch]')
                .exists(),
        ).toBe(false);
    });

    it('offers the theme toggle in both the user menu and the mobile panel', () => {
        const wrapper = mount(NavigationMenu);

        expect(
            wrapper
                .find('[data-user-dropdown] [data-appearance-toggle]')
                .exists(),
        ).toBe(true);
        expect(
            wrapper.find('#mobile-nav-panel [data-appearance-toggle]').exists(),
        ).toBe(true);
    });

    describe('tab bar', () => {
        function tabBar(wrapper: ReturnType<typeof mount>) {
            return wrapper.find('[data-tab-bar]');
        }

        /** Lets the deferred focus check run, then Vue re-render. */
        async function settle() {
            await new Promise((resolve) => setTimeout(resolve));
            await nextTick();
        }

        it('links the main sections and marks the current one', () => {
            const bar = tabBar(mount(NavigationMenu));

            expect(bar.exists()).toBe(true);
            expect(
                bar
                    .findAll('a')
                    .filter(
                        (link) => !link.element.closest('#tab-bar-new-menu'),
                    )
                    .map((link) => link.attributes('href')),
            ).toEqual(['/dashboard', '/invoices', '/customers']);
            expect(bar.find('a[aria-current="page"]').text()).toBe('Dashboard');
        });

        it('counts an estimate as part of Invoices', () => {
            pageState.current!.url = '/estimates/create';
            const bar = tabBar(mount(NavigationMenu));

            expect(bar.find('a[aria-current="page"]').text()).toBe('Invoices');
        });

        it('offers a new invoice or estimate from the + button', async () => {
            const bar = tabBar(mount(NavigationMenu));
            const button = bar.find('[aria-label="Create new"]');

            // Not rendered while closed, so the page's own create links are
            // the only ones a selector or a screen reader's link list finds.
            expect(button.attributes('aria-expanded')).toBe('false');
            expect(bar.find('#tab-bar-new-menu').exists()).toBe(false);

            await button.trigger('click');

            expect(button.attributes('aria-expanded')).toBe('true');
            expect(
                bar
                    .find('#tab-bar-new-menu')
                    .findAll('a')
                    .map((link) => link.attributes('href')),
            ).toEqual(['/invoices/create', '/estimates/create']);
        });

        it('leaves out the + button for someone who cannot create', () => {
            (
                pageState.current!.props.auth as {
                    currentTeam: { permissions: string[] };
                }
            ).currentTeam.permissions = ['view'];
            const bar = tabBar(mount(NavigationMenu));

            expect(bar.find('[aria-label="Create new"]').exists()).toBe(false);
        });

        it('opens the same panel as the hamburger from More', async () => {
            window.scrollTo = vi.fn();
            const wrapper = mount(NavigationMenu);
            const more = tabBar(wrapper).find(
                'button[aria-controls="mobile-nav-panel"]',
            );
            const burger = wrapper.find(
                '[aria-label="Toggle navigation menu"]',
            );

            await more.trigger('click');

            expect(more.attributes('aria-expanded')).toBe('true');
            expect(burger.attributes('aria-expanded')).toBe('true');
            expect(window.scrollTo).toHaveBeenCalled();
        });

        it('steps aside while a text field has focus, not for a checkbox', async () => {
            const wrapper = mount(NavigationMenu, { attachTo: document.body });
            const field = document.createElement('input');
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            document.body.append(field, checkbox);

            field.focus();
            await settle();
            expect(tabBar(wrapper).isVisible()).toBe(false);

            checkbox.focus();
            await settle();
            expect(tabBar(wrapper).isVisible()).toBe(true);

            field.remove();
            checkbox.remove();
            wrapper.unmount();
        });

        it('comes back after a tap when the focused field was removed without a focusout', async () => {
            const wrapper = mount(NavigationMenu, { attachTo: document.body });
            const field = document.createElement('input');
            document.body.append(field);

            field.focus();
            await settle();
            expect(tabBar(wrapper).isVisible()).toBe(false);

            // As on iOS: the tap that closes a sheet leaves focus on its field,
            // which then goes with the sheet. Stop the focusout reaching the
            // component, so only the tap can bring the bar back.
            const swallow = (event: Event) => event.stopImmediatePropagation();
            field.addEventListener('focusout', swallow);
            field.remove();
            document.body.click();
            await settle();

            expect(tabBar(wrapper).isVisible()).toBe(true);
            wrapper.unmount();
        });

        it('is left out on pages that have their own bottom bar', () => {
            const wrapper = mount(NavigationMenu, {
                props: { hideTabBar: true },
            });

            expect(tabBar(wrapper).exists()).toBe(false);
        });
    });
});
