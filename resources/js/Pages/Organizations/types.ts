export interface Location {
    id: number;
    name: string;
    gstin: string | null;
    address_line_1: string;
    address_line_2: string | null;
    city: string;
    state: string;
    country: string;
    postal_code: string;
}

export interface BankDetails {
    account_name?: string;
    account_number?: string;
    bank_name?: string;
    ifsc?: string;
    branch?: string;
    swift?: string;
    pan?: string;
}

export interface Organization {
    id: number;
    name: string;
    company_name: string | null;
    phone: string | null;
    emails: { email: string; name: string }[];
    currency: string | null;
    country_code: string | null;
    financial_year_type: string | null;
    financial_year_start_month: number;
    financial_year_start_day: number;
    tax_number: string | null;
    registration_number: string | null;
    website: string | null;
    notes: string | null;
    bank_details: BankDetails | null;
    logo_url: string | null;
    primary_location: Location | null;
    personal_team: boolean;
}

export interface CountryInfo {
    value: string;
    label: string;
    currency: string;
    financial_year_options: Record<string, string>;
    default_financial_year: string;
    supported_currencies: Record<string, string>;
    tax_system: { name: string; rates: string[] };
    recommended_numbering: string;
}
