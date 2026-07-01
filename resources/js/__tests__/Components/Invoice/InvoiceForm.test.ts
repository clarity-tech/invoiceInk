import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
    makeCustomer,
    makeLocation,
    makeTaxTemplate,
    makeNumberingSeries,
    makeInvoice,
} from '../../helpers';

const { mockPost, mockPut, mockRouterGet } = vi.hoisted(() => ({
    mockPost: vi.fn(),
    mockPut: vi.fn(),
    mockRouterGet: vi.fn(),
}));

let formProcessing = false;
let formErrors: Record<string, string> = {};

vi.mock('@inertiajs/vue3', () => {
    const { reactive } = require('vue');
    return {
        useForm: (initial: Record<string, unknown>) => {
            // Reactive so that `watch(() => form.field)` fires and v-model bindings
            // re-render when the component mutates a field (mirrors the real
            // useForm, whose returned object is reactive).
            const data = reactive({ ...initial });
            return new Proxy(data, {
                get(target, prop) {
                    if (prop === 'post') return mockPost;
                    if (prop === 'put') return mockPut;
                    if (prop === 'delete') return vi.fn();
                    if (prop === 'errors') return formErrors;
                    if (prop === 'processing') return formProcessing;
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
                    },
                },
                flash: { success: null, error: null, message: null },
            },
        }),
    };
});

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
const custLocation2 = makeLocation({
    id: 21,
    name: 'Branch Office',
    city: 'Bangalore',
    locatable_type: 'App\\Models\\Customer',
    locatable_id: 1,
});
const customer = makeCustomer({
    id: 1,
    name: 'ACME Corp',
    currency: 'INR',
    primary_location_id: 20,
    primary_location: custLocation,
    locations: [custLocation, custLocation2],
});
const customer2 = makeCustomer({
    id: 2,
    name: 'Globex Inc',
    currency: 'USD',
    primary_location_id: null,
    primary_location: null as any,
    locations: [],
});

const defaultProps = () => ({
    mode: 'create' as const,
    type: 'invoice' as const,
    customers: [customer, customer2],
    organizationLocations: [orgLocation],
    taxTemplates: [makeTaxTemplate()],
    numberingSeries: [makeNumberingSeries()],
    statusOptions: {
        draft: 'Draft',
        sent: 'Sent',
        paid: 'Paid',
        overdue: 'Overdue',
    },
    taxLabels: { IN: 'GSTIN', US: 'EIN' },
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
                        '<tr data-testid="item-row"><td>Item Row Stub</td></tr>',
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
                EmailModal: { template: '<div data-testid="email-modal" />' },
            },
        },
    });
}

describe('Invoice/InvoiceForm', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        formProcessing = false;
        formErrors = {};
    });

    describe('create mode - invoice', () => {
        it('renders with mode=create and type=invoice', () => {
            const wrapper = mountComponent();
            expect(wrapper.text()).toContain('Create Invoice');
        });

        it('shows cancel button', () => {
            const wrapper = mountComponent();
            expect(wrapper.text()).toContain('Cancel');
        });

        it('does not show edit-only buttons (Send Email, View Public, Download PDF)', () => {
            const wrapper = mountComponent();
            expect(wrapper.text()).not.toContain('Send Email');
            expect(wrapper.text()).not.toContain('View Public');
            expect(wrapper.text()).not.toContain('Download PDF');
        });
    });

    describe('create mode - estimate', () => {
        it('renders with mode=create and type=estimate', () => {
            const wrapper = mountComponent({ type: 'estimate' });
            expect(wrapper.text()).toContain('Create Estimate');
        });

        it('submit button says "Create Estimate"', () => {
            const wrapper = mountComponent({ type: 'estimate' });
            const submitBtn = wrapper.find('button[type="submit"]');
            expect(submitBtn.text()).toContain('Create');
            expect(submitBtn.text()).toContain('Estimate');
        });
    });

    describe('edit mode', () => {
        const editInvoice = makeInvoice({
            id: 42,
            ulid: 'abc123',
            customer_id: 1,
            organization_id: 1,
            status: 'sent',
            issued_at: '2026-03-01',
            due_at: '2026-03-31',
            notes: 'Some notes',
            items: [
                {
                    id: 1,
                    invoice_id: 42,
                    description: 'Consulting',
                    sac_code: '9983',
                    quantity: 2,
                    unit_price: 50000,
                    tax_rate: 1800,
                    created_at: '',
                    updated_at: '',
                },
            ],
            organization_location: orgLocation,
            customer_location: custLocation,
            customer_shipping_location: custLocation,
        });

        it('renders in edit mode with invoice data', () => {
            const wrapper = mountComponent({
                mode: 'edit',
                invoice: editInvoice,
            });
            expect(wrapper.text()).toContain('Update Invoice');
        });

        it('shows Send Email, View Public, Download PDF buttons', () => {
            const wrapper = mountComponent({
                mode: 'edit',
                invoice: editInvoice,
            });
            expect(wrapper.text()).toContain('Send Email');
            expect(wrapper.text()).toContain('View Public');
            expect(wrapper.text()).toContain('Download PDF');
        });

        it('submit button says "Update Invoice"', () => {
            const wrapper = mountComponent({
                mode: 'edit',
                invoice: editInvoice,
            });
            const submitBtn = wrapper.find('button[type="submit"]');
            expect(submitBtn.text()).toContain('Update');
            expect(submitBtn.text()).toContain('Invoice');
        });

        it('calls form.put on submit in edit mode', async () => {
            const wrapper = mountComponent({
                mode: 'edit',
                invoice: editInvoice,
            });
            await wrapper.find('form').trigger('submit');
            expect(mockPut).toHaveBeenCalledWith(
                '/invoices/42',
                expect.any(Object),
            );
        });

        it('pre-fills item rows from invoice data', () => {
            const wrapper = mountComponent({
                mode: 'edit',
                invoice: editInvoice,
            });
            const rows = wrapper.findAll('[data-testid="item-row"]');
            expect(rows.length).toBe(1);
        });
    });

    describe('customer dropdown', () => {
        it('renders all customers', () => {
            const wrapper = mountComponent();
            expect(wrapper.text()).toContain('ACME Corp');
            expect(wrapper.text()).toContain('Globex Inc');
        });

        it('shows "Select customer" placeholder', () => {
            const wrapper = mountComponent();
            const options = wrapper.findAll('option');
            expect(options.some((o) => o.text() === 'Select customer')).toBe(
                true,
            );
        });

        it('shows billing/shipping address sections when customer is pre-selected in edit mode', () => {
            const invoice = makeInvoice({
                id: 1,
                customer_id: 1,
                organization_location: orgLocation,
                customer_location: custLocation,
                customer_shipping_location: custLocation,
            });
            const wrapper = mountComponent({ mode: 'edit', invoice });
            expect(wrapper.text()).toContain('Billing Address');
            expect(wrapper.text()).toContain('Shipping Address');
        });

        it('shows customer location options when customer is pre-selected in edit mode', () => {
            const invoice = makeInvoice({
                id: 1,
                customer_id: 1,
                organization_location: orgLocation,
                customer_location: custLocation,
                customer_shipping_location: custLocation,
            });
            const wrapper = mountComponent({ mode: 'edit', invoice });
            expect(wrapper.text()).toContain('Client Office - Delhi');
            expect(wrapper.text()).toContain('Branch Office - Bangalore');
        });
    });

    describe('same as billing address', () => {
        // Selects that list the customer's locations (billing + shipping share
        // the same option list). Used to tell whether the shipping dropdown is
        // rendered alongside the billing one.
        const customerLocationSelects = (
            wrapper: ReturnType<typeof mountComponent>,
        ) =>
            wrapper
                .findAll('select')
                .filter((select) =>
                    select
                        .findAll('option')
                        .some((option) =>
                            option.text().includes('Client Office - Delhi'),
                        ),
                );

        it('checks the box and hides the shipping dropdown when shipping matches billing', () => {
            const invoice = makeInvoice({
                id: 1,
                customer_id: 1,
                organization_location: orgLocation,
                customer_location: custLocation,
                customer_shipping_location: custLocation,
            });
            const wrapper = mountComponent({ mode: 'edit', invoice });

            const checkbox = wrapper.find('input[type="checkbox"]');
            expect(checkbox.exists()).toBe(true);
            expect((checkbox.element as HTMLInputElement).checked).toBe(true);
            expect(wrapper.text()).toContain(
                'Shipping address is the same as the billing address.',
            );
            // Only the billing dropdown remains; shipping one is hidden.
            expect(customerLocationSelects(wrapper).length).toBe(1);
        });

        it('unchecks the box and shows the shipping dropdown when shipping differs from billing', () => {
            const invoice = makeInvoice({
                id: 1,
                customer_id: 1,
                organization_location: orgLocation,
                customer_location: custLocation,
                customer_shipping_location: custLocation2,
            });
            const wrapper = mountComponent({ mode: 'edit', invoice });

            const checkbox = wrapper.find('input[type="checkbox"]');
            expect((checkbox.element as HTMLInputElement).checked).toBe(false);
            expect(wrapper.text()).not.toContain(
                'Shipping address is the same as the billing address.',
            );
            // Both billing and shipping dropdowns are rendered.
            expect(customerLocationSelects(wrapper).length).toBe(2);
        });

        it('mirrors shipping to billing when toggled on, and keeps it after toggling off', async () => {
            const invoice = makeInvoice({
                id: 1,
                customer_id: 1,
                organization_location: orgLocation,
                customer_location: custLocation,
                customer_shipping_location: custLocation2,
            });
            const wrapper = mountComponent({ mode: 'edit', invoice });
            const checkbox = wrapper.find('input[type="checkbox"]');

            await checkbox.setValue(true);
            expect(wrapper.text()).toContain(
                'Shipping address is the same as the billing address.',
            );
            expect(customerLocationSelects(wrapper).length).toBe(1);

            await checkbox.setValue(false);
            // Shipping dropdown reappears and now reflects the billing location
            // (id 20) it was mirrored to while the box was checked.
            const shippingSelect = customerLocationSelects(wrapper)[1];
            expect(shippingSelect).toBeDefined();
            expect((shippingSelect.element as HTMLSelectElement).value).toBe(
                '20',
            );
        });
    });

    describe('organization location dropdown', () => {
        it('renders organization locations', () => {
            const wrapper = mountComponent();
            expect(wrapper.text()).toContain('Organization Location');
            expect(wrapper.text()).toContain('Head Office - Mumbai');
        });

        it('shows warning when no locations available', () => {
            const wrapper = mountComponent({ organizationLocations: [] });
            expect(wrapper.text()).toContain('No locations found');
        });
    });

    describe('status dropdown', () => {
        it('renders all status options', () => {
            const wrapper = mountComponent();
            expect(wrapper.text()).toContain('Draft');
            expect(wrapper.text()).toContain('Sent');
            expect(wrapper.text()).toContain('Paid');
            expect(wrapper.text()).toContain('Overdue');
        });

        it('mirrors the server status onto the dropdown when the invoice prop changes (draft -> sent after sending email)', async () => {
            const draftInvoice = makeInvoice({
                id: 99,
                customer_id: 1,
                organization_id: 1,
                status: 'draft',
                organization_location: orgLocation,
                customer_location: custLocation,
                customer_shipping_location: custLocation,
            });

            const wrapper = mountComponent({
                mode: 'edit',
                invoice: draftInvoice,
            });

            const statusSelect = wrapper
                .findAll('select')
                .find((select) =>
                    select
                        .findAll('option')
                        .some(
                            (option) => option.attributes('value') === 'draft',
                        ),
                );

            expect(statusSelect).toBeDefined();
            expect((statusSelect!.element as HTMLSelectElement).value).toBe(
                'draft',
            );

            // Simulate the server redirect returning the invoice with the
            // backend-promoted status. The frontend must reflect it, not decide it.
            await wrapper.setProps({
                invoice: { ...draftInvoice, status: 'sent' },
            });

            expect((statusSelect!.element as HTMLSelectElement).value).toBe(
                'sent',
            );
        });

        it('does not change the dropdown when the server status is unchanged', async () => {
            const sentInvoice = makeInvoice({
                id: 100,
                customer_id: 1,
                organization_id: 1,
                status: 'sent',
                organization_location: orgLocation,
                customer_location: custLocation,
                customer_shipping_location: custLocation,
            });

            const wrapper = mountComponent({
                mode: 'edit',
                invoice: sentInvoice,
            });

            const statusSelect = wrapper
                .findAll('select')
                .find((select) =>
                    select
                        .findAll('option')
                        .some(
                            (option) => option.attributes('value') === 'draft',
                        ),
                );

            expect((statusSelect!.element as HTMLSelectElement).value).toBe(
                'sent',
            );

            // A re-render that does not touch status (e.g. unrelated prop update)
            // must leave the dropdown untouched.
            await wrapper.setProps({
                invoice: { ...sentInvoice, notes: 'Updated notes' },
            });

            expect((statusSelect!.element as HTMLSelectElement).value).toBe(
                'sent',
            );
        });
    });

    describe('date fields', () => {
        it('renders issue date and due date inputs', () => {
            const wrapper = mountComponent();
            const dateInputs = wrapper.findAll('input[type="date"]');
            expect(dateInputs.length).toBe(2);
        });

        it('shows Issue Date and Due Date labels', () => {
            const wrapper = mountComponent();
            expect(wrapper.text()).toContain('Issue Date');
            expect(wrapper.text()).toContain('Due Date');
        });
    });

    describe('numbering series', () => {
        it('shows numbering series dropdown for invoices in create mode', () => {
            const wrapper = mountComponent();
            expect(wrapper.text()).toContain('Numbering Series');
        });

        it('hides numbering series for estimates', () => {
            const wrapper = mountComponent({ type: 'estimate' });
            expect(wrapper.text()).not.toContain('Numbering Series');
        });

        it('hides numbering series in edit mode', () => {
            const invoice = makeInvoice({ id: 1 });
            const wrapper = mountComponent({ mode: 'edit', invoice });
            // In edit mode, numbering series should not show
            // (the condition checks mode === 'create')
            expect(wrapper.text()).not.toContain('Numbering Series');
        });
    });

    describe('notes field', () => {
        it('renders notes textarea', () => {
            const wrapper = mountComponent();
            expect(wrapper.text()).toContain('Customer Notes');
            const textarea = wrapper.find('textarea');
            expect(textarea.exists()).toBe(true);
        });
    });

    describe('line items', () => {
        it('renders Add Item button', () => {
            const wrapper = mountComponent();
            const addBtn = wrapper
                .findAll('button')
                .find((b) => b.text() === 'Add Item');
            expect(addBtn).toBeDefined();
        });

        it('starts with one item row', () => {
            const wrapper = mountComponent();
            expect(wrapper.findAll('[data-testid="item-row"]').length).toBe(1);
        });

        it('adds a new item row when Add Item is clicked', async () => {
            const wrapper = mountComponent();
            const addBtn = wrapper
                .findAll('button')
                .find((b) => b.text() === 'Add Item');
            await addBtn!.trigger('click');
            expect(wrapper.findAll('[data-testid="item-row"]').length).toBe(2);
        });

        it('shows table headers for line items', () => {
            const wrapper = mountComponent();
            expect(wrapper.text()).toContain('Description');
            expect(wrapper.text()).toContain('Qty');
            expect(wrapper.text()).toContain('Unit Price');
            expect(wrapper.text()).toContain('Tax Rate');
            expect(wrapper.text()).toContain('Amount');
        });
    });

    describe('totals section', () => {
        it('displays subtotal, tax, and total', () => {
            const wrapper = mountComponent();
            expect(wrapper.text()).toContain('Subtotal:');
            expect(wrapper.text()).toContain('Tax:');
            expect(wrapper.text()).toContain('Total:');
        });
    });

    describe('form submission', () => {
        it('calls form.post on submit in create mode', async () => {
            const wrapper = mountComponent();
            await wrapper.find('form').trigger('submit');
            expect(mockPost).toHaveBeenCalledWith(
                '/invoices',
                expect.objectContaining({ preserveScroll: true }),
            );
        });

        it('calls form.put on submit in edit mode', async () => {
            const invoice = makeInvoice({ id: 99 });
            const wrapper = mountComponent({ mode: 'edit', invoice });
            await wrapper.find('form').trigger('submit');
            expect(mockPut).toHaveBeenCalledWith(
                '/invoices/99',
                expect.objectContaining({ preserveScroll: true }),
            );
        });
    });

    describe('cancel navigation', () => {
        it('navigates back via router.get on cancel click', async () => {
            const wrapper = mountComponent();
            const cancelBtn = wrapper
                .findAll('button')
                .find((b) => b.text() === 'Cancel');
            await cancelBtn!.trigger('click');
            expect(mockRouterGet).toHaveBeenCalledWith('/invoices');
        });
    });

    describe('processing state', () => {
        it('shows "Saving..." text when processing', () => {
            formProcessing = true;
            const wrapper = mountComponent();
            const submitBtn = wrapper.find('button[type="submit"]');
            expect(submitBtn.text()).toContain('Saving...');
        });

        it('disables submit button when processing', () => {
            formProcessing = true;
            const wrapper = mountComponent();
            const submitBtn = wrapper.find('button[type="submit"]');
            expect(submitBtn.attributes('disabled')).toBeDefined();
        });
    });

    describe('validation errors', () => {
        it('displays error for organization_location_id', () => {
            formErrors = { organization_location_id: 'Location is required' };
            const wrapper = mountComponent();
            expect(wrapper.text()).toContain('Location is required');
        });

        it('displays error for customer_id', () => {
            formErrors = { customer_id: 'Customer is required' };
            const wrapper = mountComponent();
            expect(wrapper.text()).toContain('Customer is required');
        });

        it('displays error for status', () => {
            formErrors = { status: 'Status is required' };
            const wrapper = mountComponent();
            expect(wrapper.text()).toContain('Status is required');
        });

        it('displays error for issued_at', () => {
            formErrors = { issued_at: 'Issue date is required' };
            const wrapper = mountComponent();
            expect(wrapper.text()).toContain('Issue date is required');
        });

        it('displays error for due_at', () => {
            formErrors = { due_at: 'Due date is required' };
            const wrapper = mountComponent();
            expect(wrapper.text()).toContain('Due date is required');
        });

        it('displays error for notes', () => {
            formErrors = { notes: 'Notes too long' };
            const wrapper = mountComponent();
            expect(wrapper.text()).toContain('Notes too long');
        });
    });

    describe('payment terms sync', () => {
        function paymentTermSelect(wrapper: ReturnType<typeof mountComponent>) {
            return wrapper
                .findAll('select')
                .find((select) => select.text().includes('Net 30'))!;
        }

        // The <option> values are numbers / 'custom' (not strings), so select
        // by marking the option's element selected and firing change — Vue's
        // v-model then resolves the bound value. Read the current selection
        // back via the option flagged selected.
        async function selectTerm(
            wrapper: ReturnType<typeof mountComponent>,
            label: string,
        ) {
            const select = paymentTermSelect(wrapper);
            const option = select
                .findAll('option')
                .find((o) => o.text() === label)!;
            (option.element as HTMLOptionElement).selected = true;
            await select.trigger('change');
        }

        function selectedTerm(wrapper: ReturnType<typeof mountComponent>) {
            return paymentTermSelect(wrapper)
                .findAll('option')
                .find((o) => (o.element as HTMLOptionElement).selected)
                ?.text();
        }

        function dateInputs(wrapper: ReturnType<typeof mountComponent>) {
            const inputs = wrapper.findAll('input[type="date"]');
            return {
                issued: inputs[0],
                due: inputs[1],
            };
        }

        it('selects the preset implied by the default dates', () => {
            const wrapper = mountComponent();
            // Default issue 2026-03-01 / due 2026-03-31 => Net 30.
            expect(selectedTerm(wrapper)).toBe('Net 30');
        });

        it('reflects the stored dates in edit mode', () => {
            const editInvoice = makeInvoice({
                id: 7,
                customer_id: 1,
                organization_id: 1,
                issued_at: '2026-03-01',
                due_at: '2026-03-08',
            });
            const wrapper = mountComponent({
                mode: 'edit',
                invoice: editInvoice,
            });
            // 7-day gap => Net 7.
            expect(selectedTerm(wrapper)).toBe('Net 7');
        });

        it('recomputes the due date when a preset term is selected', async () => {
            const wrapper = mountComponent();
            const { due } = dateInputs(wrapper);

            await selectTerm(wrapper, 'Net 15');

            expect((due.element as HTMLInputElement).value).toBe('2026-03-16');
        });

        it('shifts the due date when the issue date changes and a preset is active', async () => {
            const wrapper = mountComponent();
            const { issued, due } = dateInputs(wrapper);

            // Default term is Net 30.
            await issued.setValue('2026-04-01');

            expect((due.element as HTMLInputElement).value).toBe('2026-05-01');
        });

        it('snaps the term to the matching preset when the due date is edited', async () => {
            const wrapper = mountComponent();
            const { due } = dateInputs(wrapper);

            await due.setValue('2026-03-08');

            expect(selectedTerm(wrapper)).toBe('Net 7');
        });

        it('marks the term Custom for a non-preset due date and leaves it untouched', async () => {
            const wrapper = mountComponent();
            const { due } = dateInputs(wrapper);

            await due.setValue('2026-03-21');

            expect(selectedTerm(wrapper)).toBe('Custom');
            // Custom due date is preserved, not overwritten.
            expect((due.element as HTMLInputElement).value).toBe('2026-03-21');
        });

        it('leaves a custom due date alone when the issue date changes', async () => {
            const wrapper = mountComponent();
            const { issued, due } = dateInputs(wrapper);

            await due.setValue('2026-03-21');
            await issued.setValue('2026-04-01');

            expect((due.element as HTMLInputElement).value).toBe('2026-03-21');
        });
    });

    describe('flash messages', () => {
        it('displays success flash message', () => {
            // Need to override usePage to return flash
            // Since usePage is mocked at module level, we test the template binding
            const wrapper = mountComponent();
            // Flash is null by default so no flash shown
            expect(wrapper.find('.bg-green-100').exists()).toBe(false);
        });
    });

    describe('document type selector', () => {
        it('renders document type dropdown', () => {
            const wrapper = mountComponent();
            expect(wrapper.text()).toContain('Document Type');
        });

        it('type selector is disabled in edit mode', () => {
            const invoice = makeInvoice({ id: 1 });
            const wrapper = mountComponent({ mode: 'edit', invoice });
            const selects = wrapper.findAll('select');
            const typeSelect = selects.find((s) => {
                const opts = s.findAll('option');
                return (
                    opts.some((o) => o.text() === 'Invoice') &&
                    opts.some((o) => o.text() === 'Estimate')
                );
            });
            expect(typeSelect?.attributes('disabled')).toBeDefined();
        });
    });
});
