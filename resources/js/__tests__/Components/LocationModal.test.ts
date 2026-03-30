import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { makeLocation } from '../helpers';

const mockPost = vi.fn();
const mockPut = vi.fn();
const mockReset = vi.fn();
const mockClearErrors = vi.fn();

let formData: Record<string, string> = {};

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial: Record<string, string>) => {
        // Store initial values and allow mutation
        Object.assign(formData, initial);
        return new Proxy(formData, {
            get(target, prop) {
                if (prop === 'post') return mockPost;
                if (prop === 'put') return mockPut;
                if (prop === 'reset')
                    return () => {
                        Object.keys(initial).forEach(
                            (k) => (formData[k] = initial[k]),
                        );
                        mockReset();
                    };
                if (prop === 'clearErrors') return mockClearErrors;
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

import LocationModal from '@/Components/LocationModal.vue';

const countries = {
    IN: 'India',
    US: 'United States',
    AE: 'United Arab Emirates',
};

describe('LocationModal', () => {
    beforeEach(() => {
        formData = {};
        vi.clearAllMocks();
    });

    it('does not render when show is false', () => {
        const wrapper = mount(LocationModal, {
            props: { show: false, customerId: 1, countries },
            global: { stubs: { Teleport: true } },
        });
        expect(wrapper.find('[role="dialog"]').exists()).toBe(false);
    });

    it('renders when show is true', () => {
        const wrapper = mount(LocationModal, {
            props: { show: true, customerId: 1, countries },
            global: { stubs: { Teleport: true } },
        });
        expect(wrapper.find('[role="dialog"]').exists()).toBe(true);
    });

    it('shows "Add Location" title for new location', () => {
        const wrapper = mount(LocationModal, {
            props: { show: true, customerId: 1, countries },
            global: { stubs: { Teleport: true } },
        });
        expect(wrapper.text()).toContain('Add Location');
    });

    it('shows "Edit Location" title when location prop provided', () => {
        const wrapper = mount(LocationModal, {
            props: {
                show: true,
                customerId: 1,
                location: makeLocation(),
                countries,
            },
            global: { stubs: { Teleport: true } },
        });
        expect(wrapper.text()).toContain('Edit Location');
    });

    it('renders all address fields', () => {
        const wrapper = mount(LocationModal, {
            props: { show: true, customerId: 1, countries },
            global: { stubs: { Teleport: true } },
        });
        expect(wrapper.text()).toContain('Location Name');
        expect(wrapper.text()).toContain('GSTIN');
        expect(wrapper.text()).toContain('Address Line 1');
        expect(wrapper.text()).toContain('Address Line 2');
        expect(wrapper.text()).toContain('City');
        expect(wrapper.text()).toContain('State');
        expect(wrapper.text()).toContain('Country');
        expect(wrapper.text()).toContain('Postal Code');
    });

    it('shows country dropdown with all countries', () => {
        const wrapper = mount(LocationModal, {
            props: { show: true, customerId: 1, countries },
            global: { stubs: { Teleport: true } },
        });
        const options = wrapper.find('select').findAll('option');
        // "Select country" + 3 countries
        expect(options).toHaveLength(4);
    });

    it('emits close on cancel button click', async () => {
        const wrapper = mount(LocationModal, {
            props: { show: true, customerId: 1, countries },
            global: { stubs: { Teleport: true } },
        });
        const cancelBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Cancel');
        await cancelBtn!.trigger('click');
        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('emits close on backdrop click', async () => {
        const wrapper = mount(LocationModal, {
            props: { show: true, customerId: 1, countries },
            global: { stubs: { Teleport: true } },
        });
        await wrapper.find('.bg-opacity-75').trigger('click');
        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('calls form.post() on submit for new location', async () => {
        const wrapper = mount(LocationModal, {
            props: { show: true, customerId: 5, countries },
            global: { stubs: { Teleport: true } },
        });
        await wrapper.find('form').trigger('submit');
        expect(mockPost).toHaveBeenCalledWith(
            '/customers/5/locations',
            expect.any(Object),
        );
    });

    it('calls form.put() on submit for existing location', async () => {
        const location = makeLocation({ id: 10 });
        const wrapper = mount(LocationModal, {
            props: { show: true, customerId: 5, location, countries },
            global: { stubs: { Teleport: true } },
        });
        await wrapper.find('form').trigger('submit');
        expect(mockPut).toHaveBeenCalledWith(
            '/customers/5/locations/10',
            expect.any(Object),
        );
    });

    it('shows "Add Location" on submit button for new', () => {
        const wrapper = mount(LocationModal, {
            props: { show: true, customerId: 1, countries },
            global: { stubs: { Teleport: true } },
        });
        expect(wrapper.find('button[type="submit"]').text()).toContain(
            'Add Location',
        );
    });

    it('shows "Update Location" on submit button for edit', () => {
        const wrapper = mount(LocationModal, {
            props: {
                show: true,
                customerId: 1,
                location: makeLocation(),
                countries,
            },
            global: { stubs: { Teleport: true } },
        });
        expect(wrapper.find('button[type="submit"]').text()).toContain(
            'Update Location',
        );
    });
});
