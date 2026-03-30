import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import LocationFields from '@/Components/LocationFields.vue';

const defaultLocation = {
    name: 'Head Office',
    address_line_1: '123 Main St',
    address_line_2: 'Suite 100',
    city: 'Mumbai',
    state: 'Maharashtra',
    country: 'IN',
    postal_code: '400001',
    gstin: '27AABCU9603R1ZM',
};

function mountComponent(location = defaultLocation) {
    return mount(LocationFields, {
        props: { location },
    });
}

describe('LocationFields', () => {
    it('renders all address field labels', () => {
        const wrapper = mountComponent();
        const text = wrapper.text();
        expect(text).toContain('Address Line 1');
        expect(text).toContain('Address Line 2');
        expect(text).toContain('City');
        expect(text).toContain('State');
        expect(text).toContain('Country');
        expect(text).toContain('Postal Code');
        expect(text).toContain('GSTIN');
        expect(text).toContain('Location Name');
    });

    it('renders input values from location prop', () => {
        const wrapper = mountComponent();
        const inputs = wrapper.findAll('input');
        const values = inputs.map((i) => (i.element as HTMLInputElement).value);
        expect(values).toContain('123 Main St');
        expect(values).toContain('Suite 100');
        expect(values).toContain('Mumbai');
        expect(values).toContain('Maharashtra');
        expect(values).toContain('IN');
        expect(values).toContain('400001');
        expect(values).toContain('27AABCU9603R1ZM');
        expect(values).toContain('Head Office');
    });

    it('emits update:location when address_line_1 changes', async () => {
        const wrapper = mountComponent();
        const inputs = wrapper.findAll('input');
        // address_line_1 is the first input
        await inputs[0].setValue('456 New St');
        const emitted = wrapper.emitted('update:location');
        expect(emitted).toBeTruthy();
        expect(emitted![emitted!.length - 1][0]).toMatchObject({
            address_line_1: '456 New St',
        });
    });

    it('emits update:location when city changes', async () => {
        const wrapper = mountComponent();
        const inputs = wrapper.findAll('input');
        // city is the 3rd input (index 2)
        await inputs[2].setValue('Delhi');
        const emitted = wrapper.emitted('update:location');
        expect(emitted).toBeTruthy();
        expect(emitted![emitted!.length - 1][0]).toMatchObject({
            city: 'Delhi',
        });
    });

    it('emits update:location when gstin changes', async () => {
        const wrapper = mountComponent();
        const inputs = wrapper.findAll('input');
        // gstin is the 7th input (index 6)
        await inputs[6].setValue('NEW_GSTIN');
        const emitted = wrapper.emitted('update:location');
        expect(emitted).toBeTruthy();
        expect(emitted![emitted!.length - 1][0]).toMatchObject({
            gstin: 'NEW_GSTIN',
        });
    });

    it('renders with empty/partial location', () => {
        const wrapper = mountComponent({ city: 'Test' });
        expect(wrapper.text()).toContain('City');
        const inputs = wrapper.findAll('input');
        const cityInput = inputs[2];
        expect((cityInput.element as HTMLInputElement).value).toBe('Test');
    });

    it('preserves other fields when one field is updated', async () => {
        const wrapper = mountComponent();
        const inputs = wrapper.findAll('input');
        await inputs[3].setValue('Karnataka'); // state
        const emitted = wrapper.emitted('update:location');
        const lastEmit = emitted![emitted!.length - 1][0] as Record<
            string,
            unknown
        >;
        expect(lastEmit.state).toBe('Karnataka');
        expect(lastEmit.city).toBe('Mumbai');
        expect(lastEmit.address_line_1).toBe('123 Main St');
    });

    it('has placeholder on location name field', () => {
        const wrapper = mountComponent();
        const inputs = wrapper.findAll('input');
        const nameInput = inputs[7]; // last input
        expect(nameInput.attributes('placeholder')).toBe('e.g., Head Office');
    });

    it('renders 8 input fields total', () => {
        const wrapper = mountComponent();
        expect(wrapper.findAll('input').length).toBe(8);
    });
});
