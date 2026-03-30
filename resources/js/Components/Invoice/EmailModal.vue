<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import TipTapEditor from '@/Components/TipTapEditor.vue';
import type { Invoice, Customer } from '@/types';

const props = defineProps<{
    show: boolean;
    invoice: Invoice;
    customers: Customer[];
}>();

const emit = defineEmits<{
    close: [];
}>();

const page = usePage();
const auth = computed(() => (page.props as any).auth);

const customer = computed(
    () =>
        props.customers.find((c) => c.id === props.invoice.customer_id) ?? null,
);

const customerEmails = computed(
    () => customer.value?.emails?.map((c) => c.email) ?? [],
);

const orgEmails = computed(
    () => auth.value?.currentTeam?.emails?.map((c: any) => c.email) ?? [],
);

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
    attach_pdf: true,
});

const newToEmail = ref('');
const newCcEmail = ref('');

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

            form.recipients = [...customerEmails.value];
            form.cc = [...orgEmails.value];
            form.attach_pdf = true;
            form.clearErrors();
            newToEmail.value = '';
            newCcEmail.value = '';

            loadTemplate(selectedTemplateType.value);
        }
    },
);

// Reload template when type changes
watch(selectedTemplateType, (newType) => {
    if (newType && props.show) {
        loadTemplate(newType);
    }
});

function addToRecipient() {
    const email = newToEmail.value.trim();

    if (email && !form.recipients.includes(email)) {
        form.recipients.push(email);
    }

    newToEmail.value = '';
}

function removeRecipient(email: string) {
    form.recipients = form.recipients.filter((e) => e !== email);
}

function addCcRecipient() {
    const email = newCcEmail.value.trim();

    if (email && !form.cc.includes(email)) {
        form.cc.push(email);
    }

    newCcEmail.value = '';
}

function removeCcRecipient(email: string) {
    form.cc = form.cc.filter((e) => e !== email);
}

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
        class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-gray-900"
        @keydown.escape="emit('close')"
    >
        <div
            class="mx-4 flex max-h-[95vh] w-full max-w-lg flex-col overflow-hidden rounded-lg bg-white shadow-2xl sm:max-w-xl md:max-w-2xl lg:max-w-3xl"
            role="dialog"
            aria-modal="true"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4"
            >
                <h2 class="text-xl font-semibold text-gray-900">
                    Email to {{ customer?.name ?? 'Customer' }}
                </h2>
                <button
                    class="text-gray-400 hover:text-gray-600"
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
                        <label class="w-20 text-sm font-medium text-gray-600"
                            >Template</label
                        >
                        <div class="flex flex-1 items-center gap-2">
                            <select
                                v-model="selectedTemplateType"
                                class="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
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
                                class="text-xs text-gray-400"
                                >Loading...</span
                            >
                            <a
                                href="/email-templates"
                                target="_blank"
                                class="text-xs text-brand-600 hover:text-brand-800"
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
                            class="w-20 pt-2 text-sm font-medium text-gray-600"
                            >Send To</label
                        >
                        <div class="flex-1">
                            <div
                                class="flex min-h-[44px] flex-wrap items-center gap-2 rounded-md border border-gray-300 p-2 focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500"
                            >
                                <div
                                    v-for="email in form.recipients"
                                    :key="email"
                                    class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-1"
                                >
                                    <span class="text-sm text-gray-700">{{
                                        email
                                    }}</span>
                                    <button
                                        type="button"
                                        class="text-gray-500 hover:text-gray-700"
                                        @click="removeRecipient(email)"
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
                                </div>
                                <input
                                    v-model="newToEmail"
                                    type="email"
                                    placeholder="Type email and press Enter"
                                    class="min-w-[200px] flex-1 border-none p-1 text-sm outline-none focus:ring-0"
                                    @keydown.enter.prevent="addToRecipient"
                                />
                            </div>
                            <p
                                v-if="form.errors.recipients"
                                class="mt-1 text-xs text-red-600"
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
                            class="w-20 pt-2 text-sm font-medium text-gray-600"
                            >Cc</label
                        >
                        <div class="flex-1">
                            <div
                                class="flex min-h-[44px] flex-wrap items-center gap-2 rounded-md border border-gray-300 p-2 focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500"
                            >
                                <div
                                    v-for="email in form.cc"
                                    :key="email"
                                    class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-1"
                                >
                                    <span class="text-sm text-gray-700">{{
                                        email
                                    }}</span>
                                    <button
                                        type="button"
                                        class="text-gray-500 hover:text-gray-700"
                                        @click="removeCcRecipient(email)"
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
                                </div>
                                <input
                                    v-model="newCcEmail"
                                    type="email"
                                    placeholder="Type email and press Enter"
                                    class="min-w-[200px] flex-1 border-none p-1 text-sm outline-none focus:ring-0"
                                    @keydown.enter.prevent="addCcRecipient"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subject -->
                <div class="mb-4">
                    <div class="flex items-center">
                        <label class="w-20 text-sm font-medium text-gray-600"
                            >Subject</label
                        >
                        <input
                            v-model="form.subject"
                            type="text"
                            class="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                        />
                    </div>
                    <p
                        v-if="form.errors.subject"
                        class="mt-1 ml-20 text-xs text-red-600"
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
                        class="mt-1 text-xs text-red-600"
                    >
                        {{ form.errors.body }}
                    </p>
                </div>

                <!-- Attach PDF -->
                <div class="mb-4 border-t pt-4">
                    <h3 class="mb-3 text-sm font-semibold text-gray-700">
                        Attachments
                    </h3>
                    <label
                        class="flex cursor-pointer items-center rounded-md p-3 hover:bg-gray-50"
                    >
                        <input
                            v-model="form.attach_pdf"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                        />
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">
                                {{ invoice.invoice_number }}.pdf
                            </div>
                            <div class="text-xs text-gray-500">
                                {{
                                    invoice.type === 'estimate'
                                        ? 'Estimate'
                                        : 'Invoice'
                                }}
                                PDF document
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4"
            >
                <button
                    type="button"
                    class="rounded-md border border-gray-300 px-6 py-2 font-medium text-gray-700 hover:bg-gray-100"
                    @click="emit('close')"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    :disabled="form.processing"
                    class="rounded-md bg-brand-600 px-6 py-2 font-medium text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="send"
                >
                    {{ form.processing ? 'Sending...' : 'Send' }}
                </button>
            </div>
        </div>
    </div>
</template>
