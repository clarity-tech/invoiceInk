/**
 * Format a date string for display in Indian locale (DD Mon YYYY).
 */
export function formatDate(dateStr: string | null | undefined): string {
    if (!dateStr) {
        return '-';
    }

    return new Date(dateStr).toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

export function useFormatDate() {
    return { formatDate };
}
