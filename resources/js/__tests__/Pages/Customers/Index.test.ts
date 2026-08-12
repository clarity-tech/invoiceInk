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
                    role: 'owner',
                    permissions: ['*'],
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
    countries: { IN: '🇮🇳 India', US: '🇺🇸 United States' },
    countryNames: { IN: 'India', US: 'United States' },
    taxLabels: { IN: 'GSTIN', US: 'EIN' },
    indianStates: {},
    gstTreatments: {},
    filters: { search: '' },
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
            phone: '+919812345678',
            formatted_phone: '+91 98123 45678',
        });
        const wrapper = mountComponent({
            customers: makePaginatedCustomers([c]),
        });
        expect(wrapper.text()).toContain('ACME Corp');
        expect(wrapper.text()).toContain('+91 98123 45678');
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

    // Search runs on the server so it spans every page, not just the ten rows
    // the current page holds. The component's job is to issue one debounced
    // request, not to filter rows itself.
    it('issues a debounced server-side search request', async () => {
        vi.useFakeTimers();

        try {
            const c1 = makeCustomerWithCount({ id: 1, name: 'Alpha Inc' });
            const wrapper = mountComponent({
                customers: makePaginatedCustomers([c1]),
            });

            await wrapper.find('input[type="text"]').setValue('Alpha');

            // Still inside the debounce window: nothing sent yet.
            expect(mockRouterGet).not.toHaveBeenCalled();

            vi.advanceTimersByTime(300);

            expect(mockRouterGet).toHaveBeenCalledWith(
                '/customers',
                { search: 'Alpha' },
                expect.objectContaining({
                    preserveState: true,
                    replace: true,
                }),
            );
        } finally {
            vi.useRealTimers();
        }
    });

    it('collapses a burst of keystrokes into a single request', async () => {
        vi.useFakeTimers();

        try {
            const wrapper = mountComponent();
            const input = wrapper.find('input[type="text"]');

            await input.setValue('A');
            await input.setValue('Al');
            await input.setValue('Alp');

            vi.advanceTimersByTime(300);

            expect(mockRouterGet).toHaveBeenCalledTimes(1);
            expect(mockRouterGet).toHaveBeenCalledWith(
                '/customers',
                { search: 'Alp' },
                expect.anything(),
            );
        } finally {
            vi.useRealTimers();
        }
    });

    it('shows no results message when a search returns nothing', () => {
        // The server has already filtered and come back empty; the component
        // distinguishes "no matches" from "no customers yet" via filters.search.
        const wrapper = mountComponent({
            customers: makePaginatedCustomers([]),
            filters: { search: 'zzzznotfound' },
        });

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

    it('opens Add Location modal from the expanded locations panel', async () => {
        const c = makeCustomerWithCount({ id: 10 });
        const wrapper = mountComponent({
            customers: makePaginatedCustomers([c]),
        });

        // The disclosure is the only entry point to the location manager, and it
        // renders even at zero locations.
        const disclosure = wrapper
            .findAll('button')
            .find((b) => b.text().startsWith('Locations ('));
        expect(disclosure).toBeDefined();
        await disclosure!.trigger('click');

        const addBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === '+ Add location');
        expect(addBtn).toBeDefined();
        await addBtn!.trigger('click');

        expect(wrapper.find('[data-testid="location-modal"]').exists()).toBe(
            true,
        );
    });

    it('labels each location tax number by its country', async () => {
        const c = {
            ...makeCustomerWithCount({
                id: 7,
                locations: [
                    makeLocation({
                        id: 1,
                        name: 'US Office',
                        country: 'US',
                        tax_id: '12-3456789',
                    }),
                ],
            }),
            locations_count: 1,
        };
        const wrapper = mountComponent({
            customers: makePaginatedCustomers([c]),
        });

        // Expand the row to reveal the locations list (chevron is the first button).
        await wrapper.find('tbody button').trigger('click');

        expect(wrapper.text()).toContain('EIN: 12-3456789');
        expect(wrapper.text()).not.toContain('GSTIN: 12-3456789');
    });

    it('renders a location address without repeating a state that echoes the city', async () => {
        const dubaiOffice = makeLocation({
            id: 1,
            name: 'SANDLINE Pharmacy LLC',
            address_line_1: 'Unit 4, Palm Court, Al Barsha',
            city: 'DUBAI',
            state: 'Dubai',
            country: 'AE',
        });
        const c = {
            ...makeCustomerWithCount({
                id: 8,
                primary_location: dubaiOffice,
                locations: [dubaiOffice],
            }),
            locations_count: 1,
        };
        const wrapper = mountComponent({
            customers: makePaginatedCustomers([c]),
            countryNames: { AE: 'United Arab Emirates' },
        });

        // Summary column falls back to the country when the state adds nothing.
        expect(wrapper.text()).toContain('DUBAI, United Arab Emirates');
        expect(wrapper.text()).not.toContain('DUBAI, Dubai');

        // Expand the row to reveal the locations list (chevron is the first button).
        await wrapper.find('tbody button').trigger('click');

        expect(wrapper.text()).toContain(
            'Unit 4, Palm Court, Al Barsha, DUBAI, United Arab Emirates',
        );
    });
});
