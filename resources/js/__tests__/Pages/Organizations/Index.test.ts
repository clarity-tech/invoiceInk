import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    Link: { template: '<a :href="href"><slot /></a>', props: ['href'] },
    Head: { template: '<div />' },
    usePage: () => ({
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Test',
                    email: 'test@test.test',
                    profile_photo_url: '',
                    two_factor_enabled: false,
                },
                currentTeam: {
                    id: 1,
                    name: 'Team',
                    company_name: 'Co',
                    currency: 'INR',
                    personal_team: false,
                },
            },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

import OrganizationsIndex from '@/Pages/Organizations/Index.vue';

interface Org {
    id: number;
    name: string;
    company_name: string | null;
    currency: string | null;
    country_code: string | null;
    logo_url: string | null;
    primary_location: { city: string; state: string } | null;
    personal_team: boolean;
}

function makeOrg(overrides: Partial<Org> = {}): Org {
    return {
        id: 1,
        name: 'Clarity Technologies',
        company_name: 'Clarity Tech Pvt Ltd',
        currency: 'INR',
        country_code: 'IN',
        logo_url: null,
        primary_location: { city: 'Mumbai', state: 'Maharashtra' },
        personal_team: false,
        ...overrides,
    };
}

function makePaginated(data: Org[] = [], overrides = {}) {
    return {
        data,
        current_page: 1,
        last_page: 1,
        links: [
            { url: null, label: '&laquo; Previous', active: false },
            { url: '/organizations?page=1', label: '1', active: true },
            { url: null, label: 'Next &raquo;', active: false },
        ],
        ...overrides,
    };
}

function mountComponent(propsOverride = {}) {
    return mount(OrganizationsIndex, {
        props: { organizations: makePaginated(), ...propsOverride },
        global: {
            stubs: {
                AppLayout: {
                    template: '<div><slot name="header" /><slot /></div>',
                },
            },
        },
    });
}

describe('Organizations/Index', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders page title', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Organizations');
    });

    it('renders description text', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain(
            'Select an organization to view or manage its settings',
        );
    });

    it('renders organization cards', () => {
        const org = makeOrg({ name: 'ACME Corp' });
        const wrapper = mountComponent({ organizations: makePaginated([org]) });
        expect(wrapper.text()).toContain('ACME Corp');
    });

    it('shows company name when different from name', () => {
        const org = makeOrg({
            name: 'My Team',
            company_name: 'Real Company Ltd',
        });
        const wrapper = mountComponent({ organizations: makePaginated([org]) });
        expect(wrapper.text()).toContain('Real Company Ltd');
    });

    it('hides company name when same as name', () => {
        const org = makeOrg({ name: 'Same Name', company_name: 'Same Name' });
        const wrapper = mountComponent({ organizations: makePaginated([org]) });
        // company_name should appear only once (in the h3), not duplicated
        const matches = wrapper.text().split('Same Name').length - 1;
        expect(matches).toBe(1);
    });

    it('shows currency badge', () => {
        const org = makeOrg({ currency: 'USD' });
        const wrapper = mountComponent({ organizations: makePaginated([org]) });
        expect(wrapper.text()).toContain('USD');
    });

    it('shows location city and state', () => {
        const org = makeOrg({
            primary_location: { city: 'Dubai', state: 'Dubai' },
        });
        const wrapper = mountComponent({ organizations: makePaginated([org]) });
        expect(wrapper.text()).toContain('Dubai');
    });

    it('shows "No location" when no primary_location', () => {
        const org = makeOrg({ primary_location: null });
        const wrapper = mountComponent({ organizations: makePaginated([org]) });
        expect(wrapper.text()).toContain('No location');
    });

    it('shows empty state when no organizations', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('No organizations found');
    });

    it('hides empty state when organizations exist', () => {
        const wrapper = mountComponent({
            organizations: makePaginated([makeOrg()]),
        });
        expect(wrapper.text()).not.toContain('No organizations found');
    });

    it('renders logo image when logo_url is set', () => {
        const org = makeOrg({ logo_url: '/logos/test.png' });
        const wrapper = mountComponent({ organizations: makePaginated([org]) });
        const img = wrapper.find('img');
        expect(img.exists()).toBe(true);
        expect(img.attributes('src')).toBe('/logos/test.png');
    });

    it('renders initial letter when no logo', () => {
        const org = makeOrg({ name: 'Zebra Inc', logo_url: null });
        const wrapper = mountComponent({ organizations: makePaginated([org]) });
        expect(wrapper.text()).toContain('Z');
    });

    it('links to organization detail page', () => {
        const org = makeOrg({ id: 42 });
        const wrapper = mountComponent({ organizations: makePaginated([org]) });
        const link = wrapper.find('a');
        expect(link.attributes('href')).toBe('/organizations/42');
    });

    it('renders multiple organizations', () => {
        const orgs = [
            makeOrg({ id: 1, name: 'Alpha Inc' }),
            makeOrg({ id: 2, name: 'Beta Corp' }),
            makeOrg({ id: 3, name: 'Gamma LLC' }),
        ];
        const wrapper = mountComponent({ organizations: makePaginated(orgs) });
        expect(wrapper.text()).toContain('Alpha Inc');
        expect(wrapper.text()).toContain('Beta Corp');
        expect(wrapper.text()).toContain('Gamma LLC');
    });
});
