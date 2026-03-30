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
    taxTemplates: [],
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
