import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { makeInvoice, makeCustomer, makeContact } from '../../helpers';

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

// Mock fetch for template loading
global.fetch = vi.fn().mockResolvedValue({
    ok: true,
    json: () =>
        Promise.resolve({ subject: 'Test Subject', body: '<p>Test body</p>' }),
});

const customer = makeCustomer({
    id: 1,
    name: 'ACME Corp',
    emails: [makeContact({ email: 'billing@acme.test' })],
});

function mountComponent(propsOverride = {}) {
    return mount(EmailModal, {
        props: {
            show: true,
            invoice: makeInvoice({
                id: 5,
                invoice_number: 'INV-001',
                customer_id: 1,
            }),
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

describe('Invoice/EmailModal', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders when show is true', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('[role="dialog"]').exists()).toBe(true);
    });

    it('does not render when show is false', () => {
        const wrapper = mountComponent({ show: false });
        expect(wrapper.find('[role="dialog"]').exists()).toBe(false);
    });

    it('displays customer name in header', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('ACME Corp');
    });

    it('renders template type selector', () => {
        const wrapper = mountComponent();
        const select = wrapper.find('select');
        expect(select.exists()).toBe(true);
        expect(wrapper.text()).toContain('Initial Invoice');
    });

    it('renders estimate template types for estimate', () => {
        const wrapper = mountComponent({
            invoice: makeInvoice({ type: 'estimate', customer_id: 1 }),
        });
        expect(wrapper.text()).toContain('Initial Estimate');
    });

    it('renders subject input', () => {
        const wrapper = mountComponent();
        const labels = wrapper.findAll('label');
        expect(labels.some((l) => l.text() === 'Subject')).toBe(true);
    });

    it('renders TipTapEditor for body', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('[data-testid="tiptap-editor"]').exists()).toBe(
            true,
        );
    });

    it('shows attach PDF checkbox with invoice number', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('INV-001.pdf');
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

    it('calls form.post on Send', async () => {
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

    it('shows Send To label', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Send To');
    });

    it('shows Cc label', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Cc');
    });

    it('renders customize templates link', () => {
        const wrapper = mountComponent();
        const link = wrapper.find('a[href="/email-templates"]');
        expect(link.exists()).toBe(true);
        expect(link.text()).toContain('Customize');
    });
});
