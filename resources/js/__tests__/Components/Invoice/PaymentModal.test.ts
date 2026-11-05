import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { makeInvoice, makeOrganization, makePayment } from '../../helpers';

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

    it.each([
        ['IN', 'Subtotal (before GST)', true],
        ['AE', 'Subtotal', false],
    ] as const)(
        'labels the subtotal for an organization in %s',
        (country, label, mentionsGst) => {
            const wrapper = mount(PaymentModal, {
                props: {
                    show: true,
                    invoice: makeInvoice({
                        organization: makeOrganization({
                            country_code: country,
                        }),
                    }),
                },
            });
            const text = wrapper.text();

            expect(text).toContain(label);
            expect(text).toContain('₹100000.00');
            expect(text.includes('GST')).toBe(mentionsGst);
        },
    );

    it('leaves the subtotal out when it is the total', () => {
        const wrapper = mount(PaymentModal, {
            props: {
                show: true,
                invoice: makeInvoice({ subtotal: 10000000, total: 10000000 }),
            },
        });
        expect(wrapper.text()).not.toContain('Subtotal');
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
        const closeBtn = wrapper.find('.text-faint-foreground');
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

    it('offers the TDS field only when TDS applies', () => {
        const without = mount(PaymentModal, {
            props: { show: true, invoice: defaultInvoice },
        });
        expect(without.find('#payment-tds-amount').exists()).toBe(false);

        const withTds = mount(PaymentModal, {
            props: { show: true, invoice: defaultInvoice, tdsApplies: true },
        });
        expect(withTds.find('#payment-tds-amount').exists()).toBe(true);
    });

    it('takes the TDS off the prefilled amount until the amount is typed over', async () => {
        const wrapper = mount(PaymentModal, {
            props: { show: false, invoice: defaultInvoice, tdsApplies: true },
        });
        await wrapper.setProps({ show: true });
        expect(formState.amount).toBe(7000000);

        await wrapper.find('#payment-tds-amount').setValue('1400');
        expect(formState.tds_amount).toBe(140000);
        expect(formState.amount).toBe(6860000);

        await wrapper.find('input[type="number"]').setValue('50000');
        await wrapper.find('#payment-tds-amount').setValue('1000');
        expect(formState.amount).toBe(5000000);
    });

    it('fills the TDS and its section from a rate on the subtotal', async () => {
        const wrapper = mount(PaymentModal, {
            props: {
                show: false,
                invoice: defaultInvoice,
                tdsApplies: true,
                tdsPresets: [
                    { section: '194C', label: 'Contract', rate: 200 },
                    { section: '194J', label: 'Professional', rate: 1000 },
                ],
            },
        });
        await wrapper.setProps({ show: true });

        await wrapper
            .findAll('button[type="button"]')
            .filter((b) => b.text().includes('194C'))[0]
            .trigger('click');

        // 2% of the 1,00,000.00 subtotal, off the 70,000.00 balance.
        expect(formState.tds_amount).toBe(200000);
        expect(formState.tds_section).toBe('194C');
        expect(formState.amount).toBe(6800000);
    });

    it('gives only the TDS not yet withheld, never more than the balance', async () => {
        const invoice = makeInvoice({
            subtotal: 10000000,
            total: 10000000,
            amount_paid: 9850000,
            payments: [makePayment({ amount: 9700000, tds_amount: 150000 })],
        });
        const wrapper = mount(PaymentModal, {
            props: {
                show: false,
                invoice,
                tdsApplies: true,
                tdsPresets: [
                    { section: '194J', label: 'Technical', rate: 200 },
                    { section: '194J', label: 'Professional', rate: 1000 },
                ],
            },
        });
        await wrapper.setProps({ show: true });
        const presets = wrapper
            .findAll('button[type="button"]')
            .filter((b) => b.text().includes('194J'));

        await presets[0].trigger('click');
        expect(formState.tds_amount).toBe(50000);
        expect(formState.amount).toBe(100000);

        await presets[1].trigger('click');
        expect(formState.tds_amount).toBe(150000);
        expect(formState.amount).toBe(0);
    });
});
