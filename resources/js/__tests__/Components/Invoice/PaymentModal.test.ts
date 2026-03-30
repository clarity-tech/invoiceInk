import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { makeInvoice } from '../../helpers';

const mockPost = vi.fn();
const mockClearErrors = vi.fn();
let formState: Record<string, unknown> = {};

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial: Record<string, unknown>) => {
        formState = { ...initial };
        return new Proxy(formState, {
            get(target, prop) {
                if (prop === 'post') return mockPost;
                if (prop === 'clearErrors') return mockClearErrors;
                if (prop === 'reset') return vi.fn();
                if (prop === 'errors') return {};
                if (prop === 'processing') return false;
                return target[prop as string];
            },
            set(target, prop, value) {
                target[prop as string] = value;
                return true;
            },
        });
    },
}));

vi.mock('@/composables/useFormatMoney', () => ({
    formatMoney: (amount: number, currency: string) => {
        const val = (amount / 100).toFixed(2);
        const symbols: Record<string, string> = {
            INR: '₹',
            USD: '$',
            AED: 'AED',
        };
        return `${symbols[currency] ?? currency}${val}`;
    },
}));

import PaymentModal from '@/Components/Invoice/PaymentModal.vue';

const defaultInvoice = makeInvoice({
    total: 10000000,
    amount_paid: 3000000,
    currency: 'INR',
    invoice_number: 'INV-2026-03-0001',
});

describe('PaymentModal', () => {
    beforeEach(() => {
        formState = {};
        vi.clearAllMocks();
    });

    it('does not render when show is false', () => {
        const wrapper = mount(PaymentModal, {
            props: { show: false, invoice: defaultInvoice },
        });
        expect(wrapper.find('[role="dialog"]').exists()).toBe(false);
    });

    it('renders when show is true', () => {
        const wrapper = mount(PaymentModal, {
            props: { show: true, invoice: defaultInvoice },
        });
        expect(wrapper.find('[role="dialog"]').exists()).toBe(true);
    });

    it('displays invoice number in header', () => {
        const wrapper = mount(PaymentModal, {
            props: { show: true, invoice: defaultInvoice },
        });
        expect(wrapper.text()).toContain('INV-2026-03-0001');
    });

    it('shows Record Payment title', () => {
        const wrapper = mount(PaymentModal, {
            props: { show: true, invoice: defaultInvoice },
        });
        expect(wrapper.text()).toContain('Record Payment');
    });

    it('displays balance summary (total, paid, balance due)', () => {
        const wrapper = mount(PaymentModal, {
            props: { show: true, invoice: defaultInvoice },
        });
        const text = wrapper.text();
        expect(text).toContain('Total');
        expect(text).toContain('Paid');
        expect(text).toContain('Balance Due');
    });

    it('renders payment method dropdown with all options', () => {
        const wrapper = mount(PaymentModal, {
            props: { show: true, invoice: defaultInvoice },
        });
        const options = wrapper.find('select').findAll('option');
        // "Select method" + 7 payment methods
        expect(options).toHaveLength(8);
        expect(wrapper.text()).toContain('Bank Transfer');
        expect(wrapper.text()).toContain('UPI');
        expect(wrapper.text()).toContain('Cash');
    });

    it('renders reference and notes fields', () => {
        const wrapper = mount(PaymentModal, {
            props: { show: true, invoice: defaultInvoice },
        });
        expect(
            wrapper.find('input[placeholder="e.g. TXN-12345"]').exists(),
        ).toBe(true);
        expect(wrapper.find('textarea').exists()).toBe(true);
    });

    it('calls form.post() on submit', async () => {
        const invoice = makeInvoice({ id: 42 });
        const wrapper = mount(PaymentModal, {
            props: { show: true, invoice },
        });
        await wrapper.find('form').trigger('submit');
        expect(mockPost).toHaveBeenCalledWith(
            '/invoices/42/payments',
            expect.any(Object),
        );
    });

    it('emits close on cancel button click', async () => {
        const wrapper = mount(PaymentModal, {
            props: { show: true, invoice: defaultInvoice },
        });
        const cancelBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Cancel');
        await cancelBtn!.trigger('click');
        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('emits close on X button click', async () => {
        const wrapper = mount(PaymentModal, {
            props: { show: true, invoice: defaultInvoice },
        });
        // X button is the first button with an SVG
        const closeBtn = wrapper.find('.text-gray-400');
        await closeBtn.trigger('click');
        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('shows payment date field with date type', () => {
        const wrapper = mount(PaymentModal, {
            props: { show: true, invoice: defaultInvoice },
        });
        expect(wrapper.find('input[type="date"]').exists()).toBe(true);
    });

    it('shows amount field with number type', () => {
        const wrapper = mount(PaymentModal, {
            props: { show: true, invoice: defaultInvoice },
        });
        expect(wrapper.find('input[type="number"]').exists()).toBe(true);
    });

    it('displays currency prefix on amount field', () => {
        const wrapper = mount(PaymentModal, {
            props: { show: true, invoice: defaultInvoice },
        });
        expect(wrapper.text()).toContain('INR');
    });
});
