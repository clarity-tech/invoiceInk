import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
    makeCustomer,
    makeLocation,
    makeTaxTemplate,
    makeNumberingSeries,
    makeInvoice,
} from '../helpers';

const { mockPost, mockPut, mockRouterGet } = vi.hoisted(() => ({
    mockPost: vi.fn(),
    mockPut: vi.fn(),
    mockRouterGet: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial: Record<string, unknown>) => {
        const data = { ...initial };
        return new Proxy(data, {
            get(target, prop) {
                if (prop === 'post') return mockPost;
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
    Link: { template: '<a :href="href"><slot /></a>', props: ['href'] },
    router: {
        get: mockRouterGet,
        post: vi.fn(),
        delete: vi.fn(),
        visit: vi.fn(),
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

vi.mock('@/composables/useFormatMoney', () => ({
    formatMoney: (amount: number, currency: string) =>
        `${currency} ${(amount / 100).toFixed(2)}`,
    useFormatMoney: () => ({
        formatMoney: (amount: number, currency: string) =>
            `${currency} ${(amount / 100).toFixed(2)}`,
    }),
}));

vi.mock('@/composables/useInvoiceCalculator', () => ({
    useInvoiceCalculator: (items: {
        value: Array<{
            quantity: number;
            unit_price: number;
            tax_rate: number;
        }>;
    }) => {
        const { computed } = require('vue');
        return {
            totals: computed(() => {
                let subtotal = 0;
                let tax = 0;
                for (const item of items.value) {
                    const line = Math.round(item.quantity * item.unit_price);
                    subtotal += line;
                    tax += Math.round((line * item.tax_rate) / 10000);
                }
                return { subtotal, tax, total: subtotal + tax };
            }),
        };
    },
}));

import InvoiceForm from '@/Components/Invoice/InvoiceForm.vue';

const orgLocation = makeLocation({
    id: 10,
    name: 'Head Office',
    city: 'Mumbai',
});
const custLocation = makeLocation({
    id: 20,
    name: 'Client Office',
    city: 'Delhi',
    locatable_type: 'App\\Models\\Customer',
    locatable_id: 1,
});
const customer = makeCustomer({
    id: 1,
    name: 'ACME Corp',
    currency: 'INR',
    primary_location_id: 20,
    primary_location: custLocation,
    locations: [custLocation],
});

const defaultProps = () => ({
    mode: 'create' as const,
    type: 'invoice' as const,
    customers: [customer],
    organizationLocations: [orgLocation],
    taxTemplates: [makeTaxTemplate()],
    numberingSeries: [makeNumberingSeries()],
    statusOptions: { draft: 'Draft', sent: 'Sent', paid: 'Paid' },
    defaults: {
        organization_id: 1,
        organization_location_id: 10,
        invoice_numbering_series_id: 1,
        issued_at: '2026-03-01',
        due_at: '2026-03-31',
        currency: 'INR',
    },
});

function mountComponent(propsOverride = {}) {
    return mount(InvoiceForm, {
        props: { ...defaultProps(), ...propsOverride },
        global: {
            stubs: {
                ItemRow: {
                    template:
                        '<tr data-testid="item-row"><td>Item Row</td></tr>',
                    props: [
                        'item',
                        'index',
                        'currency',
                        'taxTemplates',
                        'canRemove',
                        'errors',
                    ],
                    emits: ['update', 'remove'],
                },
                EmailModal: { template: '<div />' },
            },
        },
    });
}

describe('InvoiceCreationFlow', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders the invoice form in create mode', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Create Invoice');
    });

    it('renders customer dropdown with options', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('ACME Corp');
    });

    it('shows billing address when customer is pre-selected in edit mode', () => {
        const invoice = makeInvoice({
            id: 1,
            customer_id: 1,
            customer_location: custLocation,
            customer_shipping_location: custLocation,
            organization_location: orgLocation,
        });
        const wrapper = mountComponent({ mode: 'edit', invoice });
        expect(wrapper.text()).toContain('Billing Address');
    });

    it('shows Add Item button', () => {
        const wrapper = mountComponent();
        const addBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Add Item');
        expect(addBtn).toBeDefined();
    });

    it('adds a new item row on Add Item click', async () => {
        const wrapper = mountComponent();
        const initialRows = wrapper.findAll('[data-testid="item-row"]').length;
        const addBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Add Item');
        await addBtn!.trigger('click');
        const newRows = wrapper.findAll('[data-testid="item-row"]').length;
        expect(newRows).toBe(initialRows + 1);
    });

    it('shows totals section', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Subtotal:');
        expect(wrapper.text()).toContain('Tax:');
        expect(wrapper.text()).toContain('Total:');
    });

    it('calls form.post on submit in create mode', async () => {
        const wrapper = mountComponent();
        const form = wrapper.find('form');
        await form.trigger('submit');
        expect(mockPost).toHaveBeenCalledWith('/invoices', expect.any(Object));
    });

    it('calls form.put on submit in edit mode', async () => {
        const invoice = makeInvoice({ id: 42 });
        const wrapper = mountComponent({ mode: 'edit', invoice });
        const form = wrapper.find('form');
        await form.trigger('submit');
        expect(mockPut).toHaveBeenCalledWith(
            '/invoices/42',
            expect.any(Object),
        );
    });

    it('shows edit-specific buttons in edit mode', () => {
        const invoice = makeInvoice({ id: 1, ulid: 'abc123' });
        const wrapper = mountComponent({ mode: 'edit', invoice });
        expect(wrapper.text()).toContain('Send Email');
        expect(wrapper.text()).toContain('View Public');
        expect(wrapper.text()).toContain('Download PDF');
    });

    it('navigates back on cancel click', async () => {
        const wrapper = mountComponent();
        const cancelBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Cancel');
        await cancelBtn!.trigger('click');
        expect(mockRouterGet).toHaveBeenCalledWith('/invoices');
    });

    it('renders estimate mode title', () => {
        const wrapper = mountComponent({ type: 'estimate' });
        expect(wrapper.text()).toContain('Create Estimate');
    });

    it('shows numbering series selector in create invoice mode', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Numbering Series');
    });

    it('shows organization location selector', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Organization Location');
        expect(wrapper.text()).toContain('Head Office - Mumbai');
    });

    it('shows notes textarea', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Customer Notes');
    });
});
