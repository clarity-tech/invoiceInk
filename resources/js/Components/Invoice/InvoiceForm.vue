<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, provide, watch, onMounted } from 'vue';
import { ChevronDown, Loader2, X } from 'lucide-vue-next';
import { emailModalDialog } from '@/Components/lazyDialogs';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Field } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select } from '@/Components/ui/select';
import { Separator } from '@/Components/ui/separator';
import { Textarea } from '@/Components/ui/textarea';
import AddressLines from './AddressLines.vue';
import DetailsCard from './Form/DetailsCard.vue';
import ItemsCard from './Form/ItemsCard.vue';
import PartiesCard from './Form/PartiesCard.vue';
import ExtrasCard from './Form/ExtrasCard.vue';
import { DocumentFormKey, InvoiceFormKey } from './Form/formContext';
import SourceTaxesCard from './Form/SourceTaxesCard.vue';
import AttachmentDropzone from './AttachmentDropzone.vue';
import RecipientEditor from './RecipientEditor.vue';
import { mergeRecipients } from '@/composables/useRecipients';
import { useDocumentParties } from '@/composables/useDocumentParties';
import { useLazyDialog } from '@/composables/useLazyDialog';
import type { LineItem } from '@/composables/useInvoiceCalculator';
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

const showEmailModal = ref(false);
// The composer carries the email editor, so it loads on first use; pressing
// Save & Send starts fetching it while the save is still in flight.
const EmailModal = emailModalDialog.component;
const emailDialog = useLazyDialog(emailModalDialog);

function openEmailModal() {
    emailDialog.open(() => {
        showEmailModal.value = true;
    });
}
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

// Edited by ItemsCard and copied into the form on submit; see syncItems.
const items = ref<LineItem[]>(buildInitialItems());

// The customer the form opens with. The form's initial recipients are seeded
// from their contacts below; useDocumentParties takes over from there.
const initialCustomerId =
    props.mode === 'edit'
        ? (props.invoice?.customer_id ?? null)
        : (props.defaults?.customer_id ?? null);

// Form
const form = useForm({
    type: props.type,
    organization_id:
        props.mode === 'edit'
            ? props.invoice!.organization_id
            : (props.defaults?.organization_id ?? null),
    customer_id: initialCustomerId,
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
        props.customers.find((c) => c.id === initialCustomerId)?.emails ?? [],
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
// Form/formContext.ts for why. The cards another document form can reuse take
// the narrower DocumentFormKey view of the same object.
provide(InvoiceFormKey, form);
provide(DocumentFormKey, form);

// Customer, addresses, place of supply and recipients, and the GST inputs
// that follow from them; see the composable.
const {
    selectedCustomerId,
    selectedCustomer,
    customerLocations,
    customerContacts,
    showPlaceOfSupply,
    showSourceTaxes,
    supplierStateCode,
    isForeignCustomer,
    sameAsBilling,
} = useDocumentParties(form, {
    customers: () => props.customers,
    organizationLocations: () => props.organizationLocations,
    indianStates: () => props.indianStates,
});

// Determine currency from selected customer
const currentCurrency = computed<Currency>(
    () =>
        selectedCustomer.value?.currency ??
        (props.defaults?.currency as Currency) ??
        'INR',
);

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
                openEmailModal();
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
        openEmailModal();
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
        <!--
            No flash message here. The layout renders one for every page, with
            the live regions and the auto-dismiss this copy never had, so a
            saved document reported itself twice: once in a bordered green box
            from the form and once in a plain one from the layout.
        -->
        <!-- A validation error whose field is conditionally hidden (a customer
             with no location hides the whole billing block, for one) would
             otherwise leave the save button doing nothing at all. -->
        <div
            v-if="form.hasErrors"
            class="rounded-xl border border-destructive/40 bg-destructive-soft/50 p-4"
            role="alert"
        >
            <p class="text-sm font-medium text-destructive-soft-foreground">
                This {{ type === 'estimate' ? 'estimate' : 'invoice' }} could
                not be saved.
            </p>
            <ul
                class="mt-2 list-inside list-disc space-y-1 text-sm text-destructive"
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

            <ItemsCard
                v-model:items="items"
                :currency="currentCurrency"
                :tax-options="taxOptions"
                :supplier-state-code="supplierStateCode"
                :is-foreign-customer="isForeignCustomer"
            />

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

                `data-sticky-actions` reserves the bar's height at the foot of
                the page's scroll area (app.css), so a field the browser brings
                into view on focus is not left underneath it. Tighter below
                640px: at 320px the three buttons ran 11px past the bar and
                clipped Save & Send. Its bottom padding grows by the safe area,
                so in the installed iPhone app the buttons stay above the home
                indicator while the bar's background runs under it.
            -->
            <div
                data-sticky-actions
                class="sticky bottom-0 z-10 flex items-center justify-between gap-2 rounded-xl border border-border bg-card px-3 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom,0px))] shadow-[0_-2px_8px_rgba(0,0,0,0.06)] sm:gap-3 sm:px-4"
            >
                <Button type="button" variant="outline" @click="cancel">
                    Cancel
                </Button>
                <div class="flex items-center gap-2 sm:gap-3">
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
                        :aria-busy="emailDialog.busy"
                        v-on="emailDialog.intent"
                        @click="saveAndSend"
                    >
                        <Loader2 v-if="emailDialog.busy" class="animate-spin" />
                        {{ form.processing ? 'Saving...' : 'Save & Send' }}
                    </Button>
                </div>
            </div>
        </form>

        <!-- Email Modal -->
        <EmailModal
            v-if="mode === 'edit' && invoice && emailDialog.loaded"
            :show="showEmailModal"
            :invoice="invoice"
            :customers="customers"
            :invoice-attachments="attachments ?? []"
            @close="showEmailModal = false"
        />
    </div>
</template>
