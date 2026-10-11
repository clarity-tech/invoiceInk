import type { InertiaForm } from '@inertiajs/vue3';
import type { InjectionKey } from 'vue';
import type { LineItem } from '@/composables/useInvoiceCalculator';
import type { EmailRecipient } from '@/types';

/** The payload InvoiceForm submits; see its `useForm()` call. */
export interface InvoiceFormData {
    type: 'invoice' | 'estimate';
    organization_id: number | null;
    customer_id: number | null;
    organization_location_id: number | null;
    customer_location_id: number | null;
    customer_shipping_location_id: number | null;
    status: string;
    issued_at: string;
    due_at: string;
    invoice_numbering_series_id: number | null;
    notes: string;
    subject: string;
    order_number: string;
    terms: string;
    email_recipients: EmailRecipient[];
    place_of_supply: string;
    tds_rate: number | null;
    tds_section: string;
    tcs_rate: number | null;
    tcs_section: string;
    items: LineItem[];
    attachments: File[];
    removed_attachment_ids: number[];
}

export type InvoiceFormContext = InertiaForm<InvoiceFormData>;

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
