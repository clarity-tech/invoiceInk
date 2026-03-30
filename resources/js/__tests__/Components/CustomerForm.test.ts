import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import CustomerForm from '@/Components/CustomerForm.vue';
import { makeMockForm } from '../helpers';

function makeFormData(overrides = {}) {
    return makeMockForm({
        name: '',
        phone: '',
        currency: 'INR',
        contacts: [{ name: '', email: '' }],
        ...overrides,
    });
}

const defaultProps = {
    form: makeFormData(),
    currencies: {
        INR: 'INR - Indian Rupee',
        USD: 'USD - US Dollar',
        AED: 'AED - UAE Dirham',
    },
    countries: { IN: 'India', US: 'United States', AE: 'United Arab Emirates' },
    isEditing: false,
};

describe('CustomerForm', () => {
    it('renders all fields', () => {
        const wrapper = mount(CustomerForm, { props: defaultProps });
        expect(wrapper.find('#customer-name').exists()).toBe(true);
        expect(wrapper.find('#customer-phone').exists()).toBe(true);
        expect(wrapper.find('#customer-currency').exists()).toBe(true);
    });

    it('shows "Create Customer" button when not editing', () => {
        const wrapper = mount(CustomerForm, { props: defaultProps });
        expect(wrapper.text()).toContain('Create Customer');
    });

    it('shows "Update Customer" button when editing', () => {
        const wrapper = mount(CustomerForm, {
            props: { ...defaultProps, isEditing: true },
        });
        expect(wrapper.text()).toContain('Update Customer');
    });

    it('emits submit on form submission', async () => {
        const wrapper = mount(CustomerForm, { props: defaultProps });
        await wrapper.find('form').trigger('submit');
        expect(wrapper.emitted('submit')).toHaveLength(1);
    });

    it('emits cancel on cancel button click', async () => {
        const wrapper = mount(CustomerForm, { props: defaultProps });
        const cancelBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Cancel');
        await cancelBtn!.trigger('click');
        expect(wrapper.emitted('cancel')).toHaveLength(1);
    });

    it('adds new contact row on Add Contact click', async () => {
        const form = makeFormData();
        const wrapper = mount(CustomerForm, {
            props: { ...defaultProps, form },
        });
        const addBtn = wrapper
            .findAll('button')
            .find((b) => b.text().includes('Add Contact'));
        await addBtn!.trigger('click');
        expect(form.contacts).toHaveLength(2);
    });

    it('removes contact row on remove click', async () => {
        const form = makeFormData({
            contacts: [
                { name: 'A', email: 'a@test.test' },
                { name: 'B', email: 'b@test.test' },
            ],
        });
        const wrapper = mount(CustomerForm, {
            props: { ...defaultProps, form },
        });
        // Find the remove button (the X svg button)
        const removeBtns = wrapper
            .findAll('button')
            .filter((b) => b.find('svg').exists());
        await removeBtns[0].trigger('click');
        expect(form.contacts).toHaveLength(1);
    });

    it('does not show remove button when only one contact', () => {
        const wrapper = mount(CustomerForm, { props: defaultProps });
        // The remove button should not exist for single contact
        const removeBtns = wrapper
            .findAll('button')
            .filter((b) => b.find('svg path[d*="M6 18"]').exists());
        expect(removeBtns).toHaveLength(0);
    });

    it('shows "Saving..." when form is processing', () => {
        const form = makeFormData();
        form.processing = true;
        const wrapper = mount(CustomerForm, {
            props: { ...defaultProps, form },
        });
        expect(wrapper.text()).toContain('Saving...');
    });

    it('disables submit button when processing', () => {
        const form = makeFormData();
        form.processing = true;
        const wrapper = mount(CustomerForm, {
            props: { ...defaultProps, form },
        });
        const submit = wrapper.find('button[type="submit"]');
        expect((submit.element as HTMLButtonElement).disabled).toBe(true);
    });

    it('displays server validation errors', () => {
        const form = makeFormData();
        form.errors = { name: 'The name field is required.' };
        const wrapper = mount(CustomerForm, {
            props: { ...defaultProps, form },
        });
        expect(wrapper.text()).toContain('The name field is required.');
    });

    it('renders all currency options', () => {
        const wrapper = mount(CustomerForm, { props: defaultProps });
        const options = wrapper.find('#customer-currency').findAll('option');
        expect(options).toHaveLength(3);
    });

    it('validates customer name on blur', async () => {
        const wrapper = mount(CustomerForm, { props: defaultProps });
        const input = wrapper.find('#customer-name');
        await input.trigger('blur');
        expect(wrapper.text()).toContain('Customer name is required');
    });

    it('validates email format on blur', async () => {
        const form = makeFormData({
            contacts: [{ name: 'Test', email: 'invalid-email' }],
        });
        const wrapper = mount(CustomerForm, {
            props: { ...defaultProps, form },
        });
        const emailInputs = wrapper.findAll('input[type="email"]');
        await emailInputs[0].trigger('blur');
        expect(wrapper.text()).toContain('Please enter a valid email address');
    });

    it('validates contact name required when email provided', async () => {
        const form = makeFormData({
            contacts: [{ name: '', email: 'test@example.test' }],
        });
        const wrapper = mount(CustomerForm, {
            props: { ...defaultProps, form },
        });
        // Find the contact name input and trigger blur
        const nameInputs = wrapper.findAll('input[placeholder="Contact name"]');
        await nameInputs[0].trigger('blur');
        expect(wrapper.text()).toContain(
            'Name is required when email is provided',
        );
    });
});
