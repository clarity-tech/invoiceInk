import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { makeCustomer, makeLocation, makeContact } from '../helpers';

const {
    mockPost,
    mockPut,
    mockOptimisticDelete,
    mockOptimistic,
    mockReset,
    mockClearErrors,
    mockRouterGet,
} = vi.hoisted(() => {
    const mockOptimisticDelete = vi.fn();
    return {
        mockPost: vi.fn(),
        mockPut: vi.fn(),
        mockOptimisticDelete,
        mockOptimistic: vi
            .fn()
            .mockReturnValue({ delete: mockOptimisticDelete }),
        mockReset: vi.fn(),
        mockClearErrors: vi.fn(),
        mockRouterGet: vi.fn(),
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
        post: vi.fn(),
        delete: vi.fn(),
        reload: vi.fn(),
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
                    template:
                        '<div data-testid="customer-form"><button data-testid="form-submit" @click="$emit(\'submit\')">Submit</button><button data-testid="form-cancel" @click="$emit(\'cancel\')">Cancel</button></div>',
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
                        '<div v-if="show" data-testid="confirm-modal"><button data-testid="confirm-btn" @click="$emit(\'confirm\')">Confirm</button><button data-testid="cancel-btn" @click="$emit(\'cancel\')">Cancel Delete</button></div>',
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

describe('CustomerManagementFlow', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('opens create modal and shows form', async () => {
        const wrapper = mountComponent();
        const addBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Add Customer');
        await addBtn!.trigger('click');
        expect(wrapper.find('[data-testid="customer-form"]').exists()).toBe(
            true,
        );
    });

    it('opens edit modal with customer data', async () => {
        const c = makeCustomerWithCount({ id: 1, name: 'Edit Me Corp' });
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

    it('closes form on cancel emit from CustomerForm', async () => {
        const wrapper = mountComponent();
        // Open form
        const addBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Add Customer');
        await addBtn!.trigger('click');
        expect(wrapper.find('[data-testid="customer-form"]').exists()).toBe(
            true,
        );

        // Cancel
        const cancelBtn = wrapper.find('[data-testid="form-cancel"]');
        await cancelBtn.trigger('click');

        // Form should be hidden
        expect(wrapper.find('[data-testid="customer-form"]').exists()).toBe(
            false,
        );
    });

    it('delete flow: click delete -> shows confirmation -> confirm -> optimistic delete', async () => {
        const c = makeCustomerWithCount({ id: 99, name: 'Delete Me' });
        const wrapper = mountComponent({
            customers: makePaginatedCustomers([c]),
        });

        // Step 1: Click delete
        const deleteBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Delete');
        await deleteBtn!.trigger('click');

        // Step 2: Confirmation modal appears
        expect(wrapper.find('[data-testid="confirm-modal"]').exists()).toBe(
            true,
        );

        // Step 3: Confirm
        const confirmBtn = wrapper.find('[data-testid="confirm-btn"]');
        await confirmBtn.trigger('click');

        // Step 4: Optimistic delete called
        expect(mockOptimistic).toHaveBeenCalled();
        expect(mockOptimisticDelete).toHaveBeenCalledWith(
            '/customers/99',
            expect.any(Object),
        );
    });

    it('shows customer details in list: name, email, location', () => {
        const c = makeCustomerWithCount({
            id: 1,
            name: 'Full Details Corp',
            emails: [makeContact({ email: 'billing@full.test' })],
            primary_location: makeLocation({
                city: 'Bangalore',
                state: 'Karnataka',
            }),
        });
        const wrapper = mountComponent({
            customers: makePaginatedCustomers([c]),
        });
        expect(wrapper.text()).toContain('Full Details Corp');
        expect(wrapper.text()).toContain('billing@full.test');
        expect(wrapper.text()).toContain('Bangalore, Karnataka');
    });

    it('searches customers on the server rather than filtering the page', async () => {
        vi.useFakeTimers();

        try {
            const c1 = makeCustomerWithCount({ id: 1, name: 'Alpha Inc' });
            const c2 = makeCustomerWithCount({ id: 2, name: 'Beta Corp' });
            const wrapper = mountComponent({
                customers: makePaginatedCustomers([c1, c2]),
            });

            await wrapper.find('input[type="text"]').setValue('Alpha');
            vi.advanceTimersByTime(300);

            // Rows are whatever the server sent; the component does not filter
            // them locally, which is what made search miss customers on other pages.
            expect(mockRouterGet).toHaveBeenCalledWith(
                '/customers',
                { search: 'Alpha' },
                expect.objectContaining({ preserveState: true }),
            );
            expect(wrapper.text()).toContain('Beta Corp');
        } finally {
            vi.useRealTimers();
        }
    });

    it('shows empty state with no customers', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('No customers yet');
    });
});
