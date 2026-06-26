import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import PhoneInput from '@/Components/PhoneInput.vue';

describe('PhoneInput', () => {
    it('renders a country selector and a national number input', () => {
        const wrapper = mount(PhoneInput, {
            props: { id: 'phone', modelValue: '', country: '' },
        });

        expect(wrapper.find('select[aria-label="Country code"]').exists()).toBe(
            true,
        );
        expect(wrapper.find('#phone').exists()).toBe(true);
    });

    it('emits an E.164 number when a national number is entered', async () => {
        const wrapper = mount(PhoneInput, {
            props: { id: 'phone', modelValue: '', country: 'US' },
        });

        await wrapper.find('#phone').setValue('4155552671');

        const emitted = wrapper.emitted('update:modelValue');
        expect(emitted).toBeTruthy();
        expect(emitted!.at(-1)![0]).toBe('+14155552671');
    });

    it('emits the selected country when the dropdown changes', async () => {
        const wrapper = mount(PhoneInput, {
            props: { id: 'phone', modelValue: '', country: 'US' },
        });

        await wrapper.find('select[aria-label="Country code"]').setValue('GB');

        const emitted = wrapper.emitted('update:country');
        expect(emitted).toBeTruthy();
        expect(emitted!.at(-1)![0]).toBe('GB');
    });

    it('hydrates the national field and country from an existing E.164 value', async () => {
        const wrapper = mount(PhoneInput, {
            props: { id: 'phone', modelValue: '+442071838750', country: '' },
        });

        await wrapper.vm.$nextTick();

        const input = wrapper.find('#phone').element as HTMLInputElement;
        expect(input.value.replace(/\D/g, '')).toContain('2071838750');

        const emittedCountry = wrapper.emitted('update:country');
        expect(emittedCountry?.some((e) => e[0] === 'GB')).toBe(true);
    });

    it('clears the model when the national number is emptied', async () => {
        const wrapper = mount(PhoneInput, {
            props: { id: 'phone', modelValue: '+14155552671', country: 'US' },
        });

        await wrapper.find('#phone').setValue('');

        const emitted = wrapper.emitted('update:modelValue');
        expect(emitted!.at(-1)![0]).toBe('');
    });
});
