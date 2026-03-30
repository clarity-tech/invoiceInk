import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { makeInvoice, makeCustomer, makeContact } from '../helpers';

const { mockPost, mockClearErrors } = vi.hoisted(() => ({
    mockPost: vi.fn(),
    mockClearErrors: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial: Record<string, unknown>) => {
        const data = { ...initial };
        return new Proxy(data, {
            get(target, prop) {
                if (prop === 'post') return mockPost;
                if (prop === 'put') return vi.fn();
                if (prop === 'delete') return vi.fn();
                if (prop === 'errors') return {};
                if (prop === 'processing') return false;
                if (prop === 'reset') return vi.fn();
                if (prop === 'clearErrors') return mockClearErrors;
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
    Link: { template: '<a><slot /></a>' },
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
                    emails: [{ name: 'Admin', email: 'admin@company.test' }],
                },
            },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

import EmailModal from '@/Components/Invoice/EmailModal.vue';

const mockFetch = vi.fn();
global.fetch = mockFetch;

const customer = makeCustomer({
    id: 1,
    name: 'ACME Corp',
    emails: [
        makeContact({ email: 'billing@acme.test' }),
        makeContact({ name: 'Support', email: 'support@acme.test' }),
    ],
});

const invoice = makeInvoice({
    id: 5,
    invoice_number: 'INV-2026-001',
    customer_id: 1,
    type: 'invoice',
});

function mountComponent(propsOverride = {}) {
    return mount(EmailModal, {
        props: {
            show: true,
            invoice,
            customers: [customer],
            ...propsOverride,
        },
        global: {
            stubs: {
                TipTapEditor: {
                    template: '<div data-testid="tiptap-editor" />',
                    props: ['modelValue', 'placeholder'],
                    emits: ['update:modelValue'],
                },
            },
        },
    });
}

describe('EmailSendFlow', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockFetch.mockResolvedValue({
            ok: true,
            json: () =>
                Promise.resolve({
                    subject: 'Invoice INV-2026-001',
                    body: '<p>Please find attached invoice.</p>',
                }),
        });
    });

    it('opens modal with invoice data and shows customer name', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('[role="dialog"]').exists()).toBe(true);
        expect(wrapper.text()).toContain('ACME Corp');
    });

    it('calls fetch for template loading on template type change', async () => {
        const wrapper = mountComponent();
        // Change template type to trigger the watch
        const select = wrapper.find('select');
        await select.setValue('invoice_reminder');
        expect(mockFetch).toHaveBeenCalledWith(
            expect.stringContaining('template_type=invoice_reminder'),
            expect.any(Object),
        );
    });

    it('shows template type selector with invoice template options', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Initial Invoice');
        expect(wrapper.text()).toContain('Reminder');
        expect(wrapper.text()).toContain('Overdue Notice');
        expect(wrapper.text()).toContain('Thank You');
    });

    it('shows estimate template types for estimate invoice', () => {
        const wrapper = mountComponent({
            invoice: makeInvoice({ id: 5, type: 'estimate', customer_id: 1 }),
        });
        expect(wrapper.text()).toContain('Initial Estimate');
        expect(wrapper.text()).toContain('Expired Notice');
    });

    it('changes template content when template type changes', async () => {
        const wrapper = mountComponent();
        // Change template type
        const select = wrapper.find('select');
        await select.setValue('invoice_reminder');
        // Should call fetch again with reminder type
        expect(mockFetch).toHaveBeenCalledWith(
            expect.stringContaining('template_type=invoice_reminder'),
            expect.any(Object),
        );
    });

    it('renders Send To and Cc sections', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Send To');
        expect(wrapper.text()).toContain('Cc');
    });

    it('renders email input fields for To and Cc', () => {
        const wrapper = mountComponent();
        const emailInputs = wrapper.findAll('input[type="email"]');
        expect(emailInputs.length).toBe(2); // one for To, one for Cc
    });

    it('adds recipient email on Enter keypress', async () => {
        const wrapper = mountComponent();
        const toInput = wrapper.findAll('input[type="email"]')[0];
        await toInput.setValue('new@recipient.test');
        await toInput.trigger('keydown.enter');
        expect(wrapper.text()).toContain('new@recipient.test');
    });

    it('renders To and Cc input containers for adding recipients', () => {
        const wrapper = mountComponent();
        // Two input containers with placeholder text for adding emails
        const emailInputs = wrapper.findAll('input[type="email"]');
        expect(emailInputs.length).toBe(2);
        expect(emailInputs[0].attributes('placeholder')).toContain(
            'Type email',
        );
        expect(emailInputs[1].attributes('placeholder')).toContain(
            'Type email',
        );
    });

    it('adds CC email on Enter keypress', async () => {
        const wrapper = mountComponent();
        const ccInput = wrapper.findAll('input[type="email"]')[1];
        await ccInput.setValue('cc@test.test');
        await ccInput.trigger('keydown.enter');
        expect(wrapper.text()).toContain('cc@test.test');
    });

    it('shows attach PDF checkbox checked by default', () => {
        const wrapper = mountComponent();
        const checkbox = wrapper.find('input[type="checkbox"]');
        expect(checkbox.exists()).toBe(true);
        expect((checkbox.element as HTMLInputElement).checked).toBe(true);
    });

    it('displays invoice number in PDF attachment label', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('INV-2026-001.pdf');
    });

    it('calls form.post with correct URL on Send', async () => {
        const wrapper = mountComponent();
        const sendBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Send');
        await sendBtn!.trigger('click');
        expect(mockPost).toHaveBeenCalledWith(
            '/invoices/5/send-email',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('emits close when Cancel is clicked', async () => {
        const wrapper = mountComponent();
        const cancelBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Cancel');
        await cancelBtn!.trigger('click');
        expect(wrapper.emitted('close')).toBeTruthy();
    });

    it('emits close when X button is clicked', async () => {
        const wrapper = mountComponent();
        const closeBtn = wrapper.find('[aria-label="Close"]');
        await closeBtn.trigger('click');
        expect(wrapper.emitted('close')).toBeTruthy();
    });

    it('does not render when show is false', () => {
        const wrapper = mountComponent({ show: false });
        expect(wrapper.find('[role="dialog"]').exists()).toBe(false);
    });

    it('shows subject input field', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Subject');
        const subjectInput = wrapper.find('input[type="text"]');
        expect(subjectInput.exists()).toBe(true);
    });

    it('renders TipTapEditor for body', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('[data-testid="tiptap-editor"]').exists()).toBe(
            true,
        );
    });

    it('shows customize templates link', () => {
        const wrapper = mountComponent();
        const link = wrapper.find('a[href="/email-templates"]');
        expect(link.exists()).toBe(true);
    });

    it('handles fetch error gracefully', async () => {
        mockFetch.mockResolvedValueOnce({
            ok: false,
            json: () => Promise.resolve({}),
        });
        const wrapper = mountComponent();
        // Should not crash
        expect(wrapper.find('[role="dialog"]').exists()).toBe(true);
    });
});
