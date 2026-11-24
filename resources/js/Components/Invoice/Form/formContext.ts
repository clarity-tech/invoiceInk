import type { InertiaForm } from '@inertiajs/vue3';
import type { InjectionKey } from 'vue';
import type { LineItem } from '@/composables/useInvoiceCalculator';
import type { EmailRecipient } from '@/types';

/**
 * The fields a document form shares with any other form that bills a customer
 * for line items: who it is between, where the supply goes, what it charges and
 * who hears about it.
 *
 * Kept apart from the invoice's own payload so the cards that edit only these
 * -- PartiesCard, ItemsCard, SourceTaxesCard -- do not depend on a status, a
 * pair of dates or a numbering series they never touch, and can be mounted
 * under a form that has none of those.
 */
export interface DocumentFields {
    customer_id: number | null;
    organization_location_id: number | null;
    customer_location_id: number | null;
    customer_shipping_location_id: number | null;
    place_of_supply: string;
    email_recipients: EmailRecipient[];
    tds_rate: number | null;
    tds_section: string;
    tcs_rate: number | null;
    tcs_section: string;
    items: LineItem[];
}

/** The payload InvoiceForm submits; see its `useForm()` call. */
export interface InvoiceFormData extends DocumentFields {
    type: 'invoice' | 'estimate';
    organization_id: number | null;
    status: string;
    issued_at: string;
    due_at: string;
    invoice_numbering_series_id: number | null;
    notes: string;
    subject: string;
    order_number: string;
    terms: string;
    attachments: File[];
    removed_attachment_ids: number[];
}

export type InvoiceFormContext = InertiaForm<InvoiceFormData>;

export type DocumentFormContext = InertiaForm<DocumentFields>;

/**
 * The form is provided rather than passed down as a prop.
 *
 * The section components write to it directly -- `v-model="form.subject"` --
 * and Vue's `no-mutating-props` rule (part of the essential ruleset this
 * project lints with) forbids exactly that on a prop. Threading forty fields
 * back up through emits would cost more than it bought, and `useForm` already
 * returns a reactive object that is safe to share.
 */
export const InvoiceFormKey: InjectionKey<InvoiceFormContext> =
    Symbol('invoiceForm');

/**
 * The same form object as InvoiceFormKey, seen through DocumentFields. A form
 * provides both; the shared cards inject only this one.
 */
export const DocumentFormKey: InjectionKey<DocumentFormContext> =
    Symbol('documentForm');
