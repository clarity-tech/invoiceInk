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

import EditBankDetailsForm from '@/Pages/Organizations/Partials/EditBankDetailsForm.vue';

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

function mountComponent(propsOverride = {}) {
    return mount(EditBankDetailsForm, {
        props: { organization: makeOrg(), ...propsOverride },
    });
}

describe('EditBankDetailsForm', () => {
    beforeEach(() => {
        mockPut.mockClear();
    });

    it('renders Bank Details heading', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Bank Details');
    });

    it('renders all bank fields', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('#bank-name').exists()).toBe(true);
        expect(wrapper.find('#bank-branch').exists()).toBe(true);
        expect(wrapper.find('#bank-acc-name').exists()).toBe(true);
        expect(wrapper.find('#bank-acc-num').exists()).toBe(true);
        expect(wrapper.find('#bank-ifsc').exists()).toBe(true);
        expect(wrapper.find('#bank-swift').exists()).toBe(true);
        expect(wrapper.find('#bank-pan').exists()).toBe(true);
    });

    it('pre-fills with existing bank details', () => {
        const wrapper = mountComponent({
            organization: makeOrg({
                bank_details: {
                    account_name: 'Clarity Tech',
                    account_number: '1234567890',
                    bank_name: 'HDFC Bank',
                    ifsc: 'HDFC0001234',
                    branch: 'Mumbai Main',
                    swift: 'HDFCINBB',
                    pan: 'ABCDE1234F',
                },
            }),
        });
        expect(
            (wrapper.find('#bank-name').element as HTMLInputElement).value,
        ).toBe('HDFC Bank');
        expect(
            (wrapper.find('#bank-acc-num').element as HTMLInputElement).value,
        ).toBe('1234567890');
        expect(
            (wrapper.find('#bank-ifsc').element as HTMLInputElement).value,
        ).toBe('HDFC0001234');
        expect(
            (wrapper.find('#bank-swift').element as HTMLInputElement).value,
        ).toBe('HDFCINBB');
        expect(
            (wrapper.find('#bank-pan').element as HTMLInputElement).value,
        ).toBe('ABCDE1234F');
    });

    it('submits form via put', async () => {
        const wrapper = mountComponent();
        await wrapper.find('form').trigger('submit');
        expect(mockPut).toHaveBeenCalledWith(
            '/organizations/1/bank-details',
            expect.any(Object),
        );
    });

    it('shows Save Bank Details button', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Save Bank Details');
    });

    it('shows no validation errors by default', () => {
        const wrapper = mountComponent();
        const errors = wrapper.findAll('.text-red-600');
        expect(errors.length).toBe(0);
    });

    it('renders description text', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain(
            'Bank information displayed on your invoices',
        );
    });
});
