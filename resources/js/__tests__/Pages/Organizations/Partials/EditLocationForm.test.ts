import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

const { mockPut } = vi.hoisted(() => ({
    mockPut: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial: Record<string, unknown>) => {
        const data = { ...initial };
        return new Proxy(data, {
            get(target, prop) {
                if (prop === 'post') return vi.fn();
                if (prop === 'put') return mockPut;
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
    router: { get: vi.fn(), post: vi.fn(), delete: vi.fn(), put: vi.fn() },
    usePage: () => ({
        props: {
            auth: { user: { id: 1, name: 'Test', email: 'test@test.test' } },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

import EditLocationForm from '@/Pages/Organizations/Partials/EditLocationForm.vue';

function makeOrg(overrides = {}) {
    return {
        id: 1,
        name: 'Clarity Technologies',
        company_name: null,
        phone: null,
        emails: [{ email: 'test@test.test', name: 'Test' }],
        currency: 'INR',
        country_code: 'IN',
        financial_year_type: 'april_march',
        financial_year_start_month: 4,
        financial_year_start_day: 1,
        tax_number: null,
        registration_number: null,
        website: null,
        notes: null,
        bank_details: null,
        logo_url: null,
        primary_location: null,
        personal_team: false,
        ...overrides,
    };
}

const countries = [
    {
        value: 'IN',
        label: 'India',
        currency: 'INR',
        financial_year_options: { april_march: 'April - March' },
        default_financial_year: 'april_march',
        supported_currencies: { INR: 'Indian Rupee' },
        tax_system: { name: 'GST', rates: ['18%'] },
        recommended_numbering: '{PREFIX}-{FY}-{SEQUENCE:4}',
    },
];

const defaultProps = {
    organization: makeOrg(),
    countries,
};

function mountComponent(propsOverride = {}) {
    return mount(EditLocationForm, {
        props: { ...defaultProps, ...propsOverride },
    });
}

describe('EditLocationForm', () => {
    beforeEach(() => {
        mockPut.mockClear();
    });

    it('renders Primary Location heading', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Primary Location');
    });

    it('renders all address fields', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('#loc-name').exists()).toBe(true);
        expect(wrapper.find('#loc-gstin').exists()).toBe(true);
        expect(wrapper.find('#loc-addr1').exists()).toBe(true);
        expect(wrapper.find('#loc-addr2').exists()).toBe(true);
        expect(wrapper.find('#loc-city').exists()).toBe(true);
        expect(wrapper.find('#loc-state').exists()).toBe(true);
        expect(wrapper.find('#loc-postal').exists()).toBe(true);
        expect(wrapper.find('#loc-country').exists()).toBe(true);
    });

    it('pre-fills with existing location data', () => {
        const wrapper = mountComponent({
            organization: makeOrg({
                primary_location: {
                    id: 1,
                    name: 'Head Office',
                    gstin: 'GSTIN123',
                    address_line_1: '123 Main St',
                    address_line_2: 'Suite 100',
                    city: 'Mumbai',
                    state: 'Maharashtra',
                    country: 'IN',
                    postal_code: '400001',
                },
            }),
        });
        expect(
            (wrapper.find('#loc-name').element as HTMLInputElement).value,
        ).toBe('Head Office');
        expect(
            (wrapper.find('#loc-city').element as HTMLInputElement).value,
        ).toBe('Mumbai');
        expect(
            (wrapper.find('#loc-addr1').element as HTMLInputElement).value,
        ).toBe('123 Main St');
    });

    it('submits form via put', async () => {
        const wrapper = mountComponent();
        await wrapper.find('form').trigger('submit');
        expect(mockPut).toHaveBeenCalledWith(
            '/organizations/1/location',
            expect.any(Object),
        );
    });

    it('shows Save Location button', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Save Location');
    });

    it('shows no validation errors by default', () => {
        const wrapper = mountComponent();
        const errors = wrapper.findAll('.text-red-600');
        expect(errors.length).toBe(0);
    });

    it('renders country select with options', () => {
        const wrapper = mountComponent();
        const select = wrapper.find('#loc-country');
        expect(select.exists()).toBe(true);
        expect(wrapper.text()).toContain('India');
    });
});
