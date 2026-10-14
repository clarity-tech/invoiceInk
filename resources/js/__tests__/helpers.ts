import { vi } from 'vitest';
import type {
    User,
    Organization,
    Customer,
    Invoice,
    Payment,
    Location,
    TaxRateOption,
    InvoiceNumberingSeries,
    Contact,
    Currency,
    InvoiceStatus,
} from '@/types';

export function makeContact(overrides: Partial<Contact> = {}): Contact {
    return { name: 'John Doe', email: 'john@example.test', ...overrides };
}

export function makeLocation(overrides: Partial<Location> = {}): Location {
    return {
        id: 1,
        name: 'Head Office',
        tax_id: null,
        address_line_1: '123 Main St',
        address_line_2: null,
        city: 'Mumbai',
        state: 'Maharashtra',
        country: 'IN',
        postal_code: '400001',
        locatable_type: 'App\\Models\\Organization',
        locatable_id: 1,
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
        ...overrides,
    };
}

export function makeOrganization(
    overrides: Partial<Organization> = {},
): Organization {
    return {
        id: 1,
        name: "Maya's Team",
        company_name: 'Clarity Technologies',
        currency: 'INR',
        country_code: 'IN',
        financial_year_type: 'april_march',
        tax_number: null,
        registration_number: null,
        phone: null,
        phone_country: null,
        formatted_phone: null,
        website: null,
        notes: null,
        emails: [makeContact()],
        bank_details: null,
        primary_location_id: 1,
        logo_url: null,
        logo_thumb_url: null,
        setup_completed_at: '2026-01-01T00:00:00.000000Z',
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
        ...overrides,
    };
}

export function makeUser(overrides: Partial<User> = {}): User {
    return {
        id: 1,
        name: 'Test User',
        email: 'test@example.test',
        profile_photo_url: 'https://ui-avatars.com/api/?name=Test+User',
        two_factor_enabled: false,
        current_team_id: 1,
        email_verified_at: '2026-01-01T00:00:00.000000Z',
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
        ...overrides,
    };
}

export function makeCustomer(overrides: Partial<Customer> = {}): Customer {
    return {
        id: 1,
        name: 'ACME Corp',
        phone: '+919876543210',
        phone_country: 'IN',
        formatted_phone: '+91 98765 43210',
        currency: 'INR',
        place_of_supply: null,
        gst_treatment: 'domestic',
        emails: [makeContact()],
        organization_id: 1,
        primary_location_id: 1,
        primary_location: makeLocation({
            locatable_type: 'App\\Models\\Customer',
        }),
        locations: [],
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
        ...overrides,
    };
}

export function makeInvoice(overrides: Partial<Invoice> = {}): Invoice {
    return {
        id: 1,
        ulid: '01HX1234567890ABCDEF',
        type: 'invoice',
        organization_id: 1,
        customer_id: 1,
        invoice_number: 'INV-2026-03-0001',
        order_number: null,
        subject: null,
        status: 'draft' as InvoiceStatus,
        issued_at: '2026-03-01',
        due_at: '2026-03-31',
        currency: 'INR' as Currency,
        place_of_supply: null,
        exchange_rate: 1,
        subtotal: 10000000,
        tax: 1800000,
        total: 11800000,
        amount_paid: 0,
        tax_type: null,
        tax_breakdown: null,
        tds_rate: null,
        tds_amount: 0,
        tds_section: null,
        tcs_rate: null,
        tcs_amount: 0,
        tcs_section: null,
        email_recipients: null,
        notes: null,
        terms: null,
        items: [],
        payments: [],
        remaining_balance: 11800000,
        formatted_remaining_balance: '₹1,18,000.00',
        payment_percentage: 0,
        created_at: '2026-03-01T00:00:00.000000Z',
        updated_at: '2026-03-01T00:00:00.000000Z',
        ...overrides,
    };
}

export function makePayment(overrides: Partial<Payment> = {}): Payment {
    return {
        id: 1,
        invoice_id: 1,
        amount: 5000000,
        currency: 'INR' as Currency,
        payment_date: '2026-03-15',
        payment_method: 'bank_transfer',
        reference: 'TXN-12345',
        notes: null,
        created_at: '2026-03-15T00:00:00.000000Z',
        updated_at: '2026-03-15T00:00:00.000000Z',
        ...overrides,
    };
}

export function makeTaxOption(
    overrides: Partial<TaxRateOption> = {},
): TaxRateOption {
    return {
        key: 'gst-18',
        name: 'GST 18%',
        rate: 1800,
        ...overrides,
    };
}

export function makeNumberingSeries(
    overrides: Partial<InvoiceNumberingSeries> = {},
): InvoiceNumberingSeries {
    return {
        id: 1,
        organization_id: 1,
        location_id: null,
        name: 'Default Invoice',
        prefix: 'INV',
        format_pattern: '{PREFIX}-{YEAR}-{MONTH}-{SEQUENCE:4}',
        current_number: 1,
        reset_frequency: 'yearly',
        is_active: true,
        is_default: true,
        last_reset_at: null,
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
        ...overrides,
    };
}

export function makeSharedPageProps(overrides: Record<string, unknown> = {}) {
    return {
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Test User',
                    email: 'test@example.test',
                    profile_photo_url: 'https://ui-avatars.com/api/?name=Test',
                    two_factor_enabled: false,
                },
                currentTeam: {
                    id: 1,
                    name: "Maya's Team",
                    company_name: 'Clarity Technologies',
                    currency: 'INR',
                },
            },
            flash: {
                success: null,
                error: null,
                message: null,
            },
            ...overrides,
        },
    };
}

/**
 * Create a mock Inertia form object for testing components that receive
 * InertiaForm as a prop.
 */
export function makeMockForm<T extends Record<string, unknown>>(data: T) {
    return {
        ...data,
        errors: {} as Record<string, string>,
        processing: false,
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
        reset: vi.fn(),
        clearErrors: vi.fn(),
        transform: vi.fn().mockReturnThis(),
    };
}

/** Emulates the operating system's light/dark setting. */
export function stubSystemTheme(prefersDark: boolean): void {
    vi.stubGlobal(
        'matchMedia',
        vi.fn().mockReturnValue({ matches: prefersDark }),
    );
}

/** A page with no stored theme, on a light system. */
export function resetAppearance(): void {
    document.cookie = 'appearance=; max-age=0; path=/';
    document.documentElement.classList.remove('dark');
    stubSystemTheme(false);
}
