import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

const { mockPut, mockRouterDelete } = vi.hoisted(() => ({
    mockPut: vi.fn(),
    mockRouterDelete: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial: Record<string, unknown>) => {
        const data = { ...initial };
        return new Proxy(data, {
            get(target, prop) {
                if (prop === 'put') return mockPut;
                if (prop === 'post') return vi.fn();
                if (prop === 'delete') return vi.fn();
                if (prop === 'errors') return {};
                if (prop === 'processing') return false;
                if (prop === 'reset') return vi.fn();
                if (prop === 'clearErrors') return vi.fn();
                if (prop === 'transform') return vi.fn().mockReturnThis();
                return target[prop as string];
            },
            set(target, prop, value) {
                target[prop as string] = value;
                return true;
            },
        });
    },
    Head: { template: '<div />' },
    Form: { template: '<form><slot /></form>' },
    Link: { template: '<a><slot /></a>' },
    router: {
        get: vi.fn(),
        post: vi.fn(),
        delete: mockRouterDelete,
        reload: vi.fn(),
    },
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

import EditPage from '@/Pages/EmailTemplates/Edit.vue';

const defaultProps = {
    templateType: 'invoice_initial',
    label: 'Initial Invoice',
    description: 'Sent when sharing an invoice for the first time',
    documentType: 'invoice',
    template: {
        subject: 'Invoice {{invoice_number}} from {{company_name}}',
        body: '<p>Hello {{customer_name}},</p>',
        is_customized: true,
    },
    defaultTemplate: {
        subject: 'Default subject',
        body: '<p>Default body</p>',
    },
    variables: {
        '{{customer_name}}': 'Customer name',
        '{{invoice_number}}': 'Invoice number',
        '{{company_name}}': 'Your company name',
    },
};

function mountComponent(propsOverride = {}) {
    return mount(EditPage, {
        props: { ...defaultProps, ...propsOverride },
        global: {
            stubs: {
                AppLayout: {
                    template: '<div><slot name="header" /><slot /></div>',
                },
                TipTapEditor: {
                    template:
                        '<div data-testid="tiptap-editor"><textarea :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" /></div>',
                    props: ['modelValue', 'placeholder'],
                    emits: ['update:modelValue'],
                },
            },
        },
    });
}

describe('EmailTemplates/Edit', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders template label', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Initial Invoice');
    });

    it('renders subject input with template value', () => {
        const wrapper = mountComponent();
        const input = wrapper.find('input[type="text"]');
        expect((input.element as HTMLInputElement).value).toBe(
            'Invoice {{invoice_number}} from {{company_name}}',
        );
    });

    it('renders TipTapEditor for body', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('[data-testid="tiptap-editor"]').exists()).toBe(
            true,
        );
    });

    it('shows Customized badge when template is customized', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Customized');
    });

    it('shows Default badge when template is not customized', () => {
        const wrapper = mountComponent({
            template: { ...defaultProps.template, is_customized: false },
        });
        expect(wrapper.text()).toContain('Default');
    });

    it('shows Save Template button', () => {
        const wrapper = mountComponent();
        const saveBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Save Template');
        expect(saveBtn).toBeTruthy();
    });

    it('shows Reset to Default button when customized', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Reset to Default');
    });

    it('hides Reset to Default button when not customized', () => {
        const wrapper = mountComponent({
            template: { ...defaultProps.template, is_customized: false },
        });
        const resetBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Reset to Default');
        expect(resetBtn).toBeUndefined();
    });

    it('calls router.delete on Reset to Default', async () => {
        window.confirm = vi.fn().mockReturnValue(true);
        const wrapper = mountComponent();
        const resetBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Reset to Default');
        await resetBtn!.trigger('click');
        expect(mockRouterDelete).toHaveBeenCalledWith(
            '/email-templates/invoice_initial',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('renders variable buttons in sidebar', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('{{customer_name}}');
        expect(wrapper.text()).toContain('{{invoice_number}}');
        expect(wrapper.text()).toContain('{{company_name}}');
    });

    it('shows Preview button', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Preview with Sample Data');
    });

    it('shows Restore Default Text when content differs from default', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Restore Default Text');
    });

    it('hides Restore Default Text when content matches default', () => {
        const wrapper = mountComponent({
            template: {
                subject: 'Default subject',
                body: '<p>Default body</p>',
                is_customized: false,
            },
        });
        const restoreBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Restore Default Text');
        expect(restoreBtn).toBeUndefined();
    });
});
