import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { makeInvoice, makeCustomer, makeContact } from '../../helpers';

const { mockPost, mockClearErrors } = vi.hoisted(() => ({
    mockPost: vi.fn(),
    mockClearErrors: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => {
    const { reactive } = require('vue');
    return {
        useForm: (initial: Record<string, unknown>) => {
            // Reactive so computed props (e.g. included attachments) update when
            // the component mutates a field, mirroring the real useForm.
            const data = reactive({ ...initial });
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
        router: {
            get: vi.fn(),
            post: vi.fn(),
            delete: vi.fn(),
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
                        emails: [
                            { name: 'Admin', email: 'admin@company.test' },
                        ],
                    },
                },
                flash: { success: null, error: null, message: null },
            },
        }),
    };
});

import EmailModal from '@/Components/Invoice/EmailModal.vue';
import EmailRecipientInput from '@/Components/Invoice/EmailRecipientInput.vue';

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
            invoiceAttachments: [],
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

    it('shows To label', () => {
        const wrapper = mountComponent();
        expect(wrapper.findAll('label').some((l) => l.text() === 'To')).toBe(
            true,
        );
    });

    it('shows Cc label', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Cc');
    });

    it('offers the combined customer + org contacts to both recipient fields', () => {
        const wrapper = mountComponent();
        const inputs = wrapper.findAllComponents(EmailRecipientInput);
        // Send To and Cc.
        expect(inputs).toHaveLength(2);

        for (const input of inputs) {
            const emails = (
                input.props('suggestions') as { email: string }[]
            ).map((c) => c.email);
            expect(emails).toContain('billing@acme.test');
            expect(emails).toContain('admin@company.test');
        }
    });

    it("prefills Send To with the invoice's checked recipients, leaving unchecked ones as suggestions", async () => {
        const rememberedCustomer = makeCustomer({
            id: 1,
            name: 'ACME Corp',
            emails: [
                makeContact({ email: 'primary@acme.test' }),
                makeContact({ email: 'silent@acme.test' }),
            ],
        });

        // Init runs in the watcher on `show`, so mount hidden then reveal.
        const wrapper = mountComponent({
            show: false,
            customers: [rememberedCustomer],
            invoice: makeInvoice({
                id: 5,
                customer_id: 1,
                email_recipients: [
                    { email: 'primary@acme.test', checked: true },
                    { email: 'silent@acme.test', checked: false },
                    { email: 'extra@outside.test', checked: true },
                ],
            }),
        });
        await wrapper.setProps({ show: true });

        const toInput = wrapper.findAllComponents(EmailRecipientInput)[0];
        const recipients = toInput.props('modelValue') as string[];

        // Only the checked entries pre-fill the field.
        expect(recipients).toEqual(['primary@acme.test', 'extra@outside.test']);

        // The unchecked contact stays available in the suggestion dropdown.
        const suggestions = (
            toInput.props('suggestions') as { email: string }[]
        ).map((c) => c.email);
        expect(suggestions).toContain('silent@acme.test');
    });

    it('renders customize templates link', () => {
        const wrapper = mountComponent();
        const link = wrapper.find('a[href="/email-templates"]');
        expect(link.exists()).toBe(true);
        expect(link.text()).toContain('Customize');
    });

    describe('invoice attachments', () => {
        const invoiceAttachments = [
            {
                id: 11,
                name: 'contract.pdf',
                size: 2048,
                mime: 'application/pdf',
                extension: 'pdf',
                url: '/invoices/5/attachments/11',
            },
            {
                id: 12,
                name: 'diagram.png',
                size: 4096,
                mime: 'image/png',
                extension: 'png',
                url: '/invoices/5/attachments/12',
            },
        ];

        // The init happens in a watcher on `show`, so mount hidden then reveal.
        async function mountOpened() {
            const wrapper = mountComponent({
                show: false,
                invoiceAttachments,
            });
            await wrapper.setProps({ show: true });
            return wrapper;
        }

        it("lists the invoice's saved attachments with a view link", async () => {
            const wrapper = await mountOpened();

            const link = wrapper.find('a[href="/invoices/5/attachments/11"]');
            expect(link.exists()).toBe(true);
            expect(link.text()).toContain('contract.pdf');
            expect(wrapper.text()).toContain('diagram.png');
        });

        it('excludes an attachment from the send when its cross is clicked', async () => {
            const wrapper = await mountOpened();

            const removeButtons = wrapper.findAll(
                'button[aria-label="Exclude attachment from this email"]',
            );
            expect(removeButtons).toHaveLength(2);

            await removeButtons[0].trigger('click');

            expect(
                wrapper.find('a[href="/invoices/5/attachments/11"]').exists(),
            ).toBe(false);
            // The other attachment is still present.
            expect(
                wrapper.find('a[href="/invoices/5/attachments/12"]').exists(),
            ).toBe(true);
        });
    });
});
