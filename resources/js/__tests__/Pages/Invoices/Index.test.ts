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
                    personal_team: false,
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
        expect(wrapper.text()).toContain('INVOICE');
    });

    it('renders type badge for estimate', () => {
        const inv = makeInvoice({ type: 'estimate' });
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([inv]),
        });
        expect(wrapper.text()).toContain('ESTIMATE');
    });

    it('shows Convert button only for estimates', () => {
        const invoice = makeInvoice({ id: 1, type: 'invoice' });
        const estimate = makeInvoice({ id: 2, type: 'estimate' });
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([invoice, estimate]),
        });
        const convertButtons = wrapper
            .findAll('button')
            .filter((b) => b.text() === 'Convert');
        expect(convertButtons).toHaveLength(1);
    });

    it('opens delete confirmation modal', async () => {
        const inv = makeInvoice({ id: 1 });
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([inv]),
        });
        const deleteBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Delete');
        await deleteBtn!.trigger('click');
        expect(wrapper.find('[data-testid="confirm-modal"]').exists()).toBe(
            true,
        );
    });

    it('calls router.optimistic().delete on confirm delete', async () => {
        const inv = makeInvoice({ id: 5 });
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([inv]),
        });

        // Open modal
        const deleteBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Delete');
        await deleteBtn!.trigger('click');

        // Confirm
        const confirmBtn = wrapper.find('[data-testid="confirm-btn"]');
        await confirmBtn.trigger('click');

        expect(mockOptimistic).toHaveBeenCalled();
        expect(mockOptimisticDelete).toHaveBeenCalledWith(
            '/invoices/5',
            expect.any(Object),
        );
    });

    it('calls router.post for duplicate', async () => {
        const inv = makeInvoice({ id: 3 });
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([inv]),
        });
        const dupBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Duplicate');
        await dupBtn!.trigger('click');
        expect(mockRouterPost).toHaveBeenCalledWith(
            '/invoices/3/duplicate',
            {},
            expect.any(Object),
        );
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

    it('renders edit and PDF links for each invoice', () => {
        const inv = makeInvoice({ id: 7, ulid: 'TESTULID123' });
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([inv]),
        });
        const links = wrapper.findAll('a');
        expect(
            links.some((a) => a.attributes('href') === '/invoices/7/edit'),
        ).toBe(true);
        expect(
            links.some(
                (a) => a.attributes('href') === '/invoices/TESTULID123/pdf',
            ),
        ).toBe(true);
    });

    it('generates correct public URL for estimate', () => {
        const est = makeInvoice({ type: 'estimate', ulid: 'EST_ULID_123' });
        const wrapper = mountComponent({
            invoices: makePaginatedInvoices([est]),
        });
        const viewLink = wrapper.findAll('a').find((a) => a.text() === 'View');
        expect(viewLink!.attributes('href')).toBe(
            '/estimates/view/EST_ULID_123',
        );
    });
});
