<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import AttachmentDropzone from './AttachmentDropzone.vue';
import EmailModal from './EmailModal.vue';
import ItemRow from './ItemRow.vue';
import RecipientEditor from './RecipientEditor.vue';
import { useFormatMoney } from '@/composables/useFormatMoney';
import { mergeRecipients } from '@/composables/useRecipients';
import { buildGstBreakdown } from '@/composables/useGstBreakdown';
import { useInvoiceCalculator } from '@/composables/useInvoiceCalculator';
import type { LineItem } from '@/composables/useInvoiceCalculator';
import { calcTdsAmount, calcTcsAmount } from '@/composables/useSourceTaxes';
import {
    PAYMENT_TERM_OPTIONS,
    CUSTOM_TERM,
    dueFromTerm,
    termFromDates,
    type PaymentTerm,
} from '@/composables/usePaymentTerms';
import { formatBytes } from '@/composables/useFileAttachments';
import { taxLabelFor } from '@/composables/useTaxLabel';
import type {
    Invoice,
    Customer,
    Contact,
    EmailRecipient,
    Location,
    TaxRateOption,
    InvoiceNumberingSeries,
    InvoiceAttachment,
    Currency,
} from '@/types';

const props = defineProps<{
    mode: 'create' | 'edit';
    type: 'invoice' | 'estimate';
    invoice?: Invoice;
    customers: Customer[];
    organizationLocations: Location[];
    taxOptions: TaxRateOption[];
    numberingSeries: InvoiceNumberingSeries[];
    statusOptions: Record<string, string>;
    taxLabels: Record<string, string>;
    attachments?: InvoiceAttachment[];
    indianStates?: Record<string, string>;
    defaults?: {
        organization_id: number | null;
        organization_location_id: number | null;
        invoice_numbering_series_id: number | null;
        issued_at: string;
        due_at: string;
        currency: string;
    };
}>();

const { formatMoney } = useFormatMoney();
const showEmailModal = ref(false);
const downloadingPdf = ref(false);
let pdfTimer: ReturnType<typeof setTimeout> | null = null;

// Reset PDF loading state after download starts
watch(downloadingPdf, (val) => {
    if (val) {
        pdfTimer = setTimeout(() => {
            downloadingPdf.value = false;
        }, 5000);
    }
});

onUnmounted(() => {
    if (pdfTimer) {
        clearTimeout(pdfTimer);
    }
});

// Build initial items
function buildInitialItems(): LineItem[] {
    if (props.mode === 'edit' && props.invoice?.items?.length) {
        return props.invoice.items.map((item) => ({
            description: item.description,
            sac_code: item.sac_code,
            quantity: item.quantity,
            unit_price: item.unit_price,
            tax_rate: item.tax_rate,
        }));
    }

    return [
        {
            description: '',
            sac_code: null,
            quantity: 1,
            unit_price: 0,
            tax_rate: 0,
        },
    ];
}

const items = ref<LineItem[]>(buildInitialItems());
const { totals } = useInvoiceCalculator(items);

// Stable keys for the v-for so each ItemRow keeps its own local UI state
// (e.g. the tax-rate selection) when rows are added or removed.
let nextItemKey = items.value.length;
const itemKeys = ref<number[]>(items.value.map((_, index) => index));

// Determine currency from selected customer
const selectedCustomerId = ref<number | null>(
    props.mode === 'edit' ? (props.invoice?.customer_id ?? null) : null,
);

const selectedCustomer = computed(
    () =>
        props.customers.find((c) => c.id === selectedCustomerId.value) ?? null,
);

const customerLocations = computed(
    () => selectedCustomer.value?.locations ?? [],
);

// The selected customer's contacts, shown as check/uncheck rows in the
// recipients editor.
const customerContacts = computed<Contact[]>(
    () => selectedCustomer.value?.emails ?? [],
);

const currentCurrency = computed<Currency>(
    () =>
        selectedCustomer.value?.currency ??
        (props.defaults?.currency as Currency) ??
        'INR',
);

// Place of supply applies to Indian (GST) organizations, but not to overseas
// (export) customers.
const showPlaceOfSupply = computed(
    () =>
        Object.keys(props.indianStates ?? {}).length > 0 &&
        selectedCustomer.value?.gst_treatment !== 'overseas',
);

// Form
const form = useForm({
    type: props.type,
    organization_id:
        props.mode === 'edit'
            ? props.invoice!.organization_id
            : (props.defaults?.organization_id ?? null),
    customer_id: selectedCustomerId.value,
    organization_location_id:
        props.mode === 'edit'
            ? (props.invoice!.organization_location?.id ?? null)
            : (props.defaults?.organization_location_id ?? null),
    customer_location_id:
        props.mode === 'edit'
            ? (props.invoice!.customer_location?.id ?? null)
            : (null as number | null),
    customer_shipping_location_id:
        props.mode === 'edit'
            ? (props.invoice!.customer_shipping_location?.id ?? null)
            : (null as number | null),
    status: props.mode === 'edit' ? props.invoice!.status : 'draft',
    issued_at:
        props.mode === 'edit'
            ? (props.invoice!.issued_at?.split('T')[0] ?? '')
            : (props.defaults?.issued_at ?? ''),
    due_at:
        props.mode === 'edit'
            ? (props.invoice!.due_at?.split('T')[0] ?? '')
            : (props.defaults?.due_at ?? ''),
    invoice_numbering_series_id:
        props.defaults?.invoice_numbering_series_id ?? (null as number | null),
    notes: props.mode === 'edit' ? (props.invoice!.notes ?? '') : '',
    subject: props.mode === 'edit' ? (props.invoice?.subject ?? '') : '',
    order_number:
        props.mode === 'edit' ? (props.invoice?.order_number ?? '') : '',
    terms: props.mode === 'edit' ? (props.invoice?.terms ?? '') : '',
    // Remembered recipient list for this document: the customer's contacts
    // (checked/unchecked) plus any ad-hoc extras. Seeded from the invoice on
    // edit, or from the selected customer's contacts on create.
    email_recipients: mergeRecipients(
        selectedCustomer.value?.emails ?? [],
        props.mode === 'edit' ? (props.invoice?.email_recipients ?? []) : [],
    ) as EmailRecipient[],
    place_of_supply:
        props.mode === 'edit' ? (props.invoice?.place_of_supply ?? '') : '',
    tds_rate:
        props.mode === 'edit'
            ? (props.invoice?.tds_rate ?? null)
            : (null as number | null),
    tds_section:
        props.mode === 'edit' ? (props.invoice?.tds_section ?? '') : '',
    tcs_rate:
        props.mode === 'edit'
            ? (props.invoice?.tcs_rate ?? null)
            : (null as number | null),
    tcs_section:
        props.mode === 'edit' ? (props.invoice?.tcs_section ?? '') : '',
    items: [] as LineItem[],
    // Newly picked files (uploaded to the invoice on save) and ids of existing
    // attachments the user removed (deleted on save).
    attachments: [] as File[],
    removed_attachment_ids: [] as number[],
});

// Existing invoice attachments still visible (i.e. not marked for removal).
const visibleAttachments = computed(() =>
    (props.attachments ?? []).filter(
        (a) => !form.removed_attachment_ids.includes(a.id),
    ),
);

const selectedOrgLocation = computed(
    () =>
        props.organizationLocations.find(
            (l) => l.id === form.organization_location_id,
        ) ?? null,
);

// The supplier's GST state code — first two digits of the org GSTIN, only for
// Indian locations. Drives the intra- vs inter-state CGST/SGST/IGST split.
const supplierStateCode = computed<string | null>(() => {
    const loc = selectedOrgLocation.value;

    if (!loc || loc.country !== 'IN' || !loc.tax_id || loc.tax_id.length < 2) {
        return null;
    }

    return loc.tax_id.slice(0, 2);
});

// A customer billed outside India is an export → inter-state → IGST.
const isForeignCustomer = computed(() => {
    const loc = customerLocations.value.find(
        (l) => l.id === form.customer_location_id,
    );

    return loc != null && loc.country !== 'IN';
});

// Live CGST/SGST/UTGST/IGST split for the totals panel. Empty for non-GST
// supplies, in which case the single aggregate tax line is shown instead.
const gstBreakdown = computed(() =>
    buildGstBreakdown({
        items: items.value,
        supplierStateCode: supplierStateCode.value,
        placeOfSupplyCode: form.place_of_supply || null,
        isForeignCustomer: isForeignCustomer.value,
    }),
);

// TDS/TCS (India source taxes): TDS is deducted from the amount payable, TCS is
// added on top. Only offered to Indian organizations.
const showSourceTaxes = computed(() => showPlaceOfSupply.value);

const tdsAmount = computed(() =>
    calcTdsAmount(totals.value.subtotal, form.tds_rate ?? 0),
);

const tcsAmount = computed(() =>
    calcTcsAmount(totals.value.subtotal, totals.value.tax, form.tcs_rate ?? 0),
);

const netTotal = computed(
    () => totals.value.total + tcsAmount.value - tdsAmount.value,
);

function sourceTaxLabel(
    type: 'TDS' | 'TCS',
    rate: number | null,
    section: string,
): string {
    const pct = parseFloat(((rate ?? 0) / 100).toPrecision(6));
    const base = `${type} @ ${pct}%`;

    return section ? `${base} (${section})` : base;
}

const tdsLabel = computed(() =>
    sourceTaxLabel('TDS', form.tds_rate, form.tds_section),
);
const tcsLabel = computed(() =>
    sourceTaxLabel('TCS', form.tcs_rate, form.tcs_section),
);

// A percentage rate stored as basis points (1000 = 10%). Empty clears it.
function rateAsPercent(rate: number | null): string {
    return rate == null ? '' : String(rate / 100);
}

function onSourceRateInput(field: 'tds_rate' | 'tcs_rate', value: string) {
    const trimmed = value.trim();
    form[field] = trimmed === '' ? null : Math.round(Number(trimmed) * 100);
}

function removeExistingAttachment(id: number) {
    if (!form.removed_attachment_ids.includes(id)) {
        form.removed_attachment_ids.push(id);
    }
}

// Reflect the server's status when it changes the invoice (e.g. draft -> sent
// after an email is sent). The backend is the source of truth; we only mirror
// the returned prop, never decide the status on the client.
watch(
    () => props.invoice?.status,
    (serverStatus) => {
        if (props.mode === 'edit' && serverStatus) {
            form.status = serverStatus;
        }
    },
);

// Sending from the email modal remembers its recipients on the invoice; mirror
// that back into the form so a later save doesn't clobber the last-sent list.
watch(
    () => props.invoice?.email_recipients,
    (serverRecipients) => {
        if (props.mode === 'edit') {
            form.email_recipients = mergeRecipients(
                customerContacts.value,
                serverRecipients ?? [],
            );
        }
    },
);

// Payment terms: a UI helper that keeps the issue date, term, and due date in
// sync.
const paymentTerm = ref<PaymentTerm>(
    termFromDates(form.issued_at, form.due_at),
);

// Guard against watcher feedback: when we set a date/term programmatically we
// don't want the reciprocal watcher to fight it.
let syncingTerm = false;

// Term picked (a preset) -> recompute the due date. "Custom" leaves it as-is.
watch(paymentTerm, (term) => {
    if (syncingTerm || term === CUSTOM_TERM || !form.issued_at) {
        return;
    }

    const due = dueFromTerm(form.issued_at, term);

    if (due) {
        syncingTerm = true;
        form.due_at = due;
        syncingTerm = false;
    }
});

// Issue date changed -> if a preset term is active, shift the due date with it.
watch(
    () => form.issued_at,
    () => {
        if (syncingTerm || paymentTerm.value === CUSTOM_TERM) {
            return;
        }

        const due = dueFromTerm(form.issued_at, paymentTerm.value);

        if (due) {
            syncingTerm = true;
            form.due_at = due;
            syncingTerm = false;
        }
    },
);

// Due date edited directly -> snap the term to the matching preset or "Custom".
watch(
    () => form.due_at,
    () => {
        if (syncingTerm) {
            return;
        }

        syncingTerm = true;
        paymentTerm.value = termFromDates(form.issued_at, form.due_at);
        syncingTerm = false;
    },
);

// Sync items into form before submit
function syncItems() {
    form.items = items.value.map((item) => ({
        ...item,
        tax_rate: item.tax_rate ?? 0,
    }));
}

// Customer change: auto-select primary location
watch(selectedCustomerId, (newId) => {
    form.customer_id = newId;

    if (!newId) {
        form.customer_location_id = null;
        form.customer_shipping_location_id = null;
        form.email_recipients = [];

        return;
    }

    const customer = props.customers.find((c) => c.id === newId);

    if (customer) {
        const primaryId = customer.primary_location_id;
        const locs = customer.locations ?? [];
        const fallback = primaryId ?? locs[0]?.id ?? null;
        form.customer_location_id = fallback;
        form.customer_shipping_location_id = fallback;
        // Default the place of supply from the chosen customer. Editable here;
        // it never writes back to the customer.
        form.place_of_supply = customer.place_of_supply ?? '';
        // Switching customers resets recipients to the new customer's contacts
        // (all selected); any extras from the previous customer are dropped.
        form.email_recipients = mergeRecipients(customer.emails ?? [], []);
    }
});

function addItem() {
    items.value.push({
        description: '',
        sac_code: null,
        quantity: 1,
        unit_price: 0,
        tax_rate: 0,
    });
    itemKeys.value.push(nextItemKey++);
}

const confirmingItemRemove = ref(false);
const itemRemoveIndex = ref<number | null>(null);

// Ask before discarding a line item the user has actually filled in. Empty
// rows carry no work, so remove those immediately to keep the form snappy.
function requestRemoveItem(index: number) {
    if (items.value.length <= 1) {
        return;
    }

    const item = items.value[index];
    const hasContent =
        !!item.description?.trim() ||
        !!item.sac_code?.trim() ||
        item.unit_price > 0;

    if (!hasContent) {
        removeItem(index);

        return;
    }

    itemRemoveIndex.value = index;
    confirmingItemRemove.value = true;
}

function confirmRemoveItem() {
    if (itemRemoveIndex.value !== null) {
        removeItem(itemRemoveIndex.value);
    }

    confirmingItemRemove.value = false;
    itemRemoveIndex.value = null;
}

function removeItem(index: number) {
    if (items.value.length > 1) {
        items.value.splice(index, 1);
        itemKeys.value.splice(index, 1);
    }
}

function updateItem(index: number, item: LineItem) {
    items.value[index] = item;
}

// Save the document, optionally continuing to the email composer afterwards.
// "Save & Send" (send=true) persists the current edits first — like Zoho — so
// the email always reflects what's on screen, never a stale saved copy.
function submit(send = false) {
    syncItems();

    const options = {
        preserveScroll: true,
        // In edit mode a save stays on this page, so we can open the composer
        // as soon as the server confirms. In create mode the store redirects to
        // the edit page with ?send=1, which reopens it there (see onMounted).
        onSuccess: () => {
            if (send && props.mode === 'edit') {
                showEmailModal.value = true;
            }
        },
    };

    if (props.mode === 'create') {
        form.post(send ? '/invoices?send=1' : '/invoices', options);
    } else {
        form.put(`/invoices/${props.invoice!.id}`, options);
    }
}

function save() {
    submit(false);
}

function saveAndSend() {
    submit(true);
}

// A create's "Save & Send" lands here after the store redirect appends ?send=1.
// Open the composer, then strip the flag so a refresh doesn't reopen it.
onMounted(() => {
    if (props.mode !== 'edit' || typeof window === 'undefined') {
        return;
    }

    const params = new URLSearchParams(window.location.search);

    if (params.get('send') === '1') {
        showEmailModal.value = true;
        params.delete('send');
        const query = params.toString();
        window.history.replaceState(
            window.history.state,
            '',
            window.location.pathname + (query ? `?${query}` : ''),
        );
    }
});

function cancel() {
    router.get('/invoices');
}

const selectedBillingLocation = computed(() =>
    customerLocations.value.find((l) => l.id === form.customer_location_id),
);

const selectedShippingLocation = computed(() =>
    customerLocations.value.find(
        (l) => l.id === form.customer_shipping_location_id,
    ),
);

// "Same as billing" toggle for the shipping address. Defaults to enabled when
// shipping already matches billing (always the case in create mode after the
// customer auto-fill, and in edit mode when no separate shipping was chosen).
const sameAsBilling = ref(
    form.customer_shipping_location_id === form.customer_location_id,
);

// While enabled, mirror shipping to billing so any billing change (or customer
// switch) is reflected in the shipping address too.
watch(sameAsBilling, (enabled) => {
    if (enabled) {
        form.customer_shipping_location_id = form.customer_location_id;
    }
});

watch(
    () => form.customer_location_id,
    (billingId) => {
        if (sameAsBilling.value) {
            form.customer_shipping_location_id = billingId;
        }
    },
);

const page = usePage();
const flash = computed(() => (page.props as any).flash ?? {});

// Organization contacts, offered as suggestions in the recipients editor.
const orgContacts = computed<Contact[]>(
    () => (page.props as any).auth?.currentTeam?.emails ?? [],
);
</script>

<template>
    <div class="px-4 py-6 sm:px-0">
        <!-- Action buttons (edit mode only) -->
        <div
            v-if="mode === 'edit' && invoice?.ulid"
            class="mb-6 flex justify-end"
        >
            <div class="flex space-x-2">
                <button
                    type="button"
                    :disabled="form.processing"
                    class="cursor-pointer rounded bg-purple-500 px-4 py-2 font-bold text-white hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="saveAndSend"
                >
                    Save &amp; Send
                </button>
                <a
                    :href="`/${invoice.type === 'invoice' ? 'invoices' : 'estimates'}/view/${invoice.ulid}`"
                    target="_blank"
                    class="rounded bg-green-500 px-4 py-2 font-bold text-white hover:bg-green-700"
                >
                    View Public
                </a>
                <a
                    :href="`/${invoice.type === 'invoice' ? 'invoices' : 'estimates'}/${invoice.ulid}/pdf`"
                    class="inline-flex items-center gap-2 rounded bg-red-500 px-4 py-2 font-bold text-white hover:bg-red-700"
                    @click="downloadingPdf = true"
                >
                    <svg
                        v-if="downloadingPdf"
                        class="h-4 w-4 animate-spin"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                        ></path>
                    </svg>
                    {{ downloadingPdf ? 'Generating...' : 'Download PDF' }}
                </a>
            </div>
        </div>

        <!-- Flash message -->
        <div
            v-if="flash.success"
            class="mb-4 rounded border border-green-300 bg-green-100 p-4 text-green-700"
        >
            {{ flash.success }}
        </div>
        <div
            v-if="flash.error"
            class="mb-4 rounded border border-red-300 bg-red-100 p-4 text-red-700"
        >
            {{ flash.error }}
        </div>

        <form @submit.prevent="save" class="space-y-6">
            <!-- Organization Location Section -->
            <div class="rounded-lg bg-white p-6 shadow">
                <h2 class="mb-4 text-lg font-semibold text-gray-800">
                    Organization Location
                </h2>
                <div v-if="organizationLocations.length > 0" class="max-w-md">
                    <label class="mb-1 block text-sm font-medium text-gray-700"
                        >Location *</label
                    >
                    <select
                        v-model="form.organization_location_id"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                    >
                        <option :value="null">Select location</option>
                        <option
                            v-for="loc in organizationLocations"
                            :key="loc.id"
                            :value="loc.id"
                        >
                            {{ loc.name }} - {{ loc.city }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.organization_location_id"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.organization_location_id }}
                    </p>
                </div>
                <div
                    v-else
                    class="max-w-md rounded-md border border-yellow-200 bg-yellow-50 p-3"
                >
                    <p class="text-sm text-yellow-700">
                        No locations found.
                        <a
                            href="/organizations"
                            class="font-medium underline"
                            target="_blank"
                            >Add a location</a
                        >
                    </p>
                </div>
            </div>

            <!-- Customer & Address Section -->
            <div class="rounded-lg bg-white p-6 shadow">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Left: Customer & Billing -->
                    <div class="space-y-4">
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Customer *</label
                            >
                            <select
                                v-model="selectedCustomerId"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                            >
                                <option :value="null">Select customer</option>
                                <option
                                    v-for="customer in customers"
                                    :key="customer.id"
                                    :value="customer.id"
                                >
                                    {{ customer.name
                                    }}{{
                                        customer.deleted_at ? ' (Archived)' : ''
                                    }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.customer_id"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.customer_id }}
                            </p>

                            <div
                                v-if="customers.length === 0"
                                class="mt-2 rounded-md border border-brand-200 bg-brand-50 p-3"
                            >
                                <p class="text-sm text-brand-700">
                                    No customers found.
                                    <a
                                        href="/customers"
                                        class="font-medium underline"
                                        target="_blank"
                                        >Create your first customer</a
                                    >
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="
                                selectedCustomerId &&
                                customerLocations.length > 0
                            "
                            class="rounded-md border border-gray-200 bg-gray-50 p-4"
                        >
                            <h3
                                class="mb-2 text-sm font-semibold text-gray-700"
                            >
                                Billing Address
                            </h3>
                            <select
                                v-model="form.customer_location_id"
                                class="mb-3 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                            >
                                <option :value="null">Select location</option>
                                <option
                                    v-for="loc in customerLocations"
                                    :key="loc.id"
                                    :value="loc.id"
                                >
                                    {{ loc.name }} - {{ loc.city
                                    }}{{
                                        selectedCustomer?.primary_location_id ===
                                        loc.id
                                            ? ' (Primary)'
                                            : ''
                                    }}{{ loc.deleted_at ? ' (Archived)' : '' }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.customer_location_id"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.customer_location_id }}
                            </p>

                            <div
                                v-if="selectedBillingLocation"
                                class="space-y-1 text-sm text-gray-600"
                            >
                                <p
                                    v-if="
                                        selectedBillingLocation.address_line_1
                                    "
                                >
                                    {{ selectedBillingLocation.address_line_1 }}
                                </p>
                                <p
                                    v-if="
                                        selectedBillingLocation.address_line_2
                                    "
                                >
                                    {{ selectedBillingLocation.address_line_2 }}
                                </p>
                                <p>
                                    {{ selectedBillingLocation.city }}
                                    <span v-if="selectedBillingLocation.state"
                                        >,
                                        {{
                                            selectedBillingLocation.state
                                        }}</span
                                    >
                                    <span
                                        v-if="
                                            selectedBillingLocation.postal_code
                                        "
                                    >
                                        -
                                        {{
                                            selectedBillingLocation.postal_code
                                        }}</span
                                    >
                                </p>
                                <p v-if="selectedBillingLocation.country">
                                    {{ selectedBillingLocation.country }}
                                </p>
                                <p
                                    v-if="selectedBillingLocation.tax_id"
                                    class="mt-1 font-medium text-gray-700"
                                >
                                    <span class="text-gray-500"
                                        >{{
                                            taxLabelFor(
                                                selectedBillingLocation.country,
                                                taxLabels,
                                            )
                                        }}:</span
                                    >
                                    {{ selectedBillingLocation.tax_id }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Shipping -->
                    <div class="space-y-4">
                        <!-- Place of Supply (GST organizations only) -->
                        <div v-if="showPlaceOfSupply">
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Place of Supply</label
                            >
                            <select
                                v-model="form.place_of_supply"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                            >
                                <option value="">Select a state…</option>
                                <option
                                    v-for="(label, value) in indianStates"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.place_of_supply"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.place_of_supply }}
                            </p>
                        </div>

                        <div
                            v-if="
                                selectedCustomerId &&
                                customerLocations.length > 0
                            "
                            class="rounded-md border border-gray-200 bg-gray-50 p-4"
                            :class="{ 'mt-20': !showPlaceOfSupply }"
                        >
                            <div class="mb-2 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-700">
                                    Shipping Address
                                </h3>
                                <label
                                    class="flex cursor-pointer items-center gap-2 text-sm text-gray-600"
                                >
                                    <input
                                        v-model="sameAsBilling"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                                    />
                                    Same as billing address
                                </label>
                            </div>
                            <p
                                v-if="sameAsBilling"
                                class="text-sm text-gray-500"
                            >
                                Shipping address is the same as the billing
                                address.
                            </p>
                            <select
                                v-if="!sameAsBilling"
                                v-model="form.customer_shipping_location_id"
                                class="mb-3 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                            >
                                <option :value="null">Select location</option>
                                <option
                                    v-for="loc in customerLocations"
                                    :key="loc.id"
                                    :value="loc.id"
                                >
                                    {{ loc.name }} - {{ loc.city
                                    }}{{
                                        selectedCustomer?.primary_location_id ===
                                        loc.id
                                            ? ' (Primary)'
                                            : ''
                                    }}{{ loc.deleted_at ? ' (Archived)' : '' }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.customer_shipping_location_id"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.customer_shipping_location_id }}
                            </p>

                            <div
                                v-if="
                                    selectedShippingLocation && !sameAsBilling
                                "
                                class="space-y-1 text-sm text-gray-600"
                            >
                                <p
                                    v-if="
                                        selectedShippingLocation.address_line_1
                                    "
                                >
                                    {{
                                        selectedShippingLocation.address_line_1
                                    }}
                                </p>
                                <p
                                    v-if="
                                        selectedShippingLocation.address_line_2
                                    "
                                >
                                    {{
                                        selectedShippingLocation.address_line_2
                                    }}
                                </p>
                                <p>
                                    {{ selectedShippingLocation.city }}
                                    <span v-if="selectedShippingLocation.state"
                                        >,
                                        {{
                                            selectedShippingLocation.state
                                        }}</span
                                    >
                                    <span
                                        v-if="
                                            selectedShippingLocation.postal_code
                                        "
                                    >
                                        -
                                        {{
                                            selectedShippingLocation.postal_code
                                        }}</span
                                    >
                                </p>
                                <p v-if="selectedShippingLocation.country">
                                    {{ selectedShippingLocation.country }}
                                </p>
                                <p
                                    v-if="selectedShippingLocation.tax_id"
                                    class="mt-1 font-medium text-gray-700"
                                >
                                    <span class="text-gray-500"
                                        >{{
                                            taxLabelFor(
                                                selectedShippingLocation.country,
                                                taxLabels,
                                            )
                                        }}:</span
                                    >
                                    {{ selectedShippingLocation.tax_id }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Details Section -->
            <div class="rounded-lg bg-white p-6 shadow">
                <h2 class="mb-4 text-lg font-semibold text-gray-800">
                    Invoice Details
                </h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700"
                            >Document Type</label
                        >
                        <select
                            v-model="form.type"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                            :disabled="mode === 'edit'"
                        >
                            <option value="invoice">Invoice</option>
                            <option value="estimate">Estimate</option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700"
                            >Status *</label
                        >
                        <select
                            v-model="form.status"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                        >
                            <option
                                v-for="(label, value) in statusOptions"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.status"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.status }}
                        </p>
                    </div>

                    <div
                        v-if="
                            type === 'invoice' &&
                            numberingSeries.length > 0 &&
                            mode === 'create'
                        "
                    >
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700"
                            >Numbering Series</label
                        >
                        <select
                            v-model="form.invoice_numbering_series_id"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                        >
                            <option :value="null">Auto-select</option>
                            <option
                                v-for="series in numberingSeries"
                                :key="series.id"
                                :value="series.id"
                            >
                                {{ series.name }}
                                {{
                                    series.location
                                        ? `(${series.location.name})`
                                        : '(Org-wide)'
                                }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.invoice_numbering_series_id"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.invoice_numbering_series_id }}
                        </p>
                    </div>
                </div>

                <!-- Issue date, payment terms and due date are interdependent,
                     so they share one row on md+ screens (single column below). -->
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700"
                            >Issue Date *</label
                        >
                        <input
                            v-model="form.issued_at"
                            type="date"
                            required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                        />
                        <p
                            v-if="form.errors.issued_at"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.issued_at }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700"
                            >Payment Terms *</label
                        >
                        <select
                            v-model="paymentTerm"
                            required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                        >
                            <option
                                v-for="option in PAYMENT_TERM_OPTIONS"
                                :key="String(option.value)"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700"
                            >Due Date *</label
                        >
                        <input
                            v-model="form.due_at"
                            type="date"
                            required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                        />
                        <p
                            v-if="form.errors.due_at"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.due_at }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700"
                            >Order Number</label
                        >
                        <input
                            v-model="form.order_number"
                            type="text"
                            placeholder="Customer's purchase order number"
                            maxlength="255"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                        />
                        <p
                            v-if="form.errors.order_number"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.order_number }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Items Section -->
            <div class="rounded-lg bg-white p-6 shadow">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-800">
                        Line Items
                    </h2>
                    <button
                        type="button"
                        class="rounded bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600"
                        @click="addItem"
                    >
                        Add Item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Description *
                                </th>
                                <th
                                    class="w-24 px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Qty *
                                </th>
                                <th
                                    class="w-36 px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Unit Price
                                </th>
                                <th
                                    class="w-32 px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Tax Rate
                                </th>
                                <th
                                    class="w-32 px-4 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Amount
                                </th>
                                <th
                                    class="w-16 px-4 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <ItemRow
                                v-for="(item, index) in items"
                                :key="itemKeys[index]"
                                :item="item"
                                :index="index"
                                :currency="currentCurrency"
                                :tax-options="taxOptions"
                                :can-remove="items.length > 1"
                                :errors="form.errors"
                                @update="updateItem(index, $event)"
                                @remove="requestRemoveItem(index)"
                            />
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="mt-6 flex justify-end">
                    <div class="w-full space-y-2 md:w-1/3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-medium text-gray-900">{{
                                formatMoney(totals.subtotal, currentCurrency)
                            }}</span>
                        </div>
                        <template v-if="gstBreakdown.length > 0">
                            <div
                                v-for="row in gstBreakdown"
                                :key="row.label"
                                class="flex justify-between text-sm"
                            >
                                <span class="text-gray-600"
                                    >{{ row.label }}:</span
                                >
                                <span class="font-medium text-gray-900">{{
                                    formatMoney(row.amount, currentCurrency)
                                }}</span>
                            </div>
                        </template>
                        <div v-else class="flex justify-between text-sm">
                            <span class="text-gray-600">Tax:</span>
                            <span class="font-medium text-gray-900">{{
                                formatMoney(totals.tax, currentCurrency)
                            }}</span>
                        </div>
                        <div
                            v-if="tcsAmount > 0"
                            class="flex justify-between text-sm"
                        >
                            <span class="text-gray-600">{{ tcsLabel }}:</span>
                            <span class="font-medium text-gray-900"
                                >+
                                {{
                                    formatMoney(tcsAmount, currentCurrency)
                                }}</span
                            >
                        </div>
                        <div
                            v-if="tdsAmount > 0"
                            class="flex justify-between text-sm"
                        >
                            <span class="text-gray-600">{{ tdsLabel }}:</span>
                            <span class="font-medium text-gray-900"
                                >&minus;
                                {{
                                    formatMoney(tdsAmount, currentCurrency)
                                }}</span
                            >
                        </div>
                        <div
                            class="flex justify-between border-t border-gray-300 pt-2"
                        >
                            <span class="text-lg font-bold text-gray-900"
                                >Total:</span
                            >
                            <span class="text-lg font-bold text-brand-600">{{
                                formatMoney(netTotal, currentCurrency)
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TDS / TCS Section (Indian organizations only) -->
            <div v-if="showSourceTaxes" class="rounded-lg bg-white p-6 shadow">
                <h2 class="mb-1 text-lg font-semibold text-gray-800">
                    TDS / TCS
                </h2>
                <p class="mb-4 text-sm text-gray-500">
                    Tax deducted or collected at source. TDS is subtracted from
                    the total; TCS is added. Leave blank if not applicable.
                </p>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div
                        class="space-y-3 rounded-md border border-gray-200 p-4"
                    >
                        <h3 class="text-sm font-semibold text-gray-700">
                            TDS (deducted)
                        </h3>
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-gray-600"
                                >Rate (%)</label
                            >
                            <input
                                :value="rateAsPercent(form.tds_rate)"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                placeholder="e.g. 10"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                                @input="
                                    onSourceRateInput(
                                        'tds_rate',
                                        ($event.target as HTMLInputElement)
                                            .value,
                                    )
                                "
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-gray-600"
                                >Section</label
                            >
                            <input
                                v-model="form.tds_section"
                                type="text"
                                maxlength="20"
                                placeholder="e.g. 194J"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div
                        class="space-y-3 rounded-md border border-gray-200 p-4"
                    >
                        <h3 class="text-sm font-semibold text-gray-700">
                            TCS (collected)
                        </h3>
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-gray-600"
                                >Rate (%)</label
                            >
                            <input
                                :value="rateAsPercent(form.tcs_rate)"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                placeholder="e.g. 0.1"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                                @input="
                                    onSourceRateInput(
                                        'tcs_rate',
                                        ($event.target as HTMLInputElement)
                                            .value,
                                    )
                                "
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-gray-600"
                                >Section</label
                            >
                            <input
                                v-model="form.tcs_section"
                                type="text"
                                maxlength="20"
                                placeholder="e.g. 206C(1H)"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subject Section -->
            <div class="rounded-lg bg-white p-6 shadow">
                <label class="mb-2 block text-sm font-medium text-gray-700"
                    >Subject</label
                >
                <input
                    v-model="form.subject"
                    type="text"
                    placeholder="e.g. Development work for April"
                    maxlength="255"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                />
                <p v-if="form.errors.subject" class="mt-1 text-sm text-red-600">
                    {{ form.errors.subject }}
                </p>
            </div>

            <!-- Notes Section -->
            <div class="rounded-lg bg-white p-6 shadow">
                <label class="mb-2 block text-sm font-medium text-gray-700"
                    >Customer Notes</label
                >
                <textarea
                    v-model="form.notes"
                    rows="1"
                    placeholder="Enter notes for the customer..."
                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                />
                <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
                    {{ form.errors.notes }}
                </p>
                <p class="mt-1 text-xs text-gray-500">
                    Displayed on the PDF document.
                </p>
            </div>

            <!-- Terms & Conditions Section -->
            <div class="rounded-lg bg-white p-6 shadow">
                <label class="mb-2 block text-sm font-medium text-gray-700"
                    >Terms &amp; Conditions</label
                >
                <textarea
                    v-model="form.terms"
                    rows="2"
                    placeholder="e.g. Payment due within 7 days..."
                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                />
                <p v-if="form.errors.terms" class="mt-1 text-sm text-red-600">
                    {{ form.errors.terms }}
                </p>
            </div>

            <!-- Email Recipients Section -->
            <div
                v-if="selectedCustomerId"
                class="rounded-lg bg-white p-6 shadow"
            >
                <label class="mb-2 block text-sm font-medium text-gray-700"
                    >Email Recipients</label
                >
                <p class="mb-3 text-xs text-gray-500">
                    Selected contacts are pre-filled when you email this
                    {{ type === 'estimate' ? 'estimate' : 'invoice' }}. Uncheck
                    to skip a contact, or add extra addresses just for this
                    {{ type === 'estimate' ? 'estimate' : 'invoice' }}.
                </p>
                <RecipientEditor
                    v-model="form.email_recipients"
                    :contacts="customerContacts"
                    :suggestions="orgContacts"
                />
                <p
                    v-if="form.errors.email_recipients"
                    class="mt-1 text-sm text-red-600"
                >
                    {{ form.errors.email_recipients }}
                </p>
            </div>

            <!-- Attachments Section -->
            <div class="rounded-lg bg-white p-6 shadow">
                <label class="mb-2 block text-sm font-medium text-gray-700"
                    >Attachments</label
                >
                <p class="mb-3 text-xs text-gray-500">
                    Files saved with this
                    {{ type === 'estimate' ? 'estimate' : 'invoice' }}. They can
                    be attached when emailing it.
                </p>

                <!-- Existing (saved) attachments -->
                <ul v-if="visibleAttachments.length > 0" class="mb-3 space-y-1">
                    <li
                        v-for="attachment in visibleAttachments"
                        :key="attachment.id"
                        class="flex items-center justify-between rounded-md border border-gray-200 bg-gray-50 px-3 py-2"
                    >
                        <div class="min-w-0 flex-1">
                            <a
                                :href="attachment.url"
                                target="_blank"
                                rel="noopener"
                                class="truncate text-sm font-medium text-brand-600 hover:text-brand-800 hover:underline"
                            >
                                {{ attachment.name }}
                            </a>
                            <div class="text-xs text-gray-500">
                                {{ formatBytes(attachment.size) }}
                            </div>
                        </div>
                        <button
                            type="button"
                            class="ml-2 text-gray-400 hover:text-red-600"
                            aria-label="Remove attachment"
                            @click="removeExistingAttachment(attachment.id)"
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

                <!-- New files (uploaded on save) -->
                <AttachmentDropzone v-model="form.attachments" />

                <p
                    v-if="form.errors.attachments"
                    class="mt-1 text-sm text-red-600"
                >
                    {{ form.errors.attachments }}
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-4">
                <button
                    type="button"
                    class="rounded-md border border-gray-300 px-6 py-2 font-medium text-gray-700 hover:bg-gray-50"
                    @click="cancel"
                >
                    Cancel
                </button>
                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="cursor-pointer rounded-md border border-brand-600 px-6 py-2 font-medium text-brand-700 hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : 'Save' }}
                    </button>
                    <button
                        type="button"
                        :disabled="form.processing"
                        class="cursor-pointer rounded-md bg-brand-600 px-6 py-2 font-medium text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="saveAndSend"
                    >
                        {{ form.processing ? 'Saving...' : 'Save & Send' }}
                    </button>
                </div>
            </div>
        </form>

        <!-- Email Modal -->
        <EmailModal
            v-if="mode === 'edit' && invoice"
            :show="showEmailModal"
            :invoice="invoice"
            :customers="customers"
            :invoice-attachments="attachments ?? []"
            @close="showEmailModal = false"
        />

        <!-- Remove Line Item Confirmation -->
        <ConfirmationModal
            :show="confirmingItemRemove"
            title="Remove Line Item"
            message="Are you sure you want to remove this line item?"
            confirm-label="Remove"
            :destructive="true"
            @confirm="confirmRemoveItem"
            @cancel="
                confirmingItemRemove = false;
                itemRemoveIndex = null;
            "
        />
    </div>
</template>
