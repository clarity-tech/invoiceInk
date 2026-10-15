<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import {
    BellRing,
    ExternalLink,
    FileText,
    Globe,
    Loader2,
    Mail,
    Paperclip,
    Send,
    X,
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import AttachmentDropzone from './AttachmentDropzone.vue';
import EmailRecipientInput from './EmailRecipientInput.vue';
import TipTapEditor from '@/Components/TipTapEditor.vue';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import {
    Dialog,
    DialogBody,
    DialogFooter,
    DialogHeader,
} from '@/Components/ui/dialog';
import { Select } from '@/Components/ui/select';
import { formatBytes } from '@/composables/useFileAttachments';
import { formatDate } from '@/composables/useFormatDate';
import { mergeRecipients } from '@/composables/useRecipients';
import type { Invoice, Customer, Contact, InvoiceAttachment } from '@/types';

const props = withDefaults(
    defineProps<{
        show: boolean;
        invoice: Invoice;
        customers: Customer[];
        invoiceAttachments?: InvoiceAttachment[];
        // The template to open with; defaults to the initial send.
        templateType?: string;
    }>(),
    {
        invoiceAttachments: () => [],
        templateType: undefined,
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
            selectedTemplateType.value =
                props.templateType ?? `${docType}_initial`;

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

const documentLabel = computed(() =>
    props.invoice.type === 'estimate' ? 'Estimate' : 'Invoice',
);

const documentSummary = computed(() =>
    [
        `${documentLabel.value} ${props.invoice.invoice_number}`,
        props.invoice.due_at ? `Due ${formatDate(props.invoice.due_at)}` : '',
    ]
        .filter(Boolean)
        .join(' · '),
);

const isReminder = computed(() =>
    /_(reminder|overdue)$/.test(selectedTemplateType.value),
);

function counted(count: number, noun: string): string {
    return `${count} ${noun}${count === 1 ? '' : 's'}`;
}

const sendSummary = computed(() => {
    const attachments =
        (form.attach_pdf ? 1 : 0) +
        includedAttachments.value.length +
        form.attachments.length;

    return [
        counted(form.recipients.length, 'recipient'),
        form.cc.length ? `${form.cc.length} cc` : '',
        attachments ? counted(attachments, 'attachment') : '',
    ]
        .filter(Boolean)
        .join(' · ');
});

const optionCard =
    'flex cursor-pointer items-start gap-3 rounded-lg border border-border bg-background p-3 transition-colors hover:bg-muted has-checked:border-primary/50 has-checked:bg-primary-soft/40';

function send() {
    form.transform((data) => ({
        ...data,
        template_type: selectedTemplateType.value,
    })).post(`/invoices/${props.invoice.id}/send-email`, {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <Dialog
        :open="show"
        size="xl"
        aria-labelledby="email-dialog-title"
        @close="emit('close')"
    >
        <DialogHeader
            title-id="email-dialog-title"
            :title="`Email to ${customer?.name ?? 'Customer'}`"
            :description="documentSummary"
            @close="emit('close')"
        >
            <template #icon>
                <BellRing v-if="isReminder" />
                <Mail v-else />
            </template>
        </DialogHeader>

        <DialogBody>
            <slot name="notice" />

            <div
                class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2"
            >
                <div class="flex items-center gap-3">
                    <label
                        for="email-template"
                        class="text-sm font-medium text-soft-foreground"
                        >Template</label
                    >
                    <Select
                        id="email-template"
                        v-model="selectedTemplateType"
                        class="w-auto min-w-44"
                    >
                        <option
                            v-for="(label, value) in templateTypes"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </Select>
                    <Loader2
                        v-if="loadingTemplate"
                        class="size-4 animate-spin text-muted-foreground"
                        aria-label="Loading template"
                    />
                </div>
                <a
                    href="/email-templates"
                    target="_blank"
                    class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                    title="Customize templates"
                >
                    Customize templates
                    <ExternalLink class="size-3" />
                </a>
            </div>

            <div>
                <div
                    class="divide-y divide-border rounded-lg border border-border bg-background"
                >
                    <div class="flex items-start gap-3 px-3 py-1">
                        <label
                            class="w-14 shrink-0 pt-2 text-sm text-muted-foreground"
                            >To</label
                        >
                        <EmailRecipientInput
                            v-model="form.recipients"
                            bare
                            class="flex-1"
                            :suggestions="contactSuggestions"
                        />
                    </div>
                    <div class="flex items-start gap-3 px-3 py-1">
                        <label
                            class="w-14 shrink-0 pt-2 text-sm text-muted-foreground"
                            >Cc</label
                        >
                        <EmailRecipientInput
                            v-model="form.cc"
                            bare
                            class="flex-1"
                            :suggestions="contactSuggestions"
                        />
                    </div>
                    <div class="flex items-center gap-3 px-3 py-1">
                        <label
                            for="email-subject"
                            class="w-14 shrink-0 text-sm text-muted-foreground"
                            >Subject</label
                        >
                        <input
                            id="email-subject"
                            v-model="form.subject"
                            type="text"
                            class="min-h-9 w-full border-0 bg-transparent p-1 text-sm font-medium text-foreground outline-none focus:ring-0 pointer-coarse:text-base"
                        />
                    </div>
                </div>
                <p
                    v-if="form.errors.recipients"
                    class="mt-1 text-xs text-destructive"
                >
                    {{ form.errors.recipients }}
                </p>
                <p
                    v-if="form.errors.subject"
                    class="mt-1 text-xs text-destructive"
                >
                    {{ form.errors.subject }}
                </p>
            </div>

            <div>
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

            <section class="space-y-3">
                <h3
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    Include
                </h3>
                <div class="grid gap-2 sm:grid-cols-2">
                    <label :class="optionCard">
                        <Checkbox v-model="form.attach_pdf" class="mt-0.5" />
                        <FileText
                            class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                        />
                        <span class="min-w-0">
                            <span
                                class="block truncate text-sm font-medium text-foreground"
                                >{{ invoice.invoice_number }}.pdf</span
                            >
                            <span class="block text-xs text-muted-foreground"
                                >{{ documentLabel }} PDF</span
                            >
                        </span>
                    </label>
                    <label :class="optionCard">
                        <Checkbox
                            v-model="form.include_view_online_button"
                            class="mt-0.5"
                        />
                        <Globe
                            class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                        />
                        <span class="min-w-0">
                            <span
                                class="block text-sm font-medium text-foreground"
                                >"View {{ documentLabel }} Online" button</span
                            >
                            <span class="block text-xs text-muted-foreground"
                                >Links to the public page</span
                            >
                        </span>
                    </label>
                </div>

                <!-- Saved invoice attachments (excluding one only omits it
                     from this send; it stays on the invoice). -->
                <ul v-if="includedAttachments.length > 0" class="space-y-2">
                    <li
                        v-for="attachment in includedAttachments"
                        :key="attachment.id"
                        class="flex items-center gap-3 rounded-lg border border-border bg-background px-3 py-2"
                    >
                        <Paperclip
                            class="size-4 shrink-0 text-muted-foreground"
                        />
                        <div class="min-w-0 flex-1">
                            <a
                                :href="attachment.url"
                                target="_blank"
                                rel="noopener"
                                class="block truncate text-sm font-medium text-primary hover:underline"
                            >
                                {{ attachment.name }}
                            </a>
                            <div class="text-xs text-muted-foreground">
                                {{ formatBytes(attachment.size) }}
                            </div>
                        </div>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-7 text-muted-foreground hover:text-destructive"
                            aria-label="Exclude attachment from this email"
                            @click="removeInvoiceAttachment(attachment.id)"
                        >
                            <X />
                        </Button>
                    </li>
                </ul>

                <!-- New files: uploaded to the invoice and attached here. -->
                <AttachmentDropzone v-model="form.attachments" />

                <p
                    v-if="form.errors.attachments"
                    class="text-xs text-destructive"
                >
                    {{ form.errors.attachments }}
                </p>
            </section>
        </DialogBody>

        <DialogFooter>
            <template #meta>{{ sendSummary }}</template>
            <Button variant="outline" @click="emit('close')">Cancel</Button>
            <Button :disabled="form.processing || !hasRecipients" @click="send">
                <Send />
                {{ form.processing ? 'Sending...' : 'Send' }}
            </Button>
        </DialogFooter>
    </Dialog>
</template>
