<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AttachmentDropzone from './AttachmentDropzone.vue';
import EmailRecipientInput from './EmailRecipientInput.vue';
import TipTapEditor from '@/Components/TipTapEditor.vue';
import { formatBytes } from '@/composables/useFileAttachments';
import { mergeRecipients } from '@/composables/useRecipients';
import type { Invoice, Customer, Contact, InvoiceAttachment } from '@/types';

const props = withDefaults(
    defineProps<{
        show: boolean;
        invoice: Invoice;
        customers: Customer[];
        invoiceAttachments?: InvoiceAttachment[];
    }>(),
    {
        invoiceAttachments: () => [],
    },
);

const emit = defineEmits<{
    close: [];
}>();

const page = usePage();
const auth = computed(() => (page.props as any).auth);

// Default checkbox state for the optional send-options, sourced from
// config('app.invoice_email') and shared via HandleInertiaRequests.
// Falls back to `true` when the shared prop is absent (e.g. in unit tests).
const emailDefaults = {
    attach_pdf: (page.props as any).emailDefaults?.attach_pdf ?? true,
    include_view_online_button:
        (page.props as any).emailDefaults?.include_view_online_button ?? true,
};

const customer = computed(
    () =>
        props.customers.find((c) => c.id === props.invoice.customer_id) ?? null,
);

const customerContacts = computed<Contact[]>(
    () => customer.value?.emails ?? [],
);

const orgContacts = computed<Contact[]>(
    () => auth.value?.currentTeam?.emails ?? [],
);

const orgEmails = computed(() => orgContacts.value.map((c) => c.email));

// Suggestion pool for both recipient fields: the customer's contacts plus the
// organization's contacts, deduped by email.
const contactSuggestions = computed<Contact[]>(() => {
    const seen = new Set<string>();

    return [...customerContacts.value, ...orgContacts.value].filter(
        (contact) => {
            const email = contact.email?.trim();

            if (!email || seen.has(email)) {
                return false;
            }

            seen.add(email);

            return true;
        },
    );
});

const templateTypes = computed(() => {
    const docType = props.invoice.type === 'estimate' ? 'estimate' : 'invoice';

    return {
        [`${docType}_initial`]:
            docType === 'invoice' ? 'Initial Invoice' : 'Initial Estimate',
        [`${docType}_reminder`]: 'Reminder',
        [`${docType}_overdue`]:
            docType === 'invoice' ? 'Overdue Notice' : 'Expired Notice',
        [`${docType}_thank_you`]: 'Thank You',
    };
});

const selectedTemplateType = ref('');
const loadingTemplate = ref(false);

const form = useForm({
    recipients: [] as string[],
    cc: [] as string[],
    subject: '',
    body: '',
    attach_pdf: emailDefaults.attach_pdf,
    include_view_online_button: emailDefaults.include_view_online_button,
    // Ids of the invoice's saved attachments to include in this send. Excluding
    // one here never deletes it from the invoice.
    attachment_ids: [] as number[],
    // New files: uploaded to the invoice AND attached to this send.
    attachments: [] as File[],
});

// Saved invoice attachments still selected for this send.
const includedAttachments = computed(() =>
    props.invoiceAttachments.filter((a) => form.attachment_ids.includes(a.id)),
);

function removeInvoiceAttachment(id: number) {
    form.attachment_ids = form.attachment_ids.filter((a) => a !== id);
}

async function loadTemplate(templateType: string) {
    loadingTemplate.value = true;

    try {
        const response = await fetch(
            `/api/email-templates/resolve?template_type=${templateType}&invoice_id=${props.invoice.id}`,
            {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        );

        if (response.ok) {
            const data = await response.json();
            form.subject = data.subject;
            form.body = data.body;
        }
    } finally {
        loadingTemplate.value = false;
    }
}

// Initialize form when modal opens
watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            const docType =
                props.invoice.type === 'estimate' ? 'estimate' : 'invoice';
            selectedTemplateType.value = `${docType}_initial`;

            // Pre-fill "Send To" with the recipients the invoice remembers as
            // selected (its checked entries), falling back to the customer's
            // contacts. Unselected contacts stay in the suggestion dropdown so
            // they can be added back.
            form.recipients = mergeRecipients(
                customerContacts.value,
                props.invoice.email_recipients ?? [],
            )
                .filter((recipient) => recipient.checked)
                .map((recipient) => recipient.email);
            form.cc = [...orgEmails.value];
            form.attach_pdf = emailDefaults.attach_pdf;
            form.include_view_online_button =
                emailDefaults.include_view_online_button;
            form.attachment_ids = props.invoiceAttachments.map((a) => a.id);
            form.attachments = [];
            form.clearErrors();

            loadTemplate(selectedTemplateType.value);
        }
    },
    // Populate immediately in case the modal is mounted already open.
    { immediate: true },
);

// Reload template when type changes
watch(selectedTemplateType, (newType) => {
    if (newType && props.show) {
        loadTemplate(newType);
    }
});

// At least one recipient is required to send.
const hasRecipients = computed(() => form.recipients.length > 0);

function send() {
    form.post(`/invoices/${props.invoice.id}/send-email`, {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-overlay"
        @keydown.escape="emit('close')"
    >
        <div
            class="mx-4 flex max-h-[95vh] w-full max-w-lg flex-col overflow-hidden rounded-lg bg-card shadow-2xl sm:max-w-xl md:max-w-2xl lg:max-w-3xl"
            role="dialog"
            aria-modal="true"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-border bg-card px-6 py-4"
            >
                <h2 class="text-xl font-semibold text-foreground">
                    Email to {{ customer?.name ?? 'Customer' }}
                </h2>
                <button
                    class="text-faint-foreground hover:text-soft-foreground"
                    aria-label="Close"
                    @click="emit('close')"
                >
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto px-6 py-4">
                <!-- Template Type Selector -->
                <div class="mb-4">
                    <div class="flex items-center">
                        <label
                            class="w-20 text-sm font-medium text-soft-foreground"
                            >Template</label
                        >
                        <div class="flex flex-1 items-center gap-2">
                            <select
                                v-model="selectedTemplateType"
                                class="flex-1 rounded-md border border-input px-3 py-2 text-sm focus:ring-2 focus:ring-ring focus:outline-none"
                            >
                                <option
                                    v-for="(label, value) in templateTypes"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </option>
                            </select>
                            <span
                                v-if="loadingTemplate"
                                class="text-xs text-faint-foreground"
                                >Loading...</span
                            >
                            <a
                                href="/email-templates"
                                target="_blank"
                                class="text-xs text-primary hover:text-primary-soft-foreground"
                                title="Customize templates"
                            >
                                Customize
                            </a>
                        </div>
                    </div>
                </div>

                <!-- To -->
                <div class="mb-4">
                    <div class="flex items-start">
                        <label
                            class="w-20 pt-2 text-sm font-medium text-soft-foreground"
                            >Send To</label
                        >
                        <div class="flex-1">
                            <EmailRecipientInput
                                v-model="form.recipients"
                                :suggestions="contactSuggestions"
                            />
                            <p
                                v-if="form.errors.recipients"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ form.errors.recipients }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Cc -->
                <div class="mb-4">
                    <div class="flex items-start">
                        <label
                            class="w-20 pt-2 text-sm font-medium text-soft-foreground"
                            >Cc</label
                        >
                        <div class="flex-1">
                            <EmailRecipientInput
                                v-model="form.cc"
                                :suggestions="contactSuggestions"
                            />
                        </div>
                    </div>
                </div>

                <!-- Subject -->
                <div class="mb-4">
                    <div class="flex items-center">
                        <label
                            class="w-20 text-sm font-medium text-soft-foreground"
                            >Subject</label
                        >
                        <input
                            v-model="form.subject"
                            type="text"
                            class="flex-1 rounded-md border border-input px-3 py-2 text-sm focus:ring-2 focus:ring-ring focus:outline-none"
                        />
                    </div>
                    <p
                        v-if="form.errors.subject"
                        class="mt-1 ml-20 text-xs text-destructive"
                    >
                        {{ form.errors.subject }}
                    </p>
                </div>

                <!-- Body (TipTap) -->
                <div class="mb-4">
                    <TipTapEditor
                        v-model="form.body"
                        placeholder="Compose your email..."
                    />
                    <p
                        v-if="form.errors.body"
                        class="mt-1 text-xs text-destructive"
                    >
                        {{ form.errors.body }}
                    </p>
                </div>

                <!-- Options -->
                <div class="mb-4 border-t border-border pt-4">
                    <h3 class="mb-3 text-sm font-semibold text-soft-foreground">
                        Options
                    </h3>
                    <label
                        class="flex cursor-pointer items-center rounded-md p-3 hover:bg-muted"
                    >
                        <input
                            v-model="form.include_view_online_button"
                            type="checkbox"
                            class="h-4 w-4 rounded border-input text-primary focus:ring-ring"
                        />
                        <div class="ml-3">
                            <div class="text-sm font-medium text-foreground">
                                Include "View
                                {{
                                    invoice.type === 'estimate'
                                        ? 'Estimate'
                                        : 'Invoice'
                                }}
                                Online" button
                            </div>
                            <div class="text-xs text-muted-foreground">
                                Adds a CTA linking to the public document page
                            </div>
                        </div>
                    </label>
                </div>

                <!-- Attachments -->
                <div class="mb-4 border-t border-border pt-4">
                    <h3 class="mb-3 text-sm font-semibold text-soft-foreground">
                        Attachments
                    </h3>

                    <label
                        class="flex cursor-pointer items-center rounded-md p-3 hover:bg-muted"
                    >
                        <input
                            v-model="form.attach_pdf"
                            type="checkbox"
                            class="h-4 w-4 rounded border-input text-primary focus:ring-ring"
                        />
                        <div class="ml-3">
                            <div class="text-sm font-medium text-foreground">
                                {{ invoice.invoice_number }}.pdf
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{
                                    invoice.type === 'estimate'
                                        ? 'Estimate'
                                        : 'Invoice'
                                }}
                                PDF document
                            </div>
                        </div>
                    </label>

                    <!-- Saved invoice attachments (excluding one only omits it
                         from this send; it stays on the invoice). -->
                    <ul
                        v-if="includedAttachments.length > 0"
                        class="mt-2 space-y-1"
                    >
                        <li
                            v-for="attachment in includedAttachments"
                            :key="attachment.id"
                            class="flex items-center justify-between rounded-md border border-border bg-muted px-3 py-2"
                        >
                            <div class="min-w-0 flex-1">
                                <a
                                    :href="attachment.url"
                                    target="_blank"
                                    rel="noopener"
                                    class="truncate text-sm font-medium text-primary hover:underline"
                                >
                                    {{ attachment.name }}
                                </a>
                                <div class="text-xs text-muted-foreground">
                                    {{ formatBytes(attachment.size) }}
                                </div>
                            </div>
                            <button
                                type="button"
                                class="ml-2 text-faint-foreground hover:text-destructive"
                                aria-label="Exclude attachment from this email"
                                @click="removeInvoiceAttachment(attachment.id)"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </button>
                        </li>
                    </ul>

                    <!-- New files: uploaded to the invoice and attached here. -->
                    <div class="mt-2">
                        <AttachmentDropzone v-model="form.attachments" />
                    </div>

                    <p
                        v-if="form.errors.attachments"
                        class="mt-1 text-xs text-destructive"
                    >
                        {{ form.errors.attachments }}
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex gap-3 border-t border-border bg-muted px-6 py-4">
                <button
                    type="button"
                    class="rounded-md border border-input px-6 py-2 font-medium text-soft-foreground hover:bg-secondary"
                    @click="emit('close')"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    :disabled="form.processing || !hasRecipients"
                    class="rounded-md bg-primary px-6 py-2 font-medium text-primary-foreground hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="send"
                >
                    {{ form.processing ? 'Sending...' : 'Send' }}
                </button>
            </div>
        </div>
    </div>
</template>
