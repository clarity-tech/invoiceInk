import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { makeInvoice, makeCustomer } from '../../helpers';

const {
    mockRouterGet,
    mockRouterPost,
    mockRouterDelete,
    mockRouterReload,
    mockOptimisticDelete,
    mockOptimistic,
} = vi.hoisted(() => {
    const mockOptimisticDelete = vi.fn();
    return {
        mockRouterGet: vi.fn(),
        mockRouterPost: vi.fn(),
        mockRouterDelete: vi.fn(),
        mockRouterReload: vi.fn(),
        mockOptimisticDelete,
        mockOptimistic: vi
            .fn()
            .mockReturnValue({ delete: mockOptimisticDelete }),
    };
});

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
        get: mockRouterGet,
        post: mockRouterPost,
        delete: mockRouterDelete,
        reload: mockRouterReload,
        optimistic: mockOptimistic,
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
                    role: 'owner',
                    permissions: ['*'],
                },
            },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

import InvoicesIndex from '@/Pages/Invoices/Index.vue';

function makePaginatedInvoices(
    data: ReturnType<typeof makeInvoice>[] = [],
    overrides = {},
) {
    return {
        data,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: data.length,
        links: [
            { url: null, label: '&laquo; Previous', active: false },
            { url: '/invoices?page=1', label: '1', active: true },
            { url: null, label: 'Next &raquo;', active: false },
        ],
        ...overrides,
    };
}

const defaultProps = () => ({
    invoices: makePaginatedInvoices(),
    filters: { type: null, status: null },
    statusOptions: { draft: 'Draft', sent: 'Sent', paid: 'Paid', void: 'Void' },
});

function mountComponent(propsOverride = {}) {
    return mount(InvoicesIndex, {
        props: { ...defaultProps(), ...propsOverride },
        global: {
            stubs: {
                AppLayout: {
                    template: '<div><slot name="header" /><slot /></div>',
                },
                MoneyDisplay: {
                    template: '<span>{{ amount }}</span>',
                    props: ['amount', 'currency'],
                },
                StatusBadge: {
                    template: '<span>{{ status }}</span>',
                    props: ['status'],
                },
                ConfirmationModal: {
                    template:
                        '<div v-if="show" data-testid="confirm-modal"><slot /><button data-testid="confirm-btn" @click="$emit(\'confirm\')">Confirm</button></div>',
                    props: [
                        'show',
                        'title',
                        'message',
                        'confirmLabel',
                        'destructive',
                    ],
                    emits: ['confirm', 'cancel'],
                },
            },
        },
    });
}

describe('Invoices/Index collection signals', () => {
    // "Overdue" is not a status -- it is derived from due_at -- so the badge
    // alone showed an invoice months late as plainly "Sent", and Total alone
    // overstated what was collectable on a part-paid document.

    it('says how late an overdue document is', () => {
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([
                makeInvoice({
                    id: 1,
                    status: 'sent',
                    is_overdue: true,
                    days_overdue: 87,
                }),
            ]),
        });

        expect(wrapper.text()).toContain('Overdue by 87 days');
    });

    it('says one day rather than 1 days', () => {
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([
                makeInvoice({
                    id: 1,
                    status: 'sent',
                    is_overdue: true,
                    days_overdue: 1,
                }),
            ]),
        });

        expect(wrapper.text()).toContain('Overdue by 1 day');
        expect(wrapper.text()).not.toContain('1 days');
    });

    it('stays quiet when the document is not overdue', () => {
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([
                makeInvoice({ id: 1, status: 'sent', is_overdue: false }),
            ]),
        });

        expect(wrapper.text()).not.toContain('Overdue');
    });

    it('states the balance when it differs from the total', () => {
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([
                makeInvoice({
                    id: 1,
                    status: 'partially_paid',
                    total: 5900000,
                    amount_paid: 2000000,
                    remaining_balance: 3900000,
                }),
            ]),
        });

        expect(wrapper.text()).toContain('Due:');
    });

    it('does not restate the total as a balance when nothing is paid', () => {
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([
                makeInvoice({
                    id: 1,
                    total: 5900000,
                    amount_paid: 0,
                    remaining_balance: 5900000,
                }),
            ]),
        });

        expect(wrapper.text()).not.toContain('Due:');
    });

    it('says nothing about a balance once the document is settled', () => {
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([
                makeInvoice({
                    id: 1,
                    status: 'paid',
                    total: 5900000,
                    amount_paid: 5900000,
                    remaining_balance: 0,
                }),
            ]),
        });

        expect(wrapper.text()).not.toContain('Due:');
    });

    it('shows the due date, which is what decides whether to chase', () => {
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([
                makeInvoice({ id: 1, due_at: '2026-10-25' }),
            ]),
        });

        expect(wrapper.text()).toContain('Due ');
    });
});

describe('Invoices/Index', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders page title', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Invoices & Estimates');
    });

    it('renders invoice list', () => {
        const customer = makeCustomer({ id: 1, name: 'ACME Corp' });
        const inv = makeInvoice({ id: 1, invoice_number: 'INV-001', customer });
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([inv]),
        });
        expect(wrapper.text()).toContain('INV-001');
        expect(wrapper.text()).toContain('ACME Corp');
    });

    it('shows the subject beneath the customer name', () => {
        const customer = makeCustomer({ id: 1, name: 'ACME Corp' });
        const inv = makeInvoice({
            id: 1,
            customer,
            subject: 'Website redesign, phase 2',
        });
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([inv]),
        });

        expect(wrapper.text()).toContain('Website redesign, phase 2');
        // Carried on `title` too, so the full text survives truncation for
        // hover and assistive tech.
        expect(wrapper.html()).toContain('title="Website redesign, phase 2"');
    });

    it('omits the subject line when there is no subject', () => {
        const customer = makeCustomer({ id: 1, name: 'ACME Corp' });
        const inv = makeInvoice({ id: 1, customer, subject: null });
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([inv]),
        });

        expect(wrapper.text()).toContain('ACME Corp');
        // No stray empty element where the subject would sit. Matched on the
        // width constraint, which is unique to the subject line -- the plain
        // `text-xs text-gray-400` this used to look for is shared with other
        // secondary text in the row, so it reported a collision as a failure.
        expect(wrapper.html()).not.toContain('mt-0.5 max-w-[220px]');
    });

    it('constrains the subject to the same width as the customer name', () => {
        // This is the real guard. Verified by removing the classes and watching
        // it fail; InvoiceListLayoutTest does NOT catch it, because an
        // unconstrained subject wraps onto more lines rather than overflowing.
        const inv = makeInvoice({
            id: 1,
            subject: 'A'.repeat(300),
        });
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([inv]),
        });

        const subjectEl = wrapper
            .findAll('div')
            .find((d) => d.text().startsWith('AAAA'))!;

        expect(subjectEl.classes()).toContain('truncate');
        expect(subjectEl.classes()).toContain('max-w-[220px]');
    });

    it('shows empty state when no invoices', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('No documents yet');
        expect(wrapper.text()).toContain(
            'Get started by creating your first invoice or estimate',
        );
    });

    it('renders all three tabs', () => {
        const wrapper = mountComponent();
        const buttons = wrapper
            .findAll('button')
            .filter((b) => ['All', 'Invoices', 'Estimates'].includes(b.text()));
        expect(buttons).toHaveLength(3);
    });

    it('switches tab and calls router.get', async () => {
        const wrapper = mountComponent();
        const invoicesTab = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Invoices');
        await invoicesTab!.trigger('click');
        expect(mockRouterGet).toHaveBeenCalledWith(
            '/invoices',
            { type: 'invoice' },
            expect.any(Object),
        );
    });

    it('filters by status and calls router.get', async () => {
        const wrapper = mountComponent();
        const select = wrapper.find('select');
        await select.setValue('paid');
        expect(mockRouterGet).toHaveBeenCalledWith(
            '/invoices',
            { status: 'paid' },
            expect.any(Object),
        );
    });

    it('shows status filter with all options', () => {
        const wrapper = mountComponent();
        const options = wrapper.findAll('select option');
        expect(options.length).toBe(5); // All Statuses + 4 options
    });

    it('renders type badge for invoice', () => {
        const inv = makeInvoice({ type: 'invoice' });
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([inv]),
        });
        expect(wrapper.text()).toContain('Invoice');
    });

    it('renders type badge for estimate', () => {
        const inv = makeInvoice({ type: 'estimate' });
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([inv]),
        });
        expect(wrapper.text()).toContain('Estimate');
    });

    // Convert, Edit, Delete and the public View all act on one document, so
    // they live on that document's page now. The list keeps only the two that
    // are useful without opening anything.
    it('leaves per-document actions to the document page', () => {
        const invoice = makeInvoice({ id: 1, type: 'invoice' });
        const estimate = makeInvoice({ id: 2, type: 'estimate' });
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([invoice, estimate]),
        });

        const labels = [
            ...wrapper.findAll('button'),
            ...wrapper.findAll('a'),
        ].map((el) => el.text());

        expect(labels).not.toContain('Convert');
        expect(labels).not.toContain('Edit');
        expect(labels).not.toContain('Delete');
        expect(labels).not.toContain('View');
    });

    // The list used to delete optimistically, which is why it still carries
    // the focus-restoring scroll region. Deleting now happens on the document's
    // own page, where the server redirects here afterwards, so nothing on this
    // page should be able to destroy a row.
    it('never deletes from the list', async () => {
        const inv = makeInvoice({ id: 5 });
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([inv]),
        });

        expect(wrapper.find('[data-testid="confirm-modal"]').exists()).toBe(
            false,
        );

        for (const button of wrapper.findAll('button')) {
            await button.trigger('click');
        }

        expect(mockOptimistic).not.toHaveBeenCalled();
        expect(mockOptimisticDelete).not.toHaveBeenCalled();
        expect(mockRouterDelete).not.toHaveBeenCalled();
    });

    it('links to the duplicate form instead of posting', async () => {
        const inv = makeInvoice({ id: 3 });
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([inv]),
        });
        const dupLink = wrapper
            .findAll('a')
            .find((a) => a.text() === 'Duplicate');

        expect(dupLink?.attributes('href')).toBe('/invoices/3/duplicate');
        // Duplicating must not mutate anything — it only opens a form.
        expect(mockRouterPost).not.toHaveBeenCalled();
    });

    it('shows pagination when multiple pages', () => {
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([], { last_page: 3, total: 45 }),
        });
        expect(wrapper.text()).toContain('Showing page');
    });

    it('hides pagination for single page', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).not.toContain('Showing page');
    });

    it('renders create links', () => {
        const wrapper = mountComponent();
        const links = wrapper.findAll('a');
        const createInvoice = links.find(
            (a) => a.attributes('href') === '/invoices/create',
        );
        const createEstimate = links.find(
            (a) => a.attributes('href') === '/estimates/create',
        );
        expect(createInvoice).toBeTruthy();
        expect(createEstimate).toBeTruthy();
    });

    it('opens the document from its number, and keeps the PDF on the row', () => {
        const inv = makeInvoice({ id: 7, ulid: 'TESTULID123' });
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([inv]),
        });
        const links = wrapper.findAll('a');

        expect(links.some((a) => a.attributes('href') === '/invoices/7')).toBe(
            true,
        );
        expect(
            links.some(
                (a) => a.attributes('href') === '/invoices/TESTULID123/pdf',
            ),
        ).toBe(true);
        // The edit form is reached through the document, not from the row.
        expect(
            links.some((a) => a.attributes('href') === '/invoices/7/edit'),
        ).toBe(false);
    });

    it('uses the estimate path for an estimate PDF', () => {
        const est = makeInvoice({ type: 'estimate', ulid: 'EST_ULID_123' });
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([est]),
        });
        const pdfLink = wrapper.findAll('a').find((a) => a.text() === 'PDF');
        expect(pdfLink!.attributes('href')).toBe('/estimates/EST_ULID_123/pdf');
    });
});
