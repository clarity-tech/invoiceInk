import type { Invoice } from '@/types';

/**
 * How late a document is, in the plainest form. "Overdue" is not a status — it
 * is derived from `due_at` — so a document months late reads as plainly "Sent"
 * unless the page says otherwise itself.
 *
 * Shared by the listing and the detail page because they were each phrasing it
 * themselves, and the detail page's copy had no case for a document due today:
 * at nought days late it read "Overdue by 0 days".
 */
export function overdueLabel(invoice: Pick<Invoice, 'days_overdue'>): string {
    const days = invoice.days_overdue ?? 0;

    if (days < 1) {
        return 'Overdue today';
    }

    return days === 1 ? 'Overdue by 1 day' : `Overdue by ${days} days`;
}
