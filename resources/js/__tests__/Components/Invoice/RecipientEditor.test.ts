import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import RecipientEditor from '@/Components/Invoice/RecipientEditor.vue';
import type { EmailRecipient } from '@/types';

function lastEmitted(wrapper: ReturnType<typeof mount>): EmailRecipient[] {
    const events = wrapper.emitted('update:modelValue');
    return events![events!.length - 1][0] as EmailRecipient[];
}

const contacts = [
    { name: 'Primary', email: 'primary@acme.test' },
    { name: 'Second', email: 'second@acme.test' },
];

describe('Invoice/RecipientEditor', () => {
    it('renders a checkbox row per recipient with contact names', () => {
        const wrapper = mount(RecipientEditor, {
            props: {
                modelValue: [
                    { email: 'primary@acme.test', checked: true },
                    { email: 'second@acme.test', checked: false },
                ],
                contacts,
            },
        });

        const checkboxes = wrapper.findAll('input[type="checkbox"]');
        expect(checkboxes).toHaveLength(2);
        expect((checkboxes[0].element as HTMLInputElement).checked).toBe(true);
        expect((checkboxes[1].element as HTMLInputElement).checked).toBe(false);
        expect(wrapper.text()).toContain('Primary');
        expect(wrapper.text()).toContain('primary@acme.test');
    });

    it('toggles a recipient when its checkbox changes', async () => {
        const wrapper = mount(RecipientEditor, {
            props: {
                modelValue: [{ email: 'primary@acme.test', checked: true }],
                contacts,
            },
        });

        await wrapper.find('input[type="checkbox"]').trigger('change');

        expect(lastEmitted(wrapper)).toEqual([
            { email: 'primary@acme.test', checked: false },
        ]);
    });

    it('shows a delete button only for extras (non-contacts)', () => {
        const wrapper = mount(RecipientEditor, {
            props: {
                modelValue: [
                    { email: 'primary@acme.test', checked: true },
                    { email: 'extra@outside.test', checked: true },
                ],
                contacts,
            },
        });

        expect(
            wrapper
                .find('button[aria-label="Remove primary@acme.test"]')
                .exists(),
        ).toBe(false);
        expect(
            wrapper
                .find('button[aria-label="Remove extra@outside.test"]')
                .exists(),
        ).toBe(true);
    });

    it('removes an extra when its delete button is clicked', async () => {
        const wrapper = mount(RecipientEditor, {
            props: {
                modelValue: [
                    { email: 'primary@acme.test', checked: true },
                    { email: 'extra@outside.test', checked: true },
                ],
                contacts,
            },
        });

        await wrapper
            .find('button[aria-label="Remove extra@outside.test"]')
            .trigger('click');

        expect(lastEmitted(wrapper)).toEqual([
            { email: 'primary@acme.test', checked: true },
        ]);
    });

    it('adds a typed email as a checked extra', async () => {
        const wrapper = mount(RecipientEditor, {
            props: { modelValue: [], contacts },
        });

        const input = wrapper.find('input[type="email"]');
        await input.setValue('new@outside.test');
        await input.trigger('keydown.enter');

        expect(lastEmitted(wrapper)).toEqual([
            { email: 'new@outside.test', checked: true },
        ]);
    });

    it('does not add a duplicate email', async () => {
        const wrapper = mount(RecipientEditor, {
            props: {
                modelValue: [{ email: 'primary@acme.test', checked: true }],
                contacts,
            },
        });

        const input = wrapper.find('input[type="email"]');
        await input.setValue('primary@acme.test');
        await input.trigger('keydown.enter');

        expect(wrapper.emitted('update:modelValue')).toBeFalsy();
    });

    it('adds a typed email on Tab', async () => {
        const wrapper = mount(RecipientEditor, {
            props: { modelValue: [], contacts },
        });

        const input = wrapper.find('input[type="email"]');
        await input.setValue('new@outside.test');
        await input.trigger('keydown.tab');

        expect(lastEmitted(wrapper)).toEqual([
            { email: 'new@outside.test', checked: true },
        ]);
    });

    it('adds a typed email when the field is blurred', async () => {
        const wrapper = mount(RecipientEditor, {
            props: { modelValue: [], contacts },
        });

        const input = wrapper.find('input[type="email"]');
        await input.setValue('new@outside.test');
        await input.trigger('blur');

        expect(lastEmitted(wrapper)).toEqual([
            { email: 'new@outside.test', checked: true },
        ]);
    });

    it('shows an error and does not add an invalid email', async () => {
        const wrapper = mount(RecipientEditor, {
            props: { modelValue: [], contacts },
        });

        const input = wrapper.find('input[type="email"]');
        await input.setValue('not-an-email');
        await input.trigger('keydown.enter');

        expect(wrapper.emitted('update:modelValue')).toBeFalsy();
        expect(wrapper.text()).toContain('Please enter a valid email address');
    });

    it('checks every recipient when "Select all" is clicked', async () => {
        const wrapper = mount(RecipientEditor, {
            props: {
                modelValue: [
                    { email: 'primary@acme.test', checked: true },
                    { email: 'second@acme.test', checked: false },
                ],
                contacts,
            },
        });

        const button = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Select all');
        await button!.trigger('click');

        expect(lastEmitted(wrapper)).toEqual([
            { email: 'primary@acme.test', checked: true },
            { email: 'second@acme.test', checked: true },
        ]);
    });

    it('unchecks every recipient when "Deselect all" is clicked', async () => {
        const wrapper = mount(RecipientEditor, {
            props: {
                modelValue: [
                    { email: 'primary@acme.test', checked: true },
                    { email: 'second@acme.test', checked: true },
                ],
                contacts,
            },
        });

        const button = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Deselect all');
        await button!.trigger('click');

        expect(lastEmitted(wrapper)).toEqual([
            { email: 'primary@acme.test', checked: false },
            { email: 'second@acme.test', checked: false },
        ]);
    });

    it('suggests org contacts not already selected on focus', async () => {
        const wrapper = mount(RecipientEditor, {
            props: {
                modelValue: [{ email: 'primary@acme.test', checked: true }],
                contacts,
                suggestions: [
                    { name: 'Admin', email: 'admin@company.test' },
                    { name: 'Primary', email: 'primary@acme.test' },
                ],
            },
        });

        await wrapper.find('input[type="email"]').trigger('focus');

        // The already-selected contact is filtered out; the org contact remains.
        const suggestionList = wrapper.find('ul.absolute');
        expect(suggestionList.exists()).toBe(true);
        const suggestionButtons = suggestionList.findAll('button');
        expect(suggestionButtons).toHaveLength(1);
        expect(suggestionButtons[0].text()).toContain('admin@company.test');
    });
});
