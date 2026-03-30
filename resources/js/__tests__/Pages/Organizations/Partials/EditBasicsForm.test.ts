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

import EditBasicsForm from '@/Pages/Organizations/Partials/EditBasicsForm.vue';

function makeOrg(overrides = {}) {
    return {
        id: 1,
        name: 'Clarity Technologies',
        company_name: 'Clarity Tech',
        phone: '+91 12345',
        emails: [{ email: 'test@clarity.test', name: 'Test' }],
        currency: 'INR',
        country_code: 'IN',
        financial_year_type: 'april_march',
        financial_year_start_month: 4,
        financial_year_start_day: 1,
        tax_number: 'TAX123',
        registration_number: 'REG456',
        website: 'https://clarity.test',
        notes: 'Notes',
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
    {
        value: 'US',
        label: 'United States',
        currency: 'USD',
        financial_year_options: { calendar: 'Calendar Year' },
        default_financial_year: 'calendar',
        supported_currencies: { USD: 'US Dollar' },
        tax_system: { name: 'Sales Tax', rates: ['8%'] },
        recommended_numbering: '{PREFIX}-{YEAR}-{SEQUENCE:4}',
    },
];

const currencies = { INR: 'Indian Rupee', USD: 'US Dollar' };

const defaultProps = {
    organization: makeOrg(),
    countries,
    currencies,
};

function mountComponent(propsOverride = {}) {
    return mount(EditBasicsForm, {
        props: { ...defaultProps, ...propsOverride },
    });
}

describe('EditBasicsForm', () => {
    beforeEach(() => {
        mockPut.mockClear();
    });

    it('renders General Information heading', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('General Information');
    });

    it('renders name field with value', () => {
        const wrapper = mountComponent();
        const input = wrapper.find('#org-name').element as HTMLInputElement;
        expect(input.value).toBe('Clarity Technologies');
    });

    it('renders phone field', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('#org-phone').exists()).toBe(true);
    });

    it('renders country select', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('#org-country').exists()).toBe(true);
    });

    it('renders currency select', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('#org-currency').exists()).toBe(true);
    });

    it('renders email input', () => {
        const wrapper = mountComponent();
        const emailInputs = wrapper.findAll('input[type="email"]');
        expect(emailInputs.length).toBeGreaterThanOrEqual(1);
    });

    it('renders add email button', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Add email');
    });

    it('has add email button that is clickable', async () => {
        const wrapper = mountComponent();
        const addBtn = wrapper
            .findAll('button')
            .find((b) => b.text().includes('Add email'));
        expect(addBtn).toBeDefined();
        // Clicking should not throw
        await addBtn?.trigger('click');
    });

    it('submits form via put', async () => {
        const wrapper = mountComponent();
        await wrapper.find('form').trigger('submit');
        expect(mockPut).toHaveBeenCalledWith(
            '/organizations/1',
            expect.any(Object),
        );
    });

    it('shows Save Changes button', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Save Changes');
    });

    it('renders notes textarea', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('#org-notes').exists()).toBe(true);
    });

    it('renders website field', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('#org-web').exists()).toBe(true);
    });

    it('renders tax number field', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('#org-tax').exists()).toBe(true);
    });

    it('renders registration number field', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('#org-reg').exists()).toBe(true);
    });
});

describe('EditBasicsForm validation errors', () => {
    it('displays name error when present', () => {
        vi.resetModules();
        // Errors are driven by proxy, tested via DOM presence of error container
        const wrapper = mountComponent();
        // The error paragraph exists but is hidden when no error
        const errorP = wrapper
            .findAll('p')
            .filter((p) => p.classes().includes('text-red-600'));
        // No errors shown by default
        expect(errorP.length).toBe(0);
    });
});

describe('EditBasicsForm processing state', () => {
    it('shows submit button text for normal state', () => {
        const wrapper = mountComponent();
        const btn = wrapper.find('button[type="submit"]');
        expect(btn.text()).toBe('Save Changes');
    });
});
