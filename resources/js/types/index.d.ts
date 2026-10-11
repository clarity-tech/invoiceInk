export type Currency =
    'INR' | 'USD' | 'EUR' | 'GBP' | 'AUD' | 'CAD' | 'SGD' | 'JPY' | 'AED';

export type InvoiceStatus =
    'draft' | 'sent' | 'accepted' | 'partially_paid' | 'paid' | 'void';

export type Country =
    'IN' | 'GB' | 'US' | 'AE' | 'AU' | 'CA' | 'SG' | 'JP' | 'DE';

export type FinancialYearType =
    'april_march' | 'january_december' | 'july_june' | 'october_september';

export type ResetFrequency = 'never' | 'yearly' | 'monthly' | 'financial_year';

export interface User {
    id: number;
    name: string;
    email: string;
    profile_photo_url: string;
    two_factor_enabled: boolean;
    current_team_id: number | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    all_teams?: Organization[];
    current_team?: Organization;
}

export interface Organization {
    id: number;
    name: string;
    company_name: string | null;
    currency: Currency | null;
    country_code: Country | null;
    financial_year_type: FinancialYearType | null;
    tax_number: string | null;
    registration_number: string | null;
    phone: string | null;
    phone_country: string | null;
    formatted_phone: string | null;
    website: string | null;
    notes: string | null;
    emails: Contact[];
    bank_details: BankDetails | null;
    primary_location_id: number | null;
    primary_location?: Location;
    logo_url: string | null;
    logo_thumb_url: string | null;
    setup_completed_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface Contact {
    name: string;
    email: string;
}

// A remembered recipient stored on an invoice: an email plus whether it is
// currently selected to receive the document. Covers both the customer's
// contacts and ad-hoc extras added on the invoice or in the email editor.
export interface EmailRecipient {
    email: string;
    checked: boolean;
}

export interface BankDetails {
    bank_name: string | null;
    account_name: string | null;
    account_number: string | null;
    ifsc_code: string | null;
    swift_code: string | null;
    iban: string | null;
    branch: string | null;
    routing_number: string | null;
}

export interface Location {
    id: number;
    name: string | null;
    tax_id: string | null;
    address_line_1: string;
    address_line_2: string | null;
    city: string;
    state: string;
    country: string;
    postal_code: string;
    locatable_type: string;
    locatable_id: number;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
}

export interface Customer {
    id: number;
    name: string;
    phone: string | null;
    phone_country: string | null;
    formatted_phone: string | null;
    currency: Currency | null;
    place_of_supply: string | null;
    gst_treatment: 'domestic' | 'overseas';
    emails: Contact[];
    organization_id: number;
    primary_location_id: number | null;
    primary_location?: Location;
    locations?: Location[];
    archived_locations?: Location[];
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
}

export interface Invoice {
    id: number;
    ulid: string;
    type: 'invoice' | 'estimate';
    organization_id: number;
    customer_id: number | null;
    invoice_number: string;
    order_number: string | null;
    subject: string | null;
    status: InvoiceStatus;
    issued_at: string;
    due_at: string | null;
    currency: Currency;
    place_of_supply: string | null;
    exchange_rate: number;
    subtotal: number;
    tax: number;
    total: number;
    amount_paid: number;
    tax_type: string | null;
    tax_breakdown: TaxBreakdownItem[] | null;
    tds_rate: number | null;
    tds_amount: number;
    tds_section: string | null;
    tcs_rate: number | null;
    tcs_amount: number;
    tcs_section: string | null;
    email_recipients: EmailRecipient[] | null;
    notes: string | null;
    terms: string | null;
    organization?: Organization;
    customer?: Customer;
    items?: InvoiceItem[];
    organization_location?: Location;
    customer_location?: Location;
    customer_shipping_location?: Location;
    formatted_subtotal?: string;
    formatted_tax?: string;
    formatted_total?: string;
    formatted_amount_paid?: string;
    remaining_balance?: number;
    /** Derived for the list; see InvoiceController::index(). */
    is_overdue?: boolean;
    days_overdue?: number | null;
    formatted_remaining_balance?: string;
    payment_percentage?: number;
    payments?: Payment[];
    latest_email_delivery?: EmailDelivery | null;
    /** Every send attempt, newest first. Loaded by InvoiceController::show(). */
    email_deliveries?: EmailDelivery[];
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
}

export type EmailDeliveryStatus = 'queued' | 'sending' | 'sent' | 'failed';

export interface EmailDelivery {
    id: number;
    status: EmailDeliveryStatus;
    mode: string;
    recipients: string[];
    error?: string | null;
    attempts: number;
    queued_at?: string | null;
    sent_at?: string | null;
    failed_at?: string | null;
}

export interface InvoiceAttachment {
    id: number;
    name: string;
    size: number;
    mime: string;
    extension: string;
    url: string;
}

export interface Payment {
    id: number;
    invoice_id: number;
    amount: number;
    currency: Currency;
    payment_date: string;
    payment_method: string | null;
    reference: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
}

export interface InvoiceItem {
    id: number;
    invoice_id: number;
    description: string;
    sac_code: string | null;
    quantity: number;
    unit_price: number;
    tax_rate: number;
    /**
     * Computed server-side; see InvoiceController::show(). Both are model
     * methods rather than attributes, and the tax one rounds, so they are sent
     * rather than recomputed in the browser.
     */
    line_total?: number;
    tax_amount?: number;
    created_at: string;
    updated_at: string;
}

export interface TaxBreakdownItem {
    name: string;
    rate: number;
    amount: number;
}

export interface TaxRateOption {
    key: string;
    name: string;
    rate: number;
}

export interface Payment {
    id: number;
    invoice_id: number;
    amount: number;
    currency: Currency;
    payment_date: string;
    payment_method: string | null;
    reference: string | null;
    notes: string | null;
    formatted_amount?: string;
    created_at: string;
    updated_at: string;
}

export interface InvoiceNumberingSeries {
    id: number;
    organization_id: number;
    location_id: number | null;
    name: string;
    prefix: string;
    format_pattern: string;
    current_number: number;
    reset_frequency: ResetFrequency;
    is_active: boolean;
    is_default: boolean;
    last_reset_at: string | null;
    location?: Location;
    created_at: string;
    updated_at: string;
}

export interface TeamSummary {
    id: number;
    name: string;
    company_name: string | null;
}

/**
 * Server-supplied seed values for a create-mode invoice form.
 *
 * The first block is always present. The rest is only sent when the form was
 * opened as a duplicate (`/invoices/{id}/duplicate`), in which case it carries
 * the source document's content. Everything the server recomputes on save —
 * the number, the totals, the currency and the exchange rate — is absent by
 * design.
 */
export interface InvoiceFormDefaults {
    organization_id: number | null;
    organization_location_id: number | null;
    invoice_numbering_series_id: number | null;
    issued_at: string;
    due_at: string;
    currency: string;
    customer_id?: number | null;
    customer_location_id?: number | null;
    customer_shipping_location_id?: number | null;
    subject?: string;
    order_number?: string;
    place_of_supply?: string;
    tds_rate?: number | null;
    tds_section?: string;
    tcs_rate?: number | null;
    tcs_section?: string;
    notes?: string;
    terms?: string;
    email_recipients?: EmailRecipient[];
    items?: InvoiceItemInput[];
}

/** A line item as the form holds it, before the server assigns it an id. */
export interface InvoiceItemInput {
    description: string;
    sac_code: string | null;
    quantity: number;
    unit_price: number;
    tax_rate: number;
}

/** Shared page props from HandleInertiaRequests */
export interface SharedProps {
    auth: {
        user:
            | (Pick<
                  User,
                  | 'id'
                  | 'name'
                  | 'email'
                  | 'profile_photo_url'
                  | 'two_factor_enabled'
              > & { all_teams: TeamSummary[] })
            | null;
        currentTeam: Pick<
            Organization,
            'id' | 'name' | 'company_name' | 'currency'
        > | null;
    };
    flash: {
        success: string | null;
        error: string | null;
        info: string | null;
        message: string | null;
    };
}

/**
 * Inertia v3 resolves shared props through `InertiaConfig['sharedPageProps']`
 * on @inertiajs/core, not through a PageProps interface on @inertiajs/vue3.
 * The previous augmentation targeted the latter, so it compiled cleanly while
 * having no effect -- `usePage().props` stayed `{}` and every access to it had
 * to be cast. Declaring it here is what actually types the shared props.
 */
declare module '@inertiajs/core' {
    interface InertiaConfig {
        sharedPageProps: SharedProps;
    }
}
