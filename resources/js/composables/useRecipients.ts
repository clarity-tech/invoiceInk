import type { Contact, EmailRecipient } from '@/types';

// Stored recipient lists may contain legacy plain-string emails (older invoices
// and seeded data stored a simple email array). Accept either shape on read.
type StoredRecipient = string | EmailRecipient;

function normalizeEmail(value: string | undefined): string {
    return (value ?? '').trim();
}

/**
 * Coerce a stored recipient list into `EmailRecipient[]`, deduping by lowercased
 * email. Legacy string entries are treated as checked.
 */
export function normalizeRecipients(
    stored: StoredRecipient[] | null | undefined,
): EmailRecipient[] {
    if (!Array.isArray(stored)) {
        return [];
    }

    const seen = new Set<string>();
    const result: EmailRecipient[] = [];

    for (const item of stored) {
        const isString = typeof item === 'string';
        const email = normalizeEmail(isString ? item : item.email);

        if (!email) {
            continue;
        }

        const key = email.toLowerCase();

        if (seen.has(key)) {
            continue;
        }

        seen.add(key);
        result.push({
            email,
            checked: isString ? true : item.checked !== false,
        });
    }

    return result;
}

/**
 * Build the editor's recipient list by merging the customer's current contacts
 * with whatever the invoice remembered. Every live contact appears (defaulting
 * to checked, or keeping its remembered checked-state), followed by any
 * remembered extras that are no longer — or never were — customer contacts.
 */
export function mergeRecipients(
    contacts: Contact[],
    stored: StoredRecipient[] | null | undefined,
): EmailRecipient[] {
    const remembered = normalizeRecipients(stored);
    const byEmail = new Map(
        remembered.map((r) => [r.email.toLowerCase(), r] as const),
    );

    const result: EmailRecipient[] = [];
    const used = new Set<string>();

    for (const contact of contacts) {
        const email = normalizeEmail(contact.email);

        if (!email) {
            continue;
        }

        const key = email.toLowerCase();

        if (used.has(key)) {
            continue;
        }

        used.add(key);
        result.push({ email, checked: byEmail.get(key)?.checked ?? true });
    }

    for (const recipient of remembered) {
        const key = recipient.email.toLowerCase();

        if (used.has(key)) {
            continue;
        }

        used.add(key);
        result.push(recipient);
    }

    return result;
}
