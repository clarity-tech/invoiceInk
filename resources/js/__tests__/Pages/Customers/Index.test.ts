import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { makeCustomer, makeLocation, makeContact } from '../../helpers';

const {
    mockPost,
    mockPut,
    mockRouterGet,
    mockRouterPost,
    mockRouterDelete,
    mockRouterReload,
    mockOptimisticDelete,
    mockOptimistic,
    mockReset,
    mockClearErrors,
} = vi.hoisted(() => {
    const mockOptimisticDelete = vi.fn();
    return {
        mockPost: vi.fn(),
        mockPut: vi.fn(),
        mockRouterGet: vi.fn(),
        mockRouterPost: vi.fn(),
        mockRouterDelete: vi.fn(),
        mockRouterReload: vi.fn(),
        mockOptimisticDelete,
        mockOptimistic: vi
            .fn()
            .mockReturnValue({ delete: mockOptimisticDelete }),
        mockReset: vi.fn(),
        mockClearErrors: vi.fn(),
    };
});

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
                if (prop === 'reset') return mockReset;
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

import CustomersIndex from '@/Pages/Customers/Index.vue';

type CustomerWithCount = ReturnType<typeof makeCustomer> & {
    locations_count: number;
};

function makeCustomerWithCount(overrides = {}): CustomerWithCount {
    return {
        ...makeCustomer(overrides),
        locations_count: 0,
    } as CustomerWithCount;
}

function makePaginatedCustomers(
    data: CustomerWithCount[] = [],
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
            { url: '/customers?page=1', label: '1', active: true },
            { url: null, label: 'Next &raquo;', active: false },
        ],
        ...overrides,
    };
}

const defaultProps = () => ({
    customers: makePaginatedCustomers(),
    currencies: { INR: 'Indian Rupee', USD: 'US Dollar', EUR: 'Euro' },
    countries: { IN: 'India', US: 'United States' },
});

function mountComponent(propsOverride = {}) {
    return mount(CustomersIndex, {
        props: { ...defaultProps(), ...propsOverride },
        global: {
            stubs: {
                AppLayout: {
                    template: '<div><slot name="header" /><slot /></div>',
                },
                CustomerForm: {
                    template: '<div data-testid="customer-form" />',
                    props: ['form', 'currencies', 'countries', 'isEditing'],
                    emits: ['submit', 'cancel'],
                },
                LocationModal: {
                    template: '<div data-testid="location-modal" />',
                    props: ['show', 'customerId', 'location', 'countries'],
                    emits: ['close'],
                },
                ConfirmationModal: {
                    template:
                        '<div v-if="show" data-testid="confirm-modal"><button data-testid="confirm-btn" @click="$emit(\'confirm\')">Confirm</button></div>',
                    props: [
                        'show',
                        'title',
                        'message',
                        'confirmLabel',
                        'destructive',
                    ],
                    emits: ['confirm', 'cancel'],
                },
                Teleport: { template: '<div><slot /></div>' },
            },
        },
    });
}

describe('Customers/Index', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders page title', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Customers');
    });

    it('renders customer list', () => {
        const c = makeCustomerWithCount({
            id: 1,
            name: 'ACME Corp',
            phone: '+91 12345',
        });
        const wrapper = mountComponent({
            customers: makePaginatedCustomers([c]),
        });
        expect(wrapper.text()).toContain('ACME Corp');
        expect(wrapper.text()).toContain('+91 12345');
    });

    it('shows primary email', () => {
        const c = makeCustomerWithCount({
            emails: [makeContact({ email: 'billing@acme.test' })],
        });
        const wrapper = mountComponent({
            customers: makePaginatedCustomers([c]),
        });
        expect(wrapper.text()).toContain('billing@acme.test');
    });

    it('shows location summary from primary_location', () => {
        const c = makeCustomerWithCount({
            primary_location: makeLocation({
                city: 'Mumbai',
                state: 'Maharashtra',
            }),
        });
        const wrapper = mountComponent({
            customers: makePaginatedCustomers([c]),
        });
        expect(wrapper.text()).toContain('Mumbai, Maharashtra');
    });

    it('shows empty state when no customers', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('No customers yet');
        expect(wrapper.text()).toContain('Add your first customer');
    });

    it('filters customers by search query', async () => {
        const c1 = makeCustomerWithCount({ id: 1, name: 'Alpha Inc' });
        const c2 = makeCustomerWithCount({ id: 2, name: 'Beta Corp' });
        const wrapper = mountComponent({
            customers: makePaginatedCustomers([c1, c2]),
        });

        const input = wrapper.find('input[type="text"]');
        await input.setValue('Alpha');

        expect(wrapper.text()).toContain('Alpha Inc');
        expect(wrapper.text()).not.toContain('Beta Corp');
    });

    it('shows no results message when search matches nothing', async () => {
        const c = makeCustomerWithCount({ name: 'Alpha Inc' });
        const wrapper = mountComponent({
            customers: makePaginatedCustomers([c]),
        });

        const input = wrapper.find('input[type="text"]');
        await input.setValue('zzzznotfound');

        expect(wrapper.text()).toContain('No results found');
    });

    it('opens create form on Add Customer click', async () => {
        const wrapper = mountComponent();
        const addBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Add Customer');
        await addBtn!.trigger('click');
        expect(wrapper.find('[data-testid="customer-form"]').exists()).toBe(
            true,
        );
    });

    it('opens edit form on Edit click', async () => {
        const c = makeCustomerWithCount({ id: 1, name: 'Edit Me' });
        const wrapper = mountComponent({
            customers: makePaginatedCustomers([c]),
        });

        const editBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Edit');
        await editBtn!.trigger('click');

        expect(wrapper.find('[data-testid="customer-form"]').exists()).toBe(
            true,
        );
    });

    it('opens delete confirmation modal', async () => {
        const c = makeCustomerWithCount({ id: 1 });
        const wrapper = mountComponent({
            customers: makePaginatedCustomers([c]),
        });

        const deleteBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Delete');
        await deleteBtn!.trigger('click');

        expect(wrapper.find('[data-testid="confirm-modal"]').exists()).toBe(
            true,
        );
    });

    it('calls optimistic delete on confirm', async () => {
        const c = makeCustomerWithCount({ id: 42 });
        const wrapper = mountComponent({
            customers: makePaginatedCustomers([c]),
        });

        const deleteBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Delete');
        await deleteBtn!.trigger('click');

        const confirmBtn = wrapper.find('[data-testid="confirm-btn"]');
        await confirmBtn.trigger('click');

        expect(mockOptimistic).toHaveBeenCalled();
        expect(mockOptimisticDelete).toHaveBeenCalledWith(
            '/customers/42',
            expect.any(Object),
        );
    });

    it('shows pagination when multiple pages', () => {
        const wrapper = mountComponent({
            customers: makePaginatedCustomers([], { last_page: 3, total: 45 }),
        });
        expect(wrapper.text()).toContain('Showing page');
    });

    it('hides pagination for single page', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).not.toContain('Showing page');
    });

    it('shows currency for each customer', () => {
        const c = makeCustomerWithCount({ currency: 'USD' });
        const wrapper = mountComponent({
            customers: makePaginatedCustomers([c]),
        });
        expect(wrapper.text()).toContain('USD');
    });

    it('opens Add Location modal', async () => {
        const c = makeCustomerWithCount({ id: 10 });
        const wrapper = mountComponent({
            customers: makePaginatedCustomers([c]),
        });

        const locBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === '+ Location');
        await locBtn!.trigger('click');

        const modal = wrapper.find('[data-testid="location-modal"]');
        expect(modal.exists()).toBe(true);
    });
});
