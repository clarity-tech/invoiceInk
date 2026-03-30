import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import {
    makeCustomer,
    makeLocation,
    makeTaxTemplate,
    makeNumberingSeries,
} from '../../helpers';

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
    Link: { template: '<a><slot /></a>' },
    router: { get: vi.fn(), post: vi.fn(), delete: vi.fn(), reload: vi.fn() },
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

import Create from '@/Pages/Invoices/Create.vue';

const defaults = {
    organization_id: 1,
    organization_location_id: 1,
    invoice_numbering_series_id: 1,
    issued_at: '2026-03-28',
    due_at: '2026-04-28',
    currency: 'INR',
};

function mountComponent(propsOverride = {}) {
    return mount(Create, {
        props: {
            type: 'invoice' as const,
            customers: [makeCustomer()],
            organizationLocations: [makeLocation()],
            taxTemplates: [makeTaxTemplate()],
            numberingSeries: [makeNumberingSeries()],
            statusOptions: { draft: 'Draft', sent: 'Sent' },
            defaults,
            ...propsOverride,
        },
        global: {
            stubs: {
                AppLayout: { template: '<div><slot /></div>' },
                InvoiceForm: {
                    template:
                        '<div data-testid="invoice-form">{{ mode }} {{ type }}</div>',
                    props: [
                        'mode',
                        'type',
                        'customers',
                        'organizationLocations',
                        'taxTemplates',
                        'numberingSeries',
                        'statusOptions',
                        'defaults',
                    ],
                },
            },
        },
    });
}

describe('Invoices/Create', () => {
    it('renders InvoiceForm in create mode', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('[data-testid="invoice-form"]').text()).toContain(
            'create',
        );
        expect(wrapper.find('[data-testid="invoice-form"]').text()).toContain(
            'invoice',
        );
    });

    it('passes estimate type to InvoiceForm', () => {
        const wrapper = mountComponent({ type: 'estimate' });
        expect(wrapper.find('[data-testid="invoice-form"]').text()).toContain(
            'estimate',
        );
    });

    it('renders InvoiceForm component', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('[data-testid="invoice-form"]').exists()).toBe(
            true,
        );
    });

    it('renders with multiple customers', () => {
        const customers = [
            makeCustomer({ id: 1 }),
            makeCustomer({ id: 2, name: 'Other Corp' }),
        ];
        const wrapper = mountComponent({ customers });
        // Component renders without error with multiple customers
        expect(wrapper.find('[data-testid="invoice-form"]').exists()).toBe(
            true,
        );
    });

    it('renders with multiple tax templates', () => {
        const templates = [
            makeTaxTemplate(),
            makeTaxTemplate({ id: 2, name: 'VAT 5%' }),
        ];
        const wrapper = mountComponent({ taxTemplates: templates });
        expect(wrapper.find('[data-testid="invoice-form"]').exists()).toBe(
            true,
        );
    });

    it('renders with numbering series', () => {
        const series = [
            makeNumberingSeries(),
            makeNumberingSeries({ id: 2, name: 'Estimate Series' }),
        ];
        const wrapper = mountComponent({ numberingSeries: series });
        expect(wrapper.find('[data-testid="invoice-form"]').exists()).toBe(
            true,
        );
    });
});
