export type Currency =
    | 'INR'
    | 'USD'
    | 'EUR'
    | 'GBP'
    | 'AUD'
    | 'CAD'
    | 'SGD'
    | 'JPY'
    | 'AED';

export type InvoiceStatus =
    | 'draft'
    | 'sent'
    | 'accepted'
    | 'partially_paid'
    | 'paid'
    | 'void';

export type Country =
    | 'IN'
    | 'GB'
    | 'US'
    | 'AE'
    | 'AU'
    | 'CA'
    | 'SG'
    | 'JP'
    | 'DE';

export type FinancialYearType =
    | 'april_march'
    | 'january_december'
    | 'july_june'
    | 'october_september';

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
    personal_team: boolean;
    currency: Currency | null;
    country_code: Country | null;
    financial_year_type: FinancialYearType | null;
    tax_number: string | null;
    registration_number: string | null;
    phone: string | null;
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
    gstin: string | null;
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
}

export interface Customer {
    id: number;
    name: string;
    phone: string | null;
    currency: Currency | null;
    emails: Contact[];
    organization_id: number;
    primary_location_id: number | null;
    primary_location?: Location;
    locations?: Location[];
    created_at: string;
    updated_at: string;
}

export interface Invoice {
    id: number;
    ulid: string;
    type: 'invoice' | 'estimate';
    organization_id: number;
    customer_id: number | null;
    invoice_number: string;
    status: InvoiceStatus;
    issued_at: string;
    due_at: string | null;
    currency: Currency;
    exchange_rate: number;
    subtotal: number;
    tax: number;
    total: number;
    amount_paid: number;
    tax_type: string | null;
    tax_breakdown: TaxBreakdownItem[] | null;
    email_recipients: string[] | null;
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
    formatted_remaining_balance?: string;
    payment_percentage?: number;
    payments?: Payment[];
    created_at: string;
    updated_at: string;
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
    created_at: string;
    updated_at: string;
}

export interface TaxBreakdownItem {
    name: string;
    rate: number;
    amount: number;
}

export interface TaxTemplate {
    id: number;
    organization_id: number;
    name: string;
    type: string;
    rate: number;
    category: string | null;
    country_code: Country | null;
    description: string | null;
    is_active: boolean;
    metadata: Record<string, unknown> | null;
    formatted_rate?: string;
    created_at: string;
    updated_at: string;
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

/** Shared page props from HandleInertiaRequests */
export interface SharedProps {
    auth: {
        user: Pick<
            User,
            'id' | 'name' | 'email' | 'profile_photo_url' | 'two_factor_enabled'
        > | null;
        currentTeam: Pick<
            Organization,
            'id' | 'name' | 'company_name' | 'currency' | 'personal_team'
        > | null;
    };
    flash: {
        success: string | null;
        error: string | null;
        message: string | null;
    };
}

declare module '@inertiajs/vue3' {
    // eslint-disable-next-line @typescript-eslint/no-empty-object-type
    interface PageProps extends SharedProps {}
}
