import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { template: '<a :href="href"><slot /></a>', props: ['href'] },
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
                },
            },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

vi.mock('lucide-vue-next', () => ({
    Pencil: { template: '<span />' },
}));

import Show from '@/Pages/Organizations/Show.vue';

function makeOrg(overrides = {}) {
    return {
        id: 1,
        name: 'Clarity Technologies',
        company_name: 'Clarity Tech Pvt Ltd',
        phone: '+91 98765 43210',
        emails: [{ email: 'test@clarity.test', name: 'Test' }],
        currency: 'INR',
        country_code: 'IN',
        financial_year_type: 'april_march',
        tax_number: 'GSTIN123',
        registration_number: 'REG456',
        website: 'https://clarity.test',
        notes: 'Some notes here',
        bank_details: null,
        logo_url: null,
        primary_location: null,
        ...overrides,
    };
}

function mountComponent(propsOverride = {}) {
    return mount(Show, {
        props: {
            organization: makeOrg(),
            numberingSeries: [],
            ...propsOverride,
        },
        global: {
            stubs: {
                AppLayout: { template: '<div><slot /></div>' },
            },
        },
    });
}

describe('Organizations/Show', () => {
    it('renders organization name', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Clarity Technologies');
    });

    it('shows company name when different from name', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Clarity Tech Pvt Ltd');
    });

    it('hides company name when same as name', () => {
        const wrapper = mountComponent({
            organization: makeOrg({ company_name: 'Clarity Technologies' }),
        });
        const matches = wrapper.text().split('Clarity Technologies').length - 1;
        expect(matches).toBe(1);
    });

    it('shows currency badge', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('INR');
    });

    it('shows country code badge', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('IN');
    });

    it('shows email addresses', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('test@clarity.test');
    });

    it('shows phone number', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('+91 98765 43210');
    });

    it('shows website', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('https://clarity.test');
    });

    it('shows tax number', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('GSTIN123');
    });

    it('shows registration number', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('REG456');
    });

    it('shows notes section', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Some notes here');
    });

    it('shows primary location when present', () => {
        const wrapper = mountComponent({
            organization: makeOrg({
                primary_location: {
                    id: 1,
                    name: 'Head Office',
                    gstin: '29ABCDE1234F1Z5',
                    address_line_1: '123 Main St',
                    address_line_2: 'Suite 100',
                    city: 'Mumbai',
                    state: 'Maharashtra',
                    country: 'IN',
                    postal_code: '400001',
                },
            }),
        });
        expect(wrapper.text()).toContain('Head Office');
        expect(wrapper.text()).toContain('123 Main St');
        expect(wrapper.text()).toContain('Mumbai');
        expect(wrapper.text()).toContain('29ABCDE1234F1Z5');
    });

    it('shows no location message when no primary location', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('No location configured');
    });

    it('shows bank details when present', () => {
        const wrapper = mountComponent({
            organization: makeOrg({
                bank_details: {
                    bank_name: 'HDFC Bank',
                    account_number: '1234567890',
                    account_name: 'Clarity Tech',
                    ifsc: 'HDFC0001234',
                },
            }),
        });
        expect(wrapper.text()).toContain('HDFC Bank');
        expect(wrapper.text()).toContain('1234567890');
        expect(wrapper.text()).toContain('HDFC0001234');
    });

    it('shows no bank details message when empty', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('No bank details configured');
    });

    it('shows logo when logo_url is set', () => {
        const wrapper = mountComponent({
            organization: makeOrg({ logo_url: '/logos/test.png' }),
        });
        const img = wrapper.find('img');
        expect(img.exists()).toBe(true);
        expect(img.attributes('src')).toBe('/logos/test.png');
    });

    it('shows initial letter when no logo', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('C');
    });

    it('links to edit page', () => {
        const wrapper = mountComponent();
        const editLink = wrapper.find('a[href="/organizations/1/edit"]');
        expect(editLink.exists()).toBe(true);
        expect(editLink.text()).toContain('Edit Settings');
    });
});
