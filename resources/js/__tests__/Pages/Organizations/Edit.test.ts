import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

const { mockRouterGet } = vi.hoisted(() => ({
    mockRouterGet: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial: Record<string, unknown>) => {
        const data = { ...initial };
        return new Proxy(data, {
            get(target, prop) {
                if (prop === 'post') return vi.fn();
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
    Link: { template: '<a :href="href"><slot /></a>', props: ['href'] },
    router: {
        get: mockRouterGet,
        post: vi.fn(),
        delete: vi.fn(),
        put: vi.fn(),
    },
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

vi.mock('lucide-vue-next', () => ({
    Settings: { template: '<span />' },
    MapPin: { template: '<span />' },
    Landmark: { template: '<span />' },
    Image: { template: '<span />' },
    ArrowLeft: { template: '<span />' },
    Hash: { template: '<span />' },
    ChevronRight: { template: '<span />' },
}));

import Edit from '@/Pages/Organizations/Edit.vue';

function makeOrg(overrides = {}) {
    return {
        id: 1,
        name: 'Clarity Technologies',
        company_name: 'Clarity Tech',
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

const defaultProps = {
    organization: makeOrg(),
    countries: [
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
    ],
    currencies: { INR: 'Indian Rupee', USD: 'US Dollar' },
    tab: 'basics',
};

function mountComponent(propsOverride = {}) {
    return mount(Edit, {
        props: { ...defaultProps, ...propsOverride },
        global: {
            stubs: {
                AppLayout: {
                    template:
                        '<div><slot name="header" /><slot /></div>',
                },
                EditBasicsForm: {
                    template: '<div data-testid="basics-form">Basics</div>',
                },
                EditLocationForm: {
                    template: '<div data-testid="location-form">Location</div>',
                },
                EditBankDetailsForm: {
                    template: '<div data-testid="bank-form">Bank</div>',
                },
                EditLogoForm: {
                    template: '<div data-testid="logo-form">Logo</div>',
                },
            },
        },
    });
}

describe('Organizations/Edit', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders organization name', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Clarity Technologies');
    });

    it('renders Organization settings subtitle', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Settings');
    });

    it('renders tab navigation buttons', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('General');
        expect(wrapper.text()).toContain('Location');
        expect(wrapper.text()).toContain('Bank Details');
        expect(wrapper.text()).toContain('Logo');
    });

    it('shows basics form when tab is basics', () => {
        const wrapper = mountComponent({ tab: 'basics' });
        expect(wrapper.text()).toContain('Basics');
    });

    it('shows location form when tab is location', () => {
        const wrapper = mountComponent({ tab: 'location' });
        expect(wrapper.text()).toContain('Location');
    });

    it('shows bank form when tab is bank', () => {
        const wrapper = mountComponent({ tab: 'bank' });
        expect(wrapper.text()).toContain('Bank');
    });

    it('shows logo form when tab is logo', () => {
        const wrapper = mountComponent({ tab: 'logo' });
        expect(wrapper.text()).toContain('Logo');
    });

    it('defaults to basics tab when tab is empty', () => {
        const wrapper = mountComponent({ tab: '' });
        expect(wrapper.text()).toContain('Basics');
    });

    it('calls router.get when switching tabs', async () => {
        const wrapper = mountComponent();
        const buttons = wrapper.findAll('button');
        const locationBtn = buttons.find((b) => b.text().includes('Location'));
        await locationBtn?.trigger('click');
        expect(mockRouterGet).toHaveBeenCalledWith(
            '/organizations/1/edit',
            { tab: 'location' },
            expect.objectContaining({ preserveState: true }),
        );
    });

    it('renders back link to organization show page', () => {
        const wrapper = mountComponent();
        const link = wrapper.find('a[href="/organizations/1"]');
        expect(link.exists()).toBe(true);
    });
});
