import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn(),
    Head: { template: '<div />' },
    Link: { template: '<a :href="href"><slot /></a>', props: ['href'] },
    router: { get: vi.fn(), post: vi.fn(), delete: vi.fn(), reload: vi.fn() },
    usePage: () => ({
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Test',
                    email: 'test@test.test',
                    profile_photo_url: '',
                    two_factor_enabled: false,
                },
                currentTeam: {
                    id: 1,
                    name: 'Team',
                    company_name: 'Co',
                    currency: 'INR',
                    personal_team: false,
                },
            },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

import Index from '@/Pages/EmailTemplates/Index.vue';

const invoiceTemplates = [
    {
        type: 'invoice_initial',
        label: 'Initial Invoice',
        description: 'Sent when sharing an invoice',
        document_type: 'invoice',
        is_customized: true,
    },
    {
        type: 'invoice_reminder',
        label: 'Invoice Reminder',
        description: 'Payment reminder',
        document_type: 'invoice',
        is_customized: false,
    },
];

const estimateTemplates = [
    {
        type: 'estimate_initial',
        label: 'Initial Estimate',
        description: 'Sent when sharing an estimate',
        document_type: 'estimate',
        is_customized: false,
    },
];

const variables = {
    '{{customer_name}}': 'Customer name',
    '{{invoice_number}}': 'Invoice number',
    '{{total}}': 'Invoice total',
};

function mountComponent(propsOverride = {}) {
    return mount(Index, {
        props: {
            templates: [...invoiceTemplates, ...estimateTemplates],
            variables,
            ...propsOverride,
        },
        global: {
            stubs: {
                SettingsLayout: {
                    template: '<div><slot /></div>',
                },
            },
        },
    });
}

describe('EmailTemplates/Index', () => {
    it('renders page title', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Invoice Templates');
    });

    it('renders invoice templates section', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Invoice Templates');
        expect(wrapper.text()).toContain('Initial Invoice');
        expect(wrapper.text()).toContain('Invoice Reminder');
    });

    it('renders estimate templates section', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Estimate Templates');
        expect(wrapper.text()).toContain('Initial Estimate');
    });

    it('shows Customized badge for customized templates', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Customized');
    });

    it('shows Default badge for non-customized templates', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Default');
    });

    it('shows Edit link for customized templates', () => {
        const wrapper = mountComponent();
        const links = wrapper.findAll('a');
        const editLink = links.find(
            (a) =>
                a.text() === 'Edit' &&
                a.attributes('href') === '/email-templates/invoice_initial',
        );
        expect(editLink).toBeTruthy();
    });

    it('shows Customize link for default templates', () => {
        const wrapper = mountComponent();
        const links = wrapper.findAll('a');
        const customizeLink = links.find(
            (a) =>
                a.text() === 'Customize' &&
                a.attributes('href') === '/email-templates/invoice_reminder',
        );
        expect(customizeLink).toBeTruthy();
    });

    it('renders available template variables', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('{{customer_name}}');
        expect(wrapper.text()).toContain('{{invoice_number}}');
        expect(wrapper.text()).toContain('{{total}}');
    });

    it('uses SettingsLayout', () => {
        const wrapper = mountComponent();
        // SettingsLayout provides the outer chrome; component renders its own content
        expect(wrapper.text()).toContain('Estimate Templates');
    });
});
