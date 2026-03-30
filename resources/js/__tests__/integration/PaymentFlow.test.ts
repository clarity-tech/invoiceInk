import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
    makeInvoice,
    makePayment,
    makeCustomer,
    makeLocation,
    makeTaxTemplate,
    makeNumberingSeries,
} from '../helpers';

const { mockPost, mockClearErrors, mockRouterDelete } = vi.hoisted(() => ({
    mockPost: vi.fn(),
    mockClearErrors: vi.fn(),
    mockRouterDelete: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial: Record<string, unknown>) => {
        const data = { ...initial };
        return new Proxy(data, {
            get(target, prop) {
                if (prop === 'post') return mockPost;
                if (prop === 'put') return vi.fn();
                if (prop === 'delete') return vi.fn();
                if (prop === 'errors') return {};
                if (prop === 'processing') return false;
                if (prop === 'reset') return vi.fn();
                if (prop === 'clearErrors') return mockClearErrors;
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
        get: vi.fn(),
        post: vi.fn(),
        delete: mockRouterDelete,
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

vi.mock('@/composables/useFormatDate', () => ({
    formatDate: (d: string) => d ?? '-',
    useFormatDate: () => ({ formatDate: (d: string) => d ?? '-' }),
}));

vi.mock('@/composables/useInvoiceCalculator', () => ({
    useInvoiceCalculator: () => ({
        totals: { value: { subtotal: 0, tax: 0, total: 0 } },
    }),
}));

import EditPage from '@/Pages/Invoices/Edit.vue';

const orgLocation = makeLocation({ id: 10, name: 'HQ', city: 'Mumbai' });

function mountEdit(invoiceOverrides = {}, propsOverride = {}) {
    const invoice = makeInvoice({
        id: 1,
        type: 'invoice',
        total: 10000000,
        amount_paid: 3000000,
        currency: 'INR',
        payments: [
            makePayment({
                id: 1,
                amount: 3000000,
                payment_method: 'bank_transfer',
                reference: 'TXN-001',
                payment_date: '2026-03-10',
            }),
        ],
        ...invoiceOverrides,
    });

    return mount(EditPage, {
        props: {
            invoice,
            customers: [makeCustomer()],
            organizationLocations: [orgLocation],
            taxTemplates: [makeTaxTemplate()],
            numberingSeries: [makeNumberingSeries()],
            statusOptions: { draft: 'Draft', sent: 'Sent', paid: 'Paid' },
            ...propsOverride,
        },
        global: {
            stubs: {
                AppLayout: {
                    template: '<div><slot name="header" /><slot /></div>',
                },
                InvoiceForm: {
                    template:
                        '<div data-testid="invoice-form">InvoiceForm</div>',
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
                    template: `<div v-if="show" data-testid="payment-modal">
                        <span data-testid="modal-title">Record Payment</span>
                        <form data-testid="payment-form" @submit.prevent="$emit('close')"><button type="submit">Record Payment</button></form>
                        <button data-testid="modal-close" @click="$emit('close')">Close</button>
                    </div>`,
                    props: ['show', 'invoice'],
                    emits: ['close'],
                },
            },
        },
    });
}

describe('PaymentFlow', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders payment section for invoices', () => {
        const wrapper = mountEdit();
        expect(wrapper.text()).toContain('Payments');
    });

    it('hides payment section for estimates', () => {
        const wrapper = mountEdit({ type: 'estimate' });
        expect(wrapper.text()).not.toContain('Payments');
    });

    it('shows payment summary: total, paid, balance due', () => {
        const wrapper = mountEdit();
        expect(wrapper.text()).toContain('Total');
        expect(wrapper.text()).toContain('Paid');
        expect(wrapper.text()).toContain('Balance Due');
    });

    it('renders payment history table', () => {
        const wrapper = mountEdit();
        expect(wrapper.text()).toContain('TXN-001');
        expect(wrapper.text()).toContain('Bank Transfer');
    });

    it('shows "No payments recorded yet" when no payments', () => {
        const wrapper = mountEdit({ payments: [], amount_paid: 0 });
        expect(wrapper.text()).toContain('No payments recorded yet');
    });

    it('shows Record Payment button when balance is due', () => {
        const wrapper = mountEdit();
        const btn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Record Payment');
        expect(btn).toBeDefined();
    });

    it('hides Record Payment button when fully paid', () => {
        const wrapper = mountEdit({ total: 10000000, amount_paid: 10000000 });
        // The "Record Payment" button in the header should not appear
        const btns = wrapper
            .findAll('button')
            .filter((b) => b.text() === 'Record Payment');
        // Only the one inside the modal stub (which is hidden) should not count
        expect(btns.length).toBe(0);
    });

    it('opens payment modal on Record Payment click', async () => {
        const wrapper = mountEdit();
        const btn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Record Payment');
        await btn!.trigger('click');
        expect(wrapper.find('[data-testid="payment-modal"]').exists()).toBe(
            true,
        );
    });

    it('closes payment modal on close emit', async () => {
        const wrapper = mountEdit();
        // Open
        const btn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Record Payment');
        await btn!.trigger('click');
        expect(wrapper.find('[data-testid="payment-modal"]').exists()).toBe(
            true,
        );

        // Close
        const closeBtn = wrapper.find('[data-testid="modal-close"]');
        await closeBtn.trigger('click');
        expect(wrapper.find('[data-testid="payment-modal"]').exists()).toBe(
            false,
        );
    });

    it('renders delete button for each payment', () => {
        const wrapper = mountEdit();
        const deleteBtns = wrapper
            .findAll('button')
            .filter((b) => b.text() === 'Delete');
        expect(deleteBtns.length).toBeGreaterThanOrEqual(1);
    });

    it('renders InvoiceForm in edit mode', () => {
        const wrapper = mountEdit();
        expect(wrapper.find('[data-testid="invoice-form"]').exists()).toBe(
            true,
        );
    });

    it('renders multiple payments in history', () => {
        const wrapper = mountEdit({
            payments: [
                makePayment({ id: 1, amount: 2000000, reference: 'PAY-A' }),
                makePayment({ id: 2, amount: 1000000, reference: 'PAY-B' }),
            ],
        });
        expect(wrapper.text()).toContain('PAY-A');
        expect(wrapper.text()).toContain('PAY-B');
    });
});
