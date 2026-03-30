import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
    makeInvoice,
    makeCustomer,
    makeLocation,
    makeTaxTemplate,
    makeNumberingSeries,
    makePayment,
} from '../../helpers';

const { mockRouterDelete } = vi.hoisted(() => ({
    mockRouterDelete: vi.fn(),
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
    Link: { template: '<a><slot /></a>' },
    router: {
        get: vi.fn(),
        post: vi.fn(),
        delete: mockRouterDelete,
        reload: vi.fn(),
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

import Edit from '@/Pages/Invoices/Edit.vue';

function mountComponent(propsOverride = {}) {
    return mount(Edit, {
        props: {
            invoice: makeInvoice(),
            customers: [makeCustomer()],
            organizationLocations: [makeLocation()],
            taxTemplates: [makeTaxTemplate()],
            numberingSeries: [makeNumberingSeries()],
            statusOptions: { draft: 'Draft', sent: 'Sent', paid: 'Paid' },
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
                        'invoice',
                        'customers',
                        'organizationLocations',
                        'taxTemplates',
                        'numberingSeries',
                        'statusOptions',
                    ],
                },
                PaymentModal: {
                    template:
                        '<div data-testid="payment-modal" v-if="show">Payment Modal</div>',
                    props: ['show', 'invoice'],
                    emits: ['close'],
                },
            },
        },
    });
}

describe('Invoices/Edit', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders InvoiceForm in edit mode', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('[data-testid="invoice-form"]').text()).toContain(
            'edit',
        );
        expect(wrapper.find('[data-testid="invoice-form"]').text()).toContain(
            'invoice',
        );
    });

    it('shows payment section for invoices', () => {
        const wrapper = mountComponent({
            invoice: makeInvoice({ type: 'invoice' }),
        });
        expect(wrapper.text()).toContain('Payments');
    });

    it('hides payment section for estimates', () => {
        const wrapper = mountComponent({
            invoice: makeInvoice({ type: 'estimate' }),
        });
        expect(wrapper.text()).not.toContain('Payments');
    });

    it('shows Record Payment button when balance is due', () => {
        const wrapper = mountComponent({
            invoice: makeInvoice({ total: 10000, amount_paid: 0 }),
        });
        expect(wrapper.text()).toContain('Record Payment');
    });

    it('hides Record Payment button when fully paid', () => {
        const wrapper = mountComponent({
            invoice: makeInvoice({ total: 10000, amount_paid: 10000 }),
        });
        expect(wrapper.text()).not.toContain('Record Payment');
    });

    it('opens payment modal when Record Payment is clicked', async () => {
        const wrapper = mountComponent({
            invoice: makeInvoice({ total: 10000, amount_paid: 0 }),
        });
        expect(wrapper.find('[data-testid="payment-modal"]').exists()).toBe(
            false,
        );

        const btn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Record Payment');
        await btn!.trigger('click');

        expect(wrapper.find('[data-testid="payment-modal"]').exists()).toBe(
            true,
        );
    });

    it('displays payment history table when payments exist', () => {
        const payment = makePayment({
            payment_method: 'bank_transfer',
            reference: 'TXN-123',
        });
        const wrapper = mountComponent({
            invoice: makeInvoice({ payments: [payment] }),
        });
        expect(wrapper.text()).toContain('TXN-123');
        expect(wrapper.text()).toContain('Bank Transfer');
    });

    it('shows no payments message when empty', () => {
        const wrapper = mountComponent({
            invoice: makeInvoice({ payments: [] }),
        });
        expect(wrapper.text()).toContain('No payments recorded yet');
    });

    it('calls router.delete when deleting a payment', async () => {
        window.confirm = vi.fn().mockReturnValue(true);
        const payment = makePayment({ id: 42 });
        const invoice = makeInvoice({ id: 10, payments: [payment] });
        const wrapper = mountComponent({ invoice });

        const deleteBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Delete');
        await deleteBtn!.trigger('click');

        expect(mockRouterDelete).toHaveBeenCalledWith(
            '/invoices/10/payments/42',
            { preserveScroll: true },
        );
    });

    it('does not delete payment when confirm is cancelled', async () => {
        window.confirm = vi.fn().mockReturnValue(false);
        const payment = makePayment({ id: 42 });
        const wrapper = mountComponent({
            invoice: makeInvoice({ id: 10, payments: [payment] }),
        });

        const deleteBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Delete');
        await deleteBtn!.trigger('click');

        expect(mockRouterDelete).not.toHaveBeenCalled();
    });

    it('shows payment summary with total, paid, and balance', () => {
        const wrapper = mountComponent({
            invoice: makeInvoice({
                total: 11800000,
                amount_paid: 5000000,
                currency: 'INR',
            }),
        });
        expect(wrapper.text()).toContain('Total');
        expect(wrapper.text()).toContain('Paid');
        expect(wrapper.text()).toContain('Balance Due');
    });
});
