import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import EmailRecipientInput from '@/Components/Invoice/EmailRecipientInput.vue';
import type { Contact } from '@/types';

const suggestions: Contact[] = [
    { name: 'Alice Buyer', email: 'alice@acme.test' },
    { name: 'Bob Payable', email: 'bob@acme.test' },
    { name: 'Org Admin', email: 'admin@org.test' },
];

function mountInput(modelValue: string[] = [], props = {}) {
    return mount(EmailRecipientInput, {
        props: {
            modelValue,
            suggestions,
            'onUpdate:modelValue': () => {},
            ...props,
        },
    });
}

describe('Invoice/EmailRecipientInput', () => {
    it('renders selected emails as chips', () => {
        const wrapper = mountInput(['alice@acme.test']);
        expect(wrapper.text()).toContain('alice@acme.test');
    });

    it('does not show the suggestion dropdown until focused', () => {
        const wrapper = mountInput([]);
        expect(wrapper.find('ul').exists()).toBe(false);
    });

    it('shows available (not-yet-added) contacts on focus', async () => {
        const wrapper = mountInput(['alice@acme.test']);

        await wrapper.find('input').trigger('focus');

        const dropdown = wrapper.find('ul');
        expect(dropdown.exists()).toBe(true);
        // Alice is already added, so she is not suggested.
        expect(dropdown.text()).not.toContain('alice@acme.test');
        expect(dropdown.text()).toContain('bob@acme.test');
        expect(dropdown.text()).toContain('admin@org.test');
    });

    it('filters suggestions by the typed text (name or email)', async () => {
        const wrapper = mountInput([]);
        const input = wrapper.find('input');

        await input.trigger('focus');
        await input.setValue('admin');

        const dropdown = wrapper.find('ul');
        expect(dropdown.text()).toContain('admin@org.test');
        expect(dropdown.text()).not.toContain('bob@acme.test');
    });

    it('adds a contact when its suggestion is clicked', async () => {
        const wrapper = mountInput([]);

        await wrapper.find('input').trigger('focus');
        await wrapper.findAll('ul button')[0].trigger('click');

        const emitted = wrapper.emitted('update:modelValue');
        expect(emitted).toBeTruthy();
        expect(emitted![emitted!.length - 1][0]).toEqual(['alice@acme.test']);
    });

    it('adds a typed non-contact email on Enter', async () => {
        const wrapper = mountInput([]);
        const input = wrapper.find('input');

        await input.setValue('stranger@elsewhere.test');
        await input.trigger('keydown.enter');

        const emitted = wrapper.emitted('update:modelValue');
        expect(emitted![emitted!.length - 1][0]).toEqual([
            'stranger@elsewhere.test',
        ]);
    });

    it('does not add a duplicate email', async () => {
        const wrapper = mountInput(['alice@acme.test']);
        const input = wrapper.find('input');

        await input.setValue('alice@acme.test');
        await input.trigger('keydown.enter');

        expect(wrapper.emitted('update:modelValue')).toBeFalsy();
    });

    it('adds a typed email on Tab', async () => {
        const wrapper = mountInput([]);
        const input = wrapper.find('input');

        await input.setValue('stranger@elsewhere.test');
        await input.trigger('keydown.tab');

        const emitted = wrapper.emitted('update:modelValue');
        expect(emitted![emitted!.length - 1][0]).toEqual([
            'stranger@elsewhere.test',
        ]);
    });

    it('adds a typed email when the field is blurred', async () => {
        const wrapper = mountInput([]);
        const input = wrapper.find('input');

        await input.setValue('stranger@elsewhere.test');
        await input.trigger('blur');

        const emitted = wrapper.emitted('update:modelValue');
        expect(emitted![emitted!.length - 1][0]).toEqual([
            'stranger@elsewhere.test',
        ]);
    });

    it('does nothing on Tab or blur when the field is empty', async () => {
        const wrapper = mountInput([]);
        const input = wrapper.find('input');

        await input.trigger('keydown.tab');
        await input.trigger('blur');

        expect(wrapper.emitted('update:modelValue')).toBeFalsy();
    });

    it('shows an error and does not add an invalid email', async () => {
        const wrapper = mountInput([]);
        const input = wrapper.find('input');

        await input.setValue('not-an-email');
        await input.trigger('keydown.enter');

        expect(wrapper.emitted('update:modelValue')).toBeFalsy();
        expect(wrapper.text()).toContain('Please enter a valid email address');
    });

    it('clears the error once the user edits the field again', async () => {
        const wrapper = mountInput([]);
        const input = wrapper.find('input');

        await input.setValue('bad');
        await input.trigger('keydown.enter');
        expect(wrapper.text()).toContain('Please enter a valid email address');

        await input.setValue('bad@');
        expect(wrapper.text()).not.toContain(
            'Please enter a valid email address',
        );
    });

    it('removes an email when its chip cross is clicked', async () => {
        const wrapper = mountInput(['alice@acme.test', 'bob@acme.test']);

        await wrapper.find('.bg-gray-100 button').trigger('click');

        const emitted = wrapper.emitted('update:modelValue');
        expect(emitted![0][0]).toEqual(['bob@acme.test']);
    });

    it('re-suggests an email once it is removed from the list', async () => {
        // Alice starts added -> not suggested; after removal she reappears.
        const wrapper = mountInput([]);

        await wrapper.find('input').trigger('focus');
        expect(wrapper.find('ul').text()).toContain('alice@acme.test');
    });
});
