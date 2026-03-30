import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import MoneyDisplay from '@/Components/MoneyDisplay.vue';

describe('MoneyDisplay', () => {
    it('renders formatted amount', () => {
        const wrapper = mount(MoneyDisplay, {
            props: { amount: 10050, currency: 'USD' },
        });
        expect(wrapper.text()).toBe('$100.50');
    });

    it('renders zero amount', () => {
        const wrapper = mount(MoneyDisplay, {
            props: { amount: 0, currency: 'INR' },
        });
        expect(wrapper.text()).toBe('₹0.00');
    });

    it('renders JPY without decimals', () => {
        const wrapper = mount(MoneyDisplay, {
            props: { amount: 5000, currency: 'JPY' },
        });
        expect(wrapper.text()).toBe('¥5,000');
    });
});
