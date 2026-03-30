import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

const { mockRouterReload } = vi.hoisted(() => ({
    mockRouterReload: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: () => ({}),
    Head: { template: '<div />' },
    Link: { template: '<a><slot /></a>' },
    router: {
        get: vi.fn(),
        post: vi.fn(),
        delete: vi.fn(),
        reload: mockRouterReload,
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

import Dashboard from '@/Pages/Dashboard.vue';

function makeStats(overrides = {}) {
    return {
        total_revenue: 50000000,
        total_collected: 30000000,
        total_outstanding: 20000000,
        invoice_count: 10,
        paid_count: 6,
        overdue_count: 2,
        collection_rate: 60,
        currency: 'INR' as const,
        ...overrides,
    };
}

function defaultProps(overrides = {}) {
    return {
        period: 'this_month',
        organizationName: 'Clarity Technologies',
        stats: makeStats(),
        statusBreakdown: [
            {
                status: 'draft' as const,
                label: 'Draft',
                count: 3,
                total: 15000000,
            },
            {
                status: 'paid' as const,
                label: 'Paid',
                count: 6,
                total: 30000000,
            },
        ],
        recentInvoices: [
            {
                id: 1,
                invoice_number: 'INV-001',
                status: 'paid' as const,
                customer_name: 'ACME',
                issued_at: '2026-03-01',
                total: 10000000,
                currency: 'INR' as const,
            },
        ],
        overdueInvoices: [
            {
                id: 2,
                invoice_number: 'INV-002',
                customer_name: 'Beta Corp',
                remaining_balance: 5000000,
                currency: 'INR' as const,
                due_at_human: '3 days ago',
            },
        ],
        recentPayments: [
            {
                id: 1,
                invoice_number: 'INV-001',
                customer_name: 'ACME',
                amount: 5000000,
                currency: 'INR' as const,
                payment_method: 'bank_transfer',
                payment_date: '2026-03-15',
            },
        ],
        topCustomers: [
            {
                name: 'ACME Corp',
                invoice_count: 5,
                total: 25000000,
                paid: 20000000,
                outstanding: 5000000,
            },
        ],
        monthlyTrend: [
            {
                label: 'October 2025',
                short: 'Oct',
                invoiced: 8000000,
                collected: 6000000,
            },
            {
                label: 'November 2025',
                short: 'Nov',
                invoiced: 10000000,
                collected: 7000000,
            },
        ],
        customerCount: 12,
        estimateStats: { count: 4, total: 8000000, accepted: 2 },
        ...overrides,
    };
}

function mountComponent(propsOverride = {}) {
    return mount(Dashboard, {
        props: defaultProps(propsOverride),
        global: {
            stubs: {
                AppLayout: {
                    template: '<div><slot name="header" /><slot /></div>',
                },
                StatusBadge: {
                    template: '<span>{{ status }}</span>',
                    props: ['status'],
                },
            },
        },
    });
}

describe('Dashboard', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders organization name', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Clarity Technologies');
    });

    it('renders subtitle', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Business overview and analytics');
    });

    it('renders KPI cards', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Total Invoiced');
        expect(wrapper.text()).toContain('Collected');
        expect(wrapper.text()).toContain('Outstanding');
        expect(wrapper.text()).toContain('Customers');
    });

    it('shows invoice count', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('10 invoices');
    });

    it('shows collection rate', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('60% collection rate');
    });

    it('shows overdue count', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('2 overdue');
    });

    it('shows customer count', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('12');
    });

    it('shows estimate stats', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('4 estimates');
        expect(wrapper.text()).toContain('2 accepted');
    });

    it('renders period selector with all options', () => {
        const wrapper = mountComponent();
        const options = wrapper.findAll('select option');
        expect(options.length).toBe(6);
        expect(options.map((o) => o.text())).toEqual([
            'This Week',
            'This Month',
            'Last Month',
            'This Quarter',
            'This Year',
            'All Time',
        ]);
    });

    it('calls router.reload on period change', async () => {
        const wrapper = mountComponent();
        const select = wrapper.find('select');
        await select.setValue('this_quarter');
        await select.trigger('change');
        expect(mockRouterReload).toHaveBeenCalled();
    });

    it('renders status breakdown', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Invoice Status Breakdown');
        expect(wrapper.text()).toContain('draft');
        expect(wrapper.text()).toContain('paid');
    });

    it('shows "No invoices in this period" when status breakdown empty', () => {
        const wrapper = mountComponent({ statusBreakdown: [] });
        expect(wrapper.text()).toContain('No invoices in this period');
    });

    it('renders monthly trend section', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('6-Month Trend');
        expect(wrapper.text()).toContain('Oct');
        expect(wrapper.text()).toContain('Nov');
    });

    it('renders recent invoices', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Recent Invoices');
        expect(wrapper.text()).toContain('INV-001');
        expect(wrapper.text()).toContain('ACME');
    });

    it('shows empty recent invoices message', () => {
        const wrapper = mountComponent({ recentInvoices: [] });
        expect(wrapper.text()).toContain('No invoices yet');
    });

    it('renders overdue invoices', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Overdue Invoices');
        expect(wrapper.text()).toContain('INV-002');
        expect(wrapper.text()).toContain('Beta Corp');
        expect(wrapper.text()).toContain('3 days ago');
    });

    it('shows no overdue message when empty', () => {
        const wrapper = mountComponent({ overdueInvoices: [] });
        expect(wrapper.text()).toContain('No overdue invoices');
    });

    it('renders top customers', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Top Customers');
        expect(wrapper.text()).toContain('ACME Corp');
        expect(wrapper.text()).toContain('5 invoices');
    });

    it('shows empty top customers message', () => {
        const wrapper = mountComponent({ topCustomers: [] });
        expect(wrapper.text()).toContain('No customer data in this period');
    });

    it('renders recent payments', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Recent Payments');
        expect(wrapper.text()).toContain('INV-001');
        expect(wrapper.text()).toContain('bank transfer');
    });

    it('shows empty payments message', () => {
        const wrapper = mountComponent({ recentPayments: [] });
        expect(wrapper.text()).toContain('No payments recorded yet');
    });

    it('renders quick action links', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('New Invoice');
        expect(wrapper.text()).toContain('New Estimate');
        expect(wrapper.text()).toContain('Customers');
        expect(wrapper.text()).toContain('Settings');
    });

    it('shows singular invoice text for count of 1', () => {
        const wrapper = mountComponent({
            stats: makeStats({ invoice_count: 1 }),
        });
        expect(wrapper.text()).toContain('1 invoice');
        expect(wrapper.text()).not.toContain('1 invoices');
    });

    it('shows singular estimate text for count of 1', () => {
        const wrapper = mountComponent({
            estimateStats: { count: 1, total: 1000, accepted: 0 },
        });
        expect(wrapper.text()).toContain('1 estimate');
        expect(wrapper.text()).not.toContain('1 estimates');
    });
});
