import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import CustomersShow from '@/Pages/Customers/Show.vue';
import { makeCustomer, makeLocation } from '../../helpers';

const {
    mockPut,
    mockReset,
    mockClearErrors,
    mockRouterPost,
    mockRouterDelete,
} = vi.hoisted(() => ({
    mockPut: vi.fn(),
    mockReset: vi.fn(),
    mockClearErrors: vi.fn(),
    mockRouterPost: vi.fn(),
    mockRouterDelete: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial: Record<string, unknown>) => {
        const data = { ...initial };
        return new Proxy(data, {
            get(target, prop) {
                if (prop === 'put') return mockPut;
                if (prop === 'post') return vi.fn();
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
        get: vi.fn(),
        post: mockRouterPost,
        delete: mockRouterDelete,
        reload: vi.fn(),
    },
    usePage: () => ({
        props: {
            auth: {
                user: { id: 1, name: 'Test', email: 'test@test.test' },
                currentTeam: { role: 'owner', permissions: ['*'] },
            },
            flash: {},
        },
    }),
}));

function defaultProps() {
    return {
        customer: makeCustomer(),
        currencies: { INR: 'Indian Rupee', USD: 'US Dollar' },
        countries: { IN: '🇮🇳 India', US: '🇺🇸 United States', AE: '🇦🇪 UAE' },
        countryNames: {
            IN: 'India',
            US: 'United States',
            AE: 'United Arab Emirates',
        },
        taxLabels: { IN: 'GSTIN', US: 'EIN', AE: 'TRN' },
        indianStates: {},
        gstTreatments: {},
    };
}

function mountComponent(propsOverride: Record<string, unknown> = {}) {
    return mount(CustomersShow, {
        props: { ...defaultProps(), ...propsOverride },
        global: {
            stubs: {
                AppLayout: {
                    template: '<div><slot name="header" /><slot /></div>',
                },
                CustomerForm: {
                    template: '<div data-testid="customer-form" />',
                },
                LocationModal: {
                    template:
                        '<div v-if="show" data-testid="location-modal" />',
                    props: ['show'],
                },
                ConfirmationModal: { template: '<div />' },
                Teleport: true,
            },
        },
    });
}

describe('Customers/Show', () => {
    // Arriving straight from customer creation, the "add at least one location"
    // prompt should be the modal itself, not something to go and find.
    it('opens the location modal on arrival from customer creation', async () => {
        const wrapper = mountComponent({ promptLocation: true });
        // The modal is opened in onMounted, so the DOM needs a tick to catch up.
        await wrapper.vm.$nextTick();

        expect(wrapper.find('[data-testid="location-modal"]').exists()).toBe(
            true,
        );
    });

    it('leaves the location modal closed on an ordinary visit', () => {
        const wrapper = mountComponent();

        expect(wrapper.find('[data-testid="location-modal"]').exists()).toBe(
            false,
        );
    });

    it('labels each location tax number by its country', () => {
        const wrapper = mountComponent({
            customer: {
                ...makeCustomer(),
                locations: [
                    makeLocation({
                        id: 1,
                        name: 'US Office',
                        country: 'US',
                        tax_id: '12-3456789',
                    }),
                ],
            },
        });

        expect(wrapper.text()).toContain('EIN: 12-3456789');
        expect(wrapper.text()).not.toContain('GSTIN: 12-3456789');
    });

    it('renders a location address without repeating a state that echoes the city', () => {
        const dubaiOffice = makeLocation({
            id: 1,
            name: 'SANDLINE Pharmacy LLC',
            address_line_1: 'Unit 4, Palm Court, Al Barsha',
            city: 'DUBAI',
            state: 'Dubai',
            country: 'AE',
        });

        const wrapper = mountComponent({
            customer: {
                ...makeCustomer(),
                primary_location: dubaiOffice,
                primary_location_id: dubaiOffice.id,
                locations: [dubaiOffice],
            },
        });

        // The summary falls back to the country when the state adds nothing.
        expect(wrapper.text()).toContain('DUBAI, United Arab Emirates');
        expect(wrapper.text()).not.toContain('DUBAI, Dubai');

        expect(wrapper.text()).toContain(
            'Unit 4, Palm Court, Al Barsha, DUBAI, United Arab Emirates',
        );
    });

    it('opens the location modal from the add button', async () => {
        const wrapper = mountComponent();

        expect(wrapper.find('[data-testid="location-modal"]').exists()).toBe(
            false,
        );

        const addBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === '+ Add location');
        expect(addBtn).toBeDefined();
        await addBtn!.trigger('click');

        expect(wrapper.find('[data-testid="location-modal"]').exists()).toBe(
            true,
        );
    });

    it('shows an empty state when the customer has no locations', () => {
        const wrapper = mountComponent({
            customer: { ...makeCustomer(), locations: [] },
        });

        expect(wrapper.text()).toContain('Locations (0)');
        expect(wrapper.text()).toContain('No locations yet');
    });

    it('marks the primary location and offers promotion for the others', async () => {
        const head = makeLocation({ id: 1, name: 'Head Office' });
        const branch = makeLocation({ id: 2, name: 'Branch Office' });

        const wrapper = mountComponent({
            customer: {
                ...makeCustomer(),
                primary_location: head,
                primary_location_id: head.id,
                locations: [head, branch],
            },
        });

        expect(wrapper.text()).toContain('Primary');

        // Only the non-primary location offers "Set Primary".
        const setPrimaryButtons = wrapper
            .findAll('button')
            .filter((b) => b.text() === 'Set Primary');
        expect(setPrimaryButtons).toHaveLength(1);

        await setPrimaryButtons[0].trigger('click');
        expect(mockRouterPost).toHaveBeenCalledWith(
            expect.stringContaining('/primary-location/2'),
            {},
            expect.anything(),
        );
    });

    it('keeps archived locations collapsed until asked for', async () => {
        const wrapper = mountComponent({
            customer: {
                ...makeCustomer(),
                archived_locations: [
                    makeLocation({ id: 9, name: 'Old Warehouse' }),
                ],
            },
        });

        expect(wrapper.text()).toContain('Archived (1)');
        expect(wrapper.text()).not.toContain('Old Warehouse');

        const toggle = wrapper
            .findAll('button')
            .find((b) => b.text().includes('Archived ('));
        await toggle!.trigger('click');

        expect(wrapper.text()).toContain('Old Warehouse');
    });
});
