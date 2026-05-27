import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { nextTick } from 'vue';
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

const taxLabels = {
    IN: 'GSTIN',
    US: 'EIN',
    AE: 'TRN',
};

const baseProps = { countries, taxLabels };

type ModalProps = InstanceType<typeof LocationModal>['$props'];

// The component defers its body Teleport until onMounted() flips `isMounted`
// to true (to avoid SSR hydration crashes). In tests we have to wait one tick
// after mount so the gated content is rendered.
async function mountModal(props: ModalProps) {
    const wrapper = mount(LocationModal, {
        props,
        global: { stubs: { Teleport: true } },
    });
    await nextTick();
    return wrapper;
}

// The form is seeded from `props.location` by a watcher on `props.show` that
// only fires on transition — so to test render with a preloaded location we
// open the modal in two steps: mount closed, then toggle show=true.
async function openModalWithLocation(extraProps: Omit<ModalProps, 'show'>) {
    const wrapper = mount(LocationModal, {
        props: { show: false, ...extraProps },
        global: { stubs: { Teleport: true } },
    });
    await wrapper.setProps({ show: true });
    await nextTick();
    return wrapper;
}

describe('LocationModal', () => {
    beforeEach(() => {
        formData = {};
        vi.clearAllMocks();
    });

    it('does not render when show is false', async () => {
        const wrapper = await mountModal({
            show: false,
            customerId: 1,
            ...baseProps,
        });
        expect(wrapper.find('[role="dialog"]').exists()).toBe(false);
    });

    it('renders when show is true', async () => {
        const wrapper = await mountModal({
            show: true,
            customerId: 1,
            ...baseProps,
        });
        expect(wrapper.find('[role="dialog"]').exists()).toBe(true);
    });

    it('shows "Add Location" title for new location', async () => {
        const wrapper = await mountModal({
            show: true,
            customerId: 1,
            ...baseProps,
        });
        expect(wrapper.text()).toContain('Add Location');
    });

    it('shows "Edit Location" title when location prop provided', async () => {
        const wrapper = await mountModal({
            show: true,
            customerId: 1,
            location: makeLocation(),
            ...baseProps,
        });
        expect(wrapper.text()).toContain('Edit Location');
    });

    it('renders all address fields', async () => {
        const wrapper = await mountModal({
            show: true,
            customerId: 1,
            ...baseProps,
        });
        expect(wrapper.text()).toContain('Location Name');
        expect(wrapper.text()).toContain('Address Line 1');
        expect(wrapper.text()).toContain('Address Line 2');
        expect(wrapper.text()).toContain('City');
        expect(wrapper.text()).toContain('State');
        expect(wrapper.text()).toContain('Country');
        expect(wrapper.text()).toContain('Postal Code');
    });

    it('shows GSTIN label when country is India', async () => {
        const wrapper = await openModalWithLocation({
            customerId: 1,
            location: makeLocation({ country: 'IN' }),
            ...baseProps,
        });
        expect(wrapper.text()).toContain('GSTIN');
    });

    it('shows country-specific tax label for non-Indian country', async () => {
        const wrapper = await openModalWithLocation({
            customerId: 1,
            location: makeLocation({ country: 'AE' }),
            ...baseProps,
        });
        expect(wrapper.text()).toContain('TRN');
        expect(wrapper.text()).not.toContain('GSTIN');
    });

    it('falls back to generic "Tax ID" label when country is missing', async () => {
        const wrapper = await mountModal({
            show: true,
            customerId: 1,
            ...baseProps,
        });
        expect(wrapper.text()).toContain('Tax ID');
    });

    it('shows country dropdown with all countries', async () => {
        const wrapper = await mountModal({
            show: true,
            customerId: 1,
            ...baseProps,
        });
        const options = wrapper.find('select').findAll('option');
        // "Select country" + 3 countries
        expect(options).toHaveLength(4);
    });

    it('emits close on cancel button click', async () => {
        const wrapper = await mountModal({
            show: true,
            customerId: 1,
            ...baseProps,
        });
        const cancelBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Cancel');
        await cancelBtn!.trigger('click');
        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('emits close on backdrop click', async () => {
        const wrapper = await mountModal({
            show: true,
            customerId: 1,
            ...baseProps,
        });
        await wrapper.find('.bg-opacity-75').trigger('click');
        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('calls form.post() on submit for new location', async () => {
        const wrapper = await mountModal({
            show: true,
            customerId: 5,
            ...baseProps,
        });
        await wrapper.find('form').trigger('submit');
        expect(mockPost).toHaveBeenCalledWith(
            '/customers/5/locations',
            expect.any(Object),
        );
    });

    it('calls form.put() on submit for existing location', async () => {
        const location = makeLocation({ id: 10 });
        const wrapper = await mountModal({
            show: true,
            customerId: 5,
            location,
            ...baseProps,
        });
        await wrapper.find('form').trigger('submit');
        expect(mockPut).toHaveBeenCalledWith(
            '/customers/5/locations/10',
            expect.any(Object),
        );
    });

    it('shows "Add Location" on submit button for new', async () => {
        const wrapper = await mountModal({
            show: true,
            customerId: 1,
            ...baseProps,
        });
        expect(wrapper.find('button[type="submit"]').text()).toContain(
            'Add Location',
        );
    });

    it('shows "Update Location" on submit button for edit', async () => {
        const wrapper = await mountModal({
            show: true,
            customerId: 1,
            location: makeLocation(),
            ...baseProps,
        });
        expect(wrapper.find('button[type="submit"]').text()).toContain(
            'Update Location',
        );
    });
});
