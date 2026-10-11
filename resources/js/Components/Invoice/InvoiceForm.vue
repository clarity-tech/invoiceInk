<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, provide, watch, onMounted } from 'vue';
import { ChevronDown, Plus, X } from 'lucide-vue-next';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import { Button } from '@/Components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { Checkbox } from '@/Components/ui/checkbox';
import { Field } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select } from '@/Components/ui/select';
import { Separator } from '@/Components/ui/separator';
import { Textarea } from '@/Components/ui/textarea';
import AddressLines from './AddressLines.vue';
import DetailsCard from './Form/DetailsCard.vue';
import PartiesCard from './Form/PartiesCard.vue';
import ExtrasCard from './Form/ExtrasCard.vue';
import { InvoiceFormKey } from './Form/formContext';
import SourceTaxesCard from './Form/SourceTaxesCard.vue';
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
    InvoiceFormDefaults,
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
    defaults?: InvoiceFormDefaults;
}>();

const { formatMoney } = useFormatMoney();
const showEmailModal = ref(false);
// Build initial items — from the invoice being edited, or from the defaults
// when the create form was seeded by "Duplicate".
function buildInitialItems(): LineItem[] {
    const source =
        props.mode === 'edit' ? props.invoice?.items : props.defaults?.items;

    if (source?.length) {
        return source.map((item) => ({
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
    props.mode === 'edit'
        ? (props.invoice?.customer_id ?? null)
        : (props.defaults?.customer_id ?? null),
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
            : (props.defaults?.customer_location_id ?? null),
    customer_shipping_location_id:
        props.mode === 'edit'
            ? (props.invoice!.customer_shipping_location?.id ?? null)
            : (props.defaults?.customer_shipping_location_id ?? null),
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
    notes:
        props.mode === 'edit'
            ? (props.invoice!.notes ?? '')
            : (props.defaults?.notes ?? ''),
    subject:
        props.mode === 'edit'
            ? (props.invoice?.subject ?? '')
            : (props.defaults?.subject ?? ''),
    order_number:
        props.mode === 'edit'
            ? (props.invoice?.order_number ?? '')
            : (props.defaults?.order_number ?? ''),
    terms:
        props.mode === 'edit'
            ? (props.invoice?.terms ?? '')
            : (props.defaults?.terms ?? ''),
    // Remembered recipient list for this document: the customer's contacts
    // (checked/unchecked) plus any ad-hoc extras. Seeded from the invoice on
    // edit, or from the selected customer's contacts on create.
    email_recipients: mergeRecipients(
        selectedCustomer.value?.emails ?? [],
        props.mode === 'edit'
            ? (props.invoice?.email_recipients ?? [])
            : (props.defaults?.email_recipients ?? []),
    ) as EmailRecipient[],
    place_of_supply:
        props.mode === 'edit'
            ? (props.invoice?.place_of_supply ?? '')
            : (props.defaults?.place_of_supply ?? ''),
    tds_rate:
        props.mode === 'edit'
            ? (props.invoice?.tds_rate ?? null)
            : (props.defaults?.tds_rate ?? null),
    tds_section:
        props.mode === 'edit'
            ? (props.invoice?.tds_section ?? '')
            : (props.defaults?.tds_section ?? ''),
    tcs_rate:
        props.mode === 'edit'
            ? (props.invoice?.tcs_rate ?? null)
            : (props.defaults?.tcs_rate ?? null),
    tcs_section:
        props.mode === 'edit'
            ? (props.invoice?.tcs_section ?? '')
            : (props.defaults?.tcs_section ?? ''),
    items: [] as LineItem[],
    // Newly picked files (uploaded to the invoice on save) and ids of existing
    // attachments the user removed (deleted on save).
    attachments: [] as File[],
    removed_attachment_ids: [] as number[],
});

// Shared with the section components below rather than passed as a prop; see
// Form/formContext.ts for why.
provide(InvoiceFormKey, form);

// Existing invoice attachments still visible (i.e. not marked for removal).
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

const orgCurrency = computed<string | null>(
    () => (page.props as any).auth?.currentTeam?.currency ?? null,
);

/**
 * Whether the document, as the form currently stands, needs converting for the
 * organization's reporting. Currency follows the selected customer, so this
 * can change mid-edit.
 */
const conversionApplies = computed(
    () =>
        props.mode === 'edit' &&
        !!props.invoice &&
        !!orgCurrency.value &&
        currentCurrency.value !== orgCurrency.value,
);

/**
 * Mirrors the rateInputsChanged check in InvoiceController::update(). The
 * server keeps the stored rate untouched unless the currency or the issue date
 * moved, and re-resolves it when they did -- so once either input moves, the
 * stored rate is no longer the number that will be saved.
 */
const rateInputsChanged = computed(() => {
    if (!props.invoice) {
        return false;
    }

    // The form seeds issued_at from the same value, date-only.
    const storedDate = props.invoice.issued_at?.split('T')[0] ?? null;

    return (
        currentCurrency.value !== props.invoice.currency ||
        storedDate === null ||
        form.issued_at !== storedDate
    );
});

/**
 * The rate saved on the document, resolved for its issue date. Every converted
 * figure in reporting derives from it, and it is otherwise invisible -- not on
 * the form, the public view or the PDF -- so a wrong total could not be
 * explained without a database query.
 *
 * Withheld once the inputs change, because at that point it is about to be
 * replaced and showing it would promise a number that will not be stored.
 */
const storedExchangeRate = computed(() => {
    if (!conversionApplies.value || rateInputsChanged.value) {
        return null;
    }

    const rate = Number(props.invoice!.exchange_rate);

    if (!Number.isFinite(rate) || rate <= 0) {
        return null;
    }

    return {
        from: props.invoice!.currency,
        to: orgCurrency.value,
        // Trimmed rather than fixed: 26.2 should not read as 26.200000.
        rate: parseFloat(rate.toPrecision(8)).toString(),
    };
});

// Said out loud rather than letting the saved rate silently vanish, which
// would leave the editor wondering what happened to it.
const exchangeRateWillChange = computed(
    () => conversionApplies.value && rateInputsChanged.value,
);

/**
 * Why the saved rate matters, which differs by document type. Dashboard
 * aggregates run through Invoice::invoices(), so an estimate's rate never
 * reaches reporting -- but EstimateToInvoiceConverter copies it onto the
 * invoice, so it is not inert either. Safe to read from props: the document
 * type select is disabled while editing, which is the only time this shows.
 */
const storedExchangeRateHint = computed(() =>
    props.type === 'estimate'
        ? 'Saved with this estimate and carried over to the invoice if it is converted.'
        : 'Saved with this document and used for every converted total in reporting.',
);

const flash = computed(() => (page.props as any).flash ?? {});

// Organization contacts, offered as suggestions in the recipients editor.
const orgContacts = computed<Contact[]>(
    () => (page.props as any).auth?.currentTeam?.emails ?? [],
);
</script>

<template>
    <!--
        No padding of its own. The page owns the gutter and the top and bottom
        margin, so the form sits on the same rhythm as whatever is above or
        below it -- the payment history on the edit page, for one, which used
        to be 44px away while the form's own cards were 24px apart.

        `space-y-5` here and on the form below is the same step the detail page
        uses between its cards, so the two pages scroll at the same pace.
    -->
    <div class="space-y-5">
        <!-- Flash message -->
        <div
            v-if="flash.success"
            class="rounded-xl border border-green-300 bg-green-100 p-4 text-green-700"
        >
            {{ flash.success }}
        </div>
        <div
            v-if="flash.error"
            class="rounded-xl border border-red-300 bg-red-100 p-4 text-red-700"
        >
            {{ flash.error }}
        </div>

        <!-- A validation error whose field is conditionally hidden (a customer
             with no location hides the whole billing block, for one) would
             otherwise leave the save button doing nothing at all. -->
        <div
            v-if="form.hasErrors"
            class="rounded-xl border border-red-300 bg-red-50 p-4"
            role="alert"
        >
            <p class="text-sm font-medium text-red-800">
                This {{ type === 'estimate' ? 'estimate' : 'invoice' }} could
                not be saved.
            </p>
            <ul
                class="mt-2 list-inside list-disc space-y-1 text-sm text-red-700"
            >
                <li v-for="(message, field) in form.errors" :key="field">
                    {{ message }}
                </li>
            </ul>
        </div>

        <form @submit.prevent="save" class="space-y-5">
            <PartiesCard
                v-model:selected-customer-id="selectedCustomerId"
                v-model:same-as-billing="sameAsBilling"
                :organization-locations="organizationLocations"
                :customers="customers"
                :customer-locations="customerLocations"
                :selected-customer="selectedCustomer"
                :show-place-of-supply="showPlaceOfSupply"
                :indian-states="indianStates"
                :tax-labels="taxLabels"
            />

            <DetailsCard
                v-model:payment-term="paymentTerm"
                :mode="mode"
                :type="type"
                :status-options="statusOptions"
                :numbering-series="numberingSeries"
                :stored-exchange-rate="storedExchangeRate"
                :stored-exchange-rate-hint="storedExchangeRateHint"
                :exchange-rate-will-change="exchangeRateWillChange"
            />

            <!-- Items Section -->
            <Card>
                <CardHeader>
                    <CardTitle>Line Items</CardTitle>
                    <CardAction>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="addItem"
                        >
                            <Plus />
                            Add Item
                        </Button>
                    </CardAction>
                </CardHeader>
                <!--
                    `px-0 py-0` so the table meets the card edge, the way the
                    detail page's items table does. Inset by the card's own
                    padding it sat a few pixels in from every other table in
                    the app, with its header stripe floating rather than
                    spanning.
                -->
                <CardContent class="px-0 py-0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted/60">
                                <tr>
                                    <th
                                        class="px-2 py-2 text-left text-xs font-medium text-muted-foreground"
                                    >
                                        Description *
                                    </th>
                                    <th
                                        class="w-20 py-2 pr-5 pl-2 text-right text-xs font-medium text-muted-foreground"
                                    >
                                        Qty *
                                    </th>
                                    <th
                                        class="w-32 py-2 pr-5 pl-2 text-right text-xs font-medium text-muted-foreground"
                                    >
                                        Unit Price
                                    </th>
                                    <th
                                        class="w-40 px-2 py-2 text-left text-xs font-medium text-muted-foreground"
                                    >
                                        Tax Rate
                                    </th>
                                    <th
                                        class="w-32 px-2 py-2 text-right text-xs font-medium text-muted-foreground"
                                    >
                                        Amount
                                    </th>
                                    <!--
                                        No heading. The column holds one
                                        control that is invisible until the row
                                        is pointed at, so a permanent "Action"
                                        label announced a column that usually
                                        shows nothing. The name stays for a
                                        screen reader, which has no hover.
                                    -->
                                    <th class="w-10 px-2 py-2">
                                        <span class="sr-only">Remove</span>
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

                    <!--
                        Totals, and the second way to add a line. The only
                        other one is in the card header, which on a document
                        with a dozen items is a scroll away from the row you
                        just finished typing -- so it shares the strip the
                        totals already occupy and costs no extra height.
                    -->
                    <div
                        class="flex flex-col gap-4 border-t border-border px-5 py-4 md:flex-row md:items-start md:justify-between"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="addItem"
                        >
                            <Plus />
                            Add Item
                        </Button>

                        <div class="w-full space-y-2 md:w-1/3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium text-gray-900">{{
                                    formatMoney(
                                        totals.subtotal,
                                        currentCurrency,
                                    )
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
                                <span class="text-gray-600"
                                    >{{ tcsLabel }}:</span
                                >
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
                                <span class="text-gray-600"
                                    >{{ tdsLabel }}:</span
                                >
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
                                <span
                                    class="text-lg font-bold text-brand-600"
                                    >{{
                                        formatMoney(netTotal, currentCurrency)
                                    }}</span
                                >
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!--
                TDS / TCS applies to a minority of documents but cost a full
                card of height on every Indian invoice, almost always empty.
                The card collapses itself; see SourceTaxesCard.
            -->
            <SourceTaxesCard v-if="showSourceTaxes" />

            <ExtrasCard
                :type="type"
                :show-recipients="Boolean(selectedCustomerId)"
                :contacts="customerContacts"
                :suggestions="orgContacts"
                :attachments="attachments ?? []"
            />

            <!--
                Sticky to the bottom of the viewport. This form still runs to
                roughly two and a half thousand pixels, and on the edit page the
                payment history sits below it, so a static bar meant scrolling
                past everything to save and left content stranded underneath the
                page's apparent end. `bottom-0` with a backdrop keeps Save
                reachable from any scroll position, and the bar settles into
                place at the end of the form once it is scrolled to.
            -->
            <div
                class="sticky bottom-0 z-10 flex items-center justify-between gap-3 rounded-xl border border-border bg-card px-4 py-3 shadow-[0_-2px_8px_rgba(0,0,0,0.06)]"
            >
                <Button type="button" variant="outline" @click="cancel">
                    Cancel
                </Button>
                <div class="flex items-center gap-3">
                    <Button
                        type="submit"
                        variant="outline"
                        class="border-primary text-primary hover:bg-primary/5"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Saving...' : 'Save' }}
                    </Button>
                    <Button
                        type="button"
                        :disabled="form.processing"
                        @click="saveAndSend"
                    >
                        {{ form.processing ? 'Saving...' : 'Save & Send' }}
                    </Button>
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
