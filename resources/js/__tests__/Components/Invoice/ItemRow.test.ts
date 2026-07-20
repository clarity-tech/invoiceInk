import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import ItemRow from '@/Components/Invoice/ItemRow.vue';

const defaultProps = {
    item: {
        description: 'Consulting',
        sac_code: null,
        quantity: 2,
        unit_price: 50000,
        tax_rate: 1800,
    },
    index: 0,
    currency: 'USD' as const,
    taxOptions: [],
    canRemove: true,
    errors: {},
};

describe('ItemRow', () => {
    it('renders item description input', () => {
        const wrapper = mount(ItemRow, { props: defaultProps });
        const input = wrapper.find('input[type="text"]');
        expect((input.element as HTMLInputElement).value).toBe('Consulting');
    });

    it('displays formatted line total', () => {
        const wrapper = mount(ItemRow, { props: defaultProps });
        // 2 × 50000 = 100000 + 18% tax = 118000 → $1,180.00
        expect(wrapper.text()).toContain('$1,180.00');
    });

    it('shows remove button when canRemove is true', () => {
        const wrapper = mount(ItemRow, { props: defaultProps });
        expect(
            wrapper.find('button[aria-label="Remove item 1"]').exists(),
        ).toBe(true);
    });

    it('hides remove button when canRemove is false', () => {
        const wrapper = mount(ItemRow, {
            props: { ...defaultProps, canRemove: false },
        });
        expect(
            wrapper.find('button[aria-label="Remove item 1"]').exists(),
        ).toBe(false);
    });

    it('emits remove when remove button clicked', async () => {
        const wrapper = mount(ItemRow, { props: defaultProps });
        await wrapper
            .find('button[aria-label="Remove item 1"]')
            .trigger('click');
        expect(wrapper.emitted('remove')).toHaveLength(1);
    });

    it('emits update when description changes', async () => {
        const wrapper = mount(ItemRow, { props: defaultProps });
        const input = wrapper.find('input[type="text"]');
        await input.setValue('New description');
        expect(wrapper.emitted('update')).toBeTruthy();
        const emitted = wrapper.emitted('update')!;
        expect((emitted[emitted.length - 1][0] as any).description).toBe(
            'New description',
        );
    });

    it('displays validation errors', () => {
        const wrapper = mount(ItemRow, {
            props: {
                ...defaultProps,
                errors: { 'items.0.description': 'Required' },
            },
        });
        expect(wrapper.text()).toContain('Required');
    });

    it('shows SAC code badge when provided', () => {
        const wrapper = mount(ItemRow, {
            props: {
                ...defaultProps,
                item: { ...defaultProps.item, sac_code: '998311' },
            },
        });
        expect(wrapper.text()).toContain('SAC: 998311');
    });
});

describe('ItemRow tax control', () => {
    const taxOptions = [
        { key: 'gst-18', name: 'GST 18%', rate: 1800 },
        { key: 'gst-5', name: 'GST 5%', rate: 500 },
    ] as any;

    it('renders an option per tax rate plus a custom option', () => {
        const wrapper = mount(ItemRow, {
            props: { ...defaultProps, taxOptions },
        });
        const options = wrapper.findAll('select option').map((o) => o.text());
        expect(options).toContain('GST 18%');
        expect(options).toContain('GST 5%');
        expect(options.some((o) => o.includes('Custom'))).toBe(true);
    });

    it('orders tax options by rate ascending', () => {
        const wrapper = mount(ItemRow, {
            props: { ...defaultProps, taxOptions },
        });
        const rateOptions = wrapper
            .findAll('select option')
            .map((o) => o.text())
            .filter((t) => t.startsWith('GST'));
        expect(rateOptions).toEqual(['GST 5%', 'GST 18%']);
    });

    it('preselects the option matching the item tax rate', () => {
        const wrapper = mount(ItemRow, {
            props: {
                ...defaultProps,
                item: { ...defaultProps.item, tax_rate: 1800 },
                taxOptions,
            },
        });
        expect(
            (wrapper.find('select').element as HTMLSelectElement).value,
        ).toBe('gst-18');
        // No custom input while a predefined option is selected.
        expect(
            wrapper.find('input[aria-label="Custom tax rate"]').exists(),
        ).toBe(false);
    });

    it('emits update with the option rate when a tax option is selected', async () => {
        const wrapper = mount(ItemRow, {
            props: {
                ...defaultProps,
                item: { ...defaultProps.item, tax_rate: 0 },
                taxOptions,
            },
        });
        await wrapper.find('select').setValue('gst-5');
        const emitted = wrapper.emitted('update')!;
        expect((emitted[emitted.length - 1][0] as any).tax_rate).toBe(500);
    });

    it('reveals a custom input and emits the manually entered rate', async () => {
        const wrapper = mount(ItemRow, {
            props: {
                ...defaultProps,
                item: { ...defaultProps.item, tax_rate: 0 },
                taxOptions,
            },
        });
        await wrapper.find('select').setValue('custom');
        const input = wrapper.find('input[aria-label="Custom tax rate"]');
        expect(input.exists()).toBe(true);
        await input.setValue('7.5');
        const emitted = wrapper.emitted('update')!;
        expect((emitted[emitted.length - 1][0] as any).tax_rate).toBe(750);
    });

    it('falls back to the custom input when the rate matches no option', () => {
        const wrapper = mount(ItemRow, {
            props: {
                ...defaultProps,
                item: { ...defaultProps.item, tax_rate: 1234 },
                taxOptions,
            },
        });
        expect(
            (wrapper.find('select').element as HTMLSelectElement).value,
        ).toBe('custom');
        expect(
            wrapper.find('input[aria-label="Custom tax rate"]').exists(),
        ).toBe(true);
    });
});
