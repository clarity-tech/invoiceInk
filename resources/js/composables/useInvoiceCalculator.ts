import { computed } from 'vue';
import type { Ref } from 'vue';

export interface LineItem {
    description: string;
    sac_code: string | null;
    quantity: number;
    unit_price: number;
    tax_rate: number; // basis points, e.g., 1800 = 18%
}

export interface InvoiceTotals {
    subtotal: number;
    tax: number;
    total: number;
}

/**
 * Calculate line total for a single item (in cents).
 */
export function calcLineTotal(item: LineItem): number {
    return Math.round(item.quantity * item.unit_price);
}

/**
 * Calculate tax amount for a single item (in cents).
 * Tax rate is in basis points (1800 = 18%).
 */
export function calcLineTax(item: LineItem): number {
    const lineTotal = calcLineTotal(item);

    return Math.round((lineTotal * item.tax_rate) / 10000);
}

/**
 * Calculate line total with tax for a single item (in cents).
 */
export function calcLineTotalWithTax(item: LineItem): number {
    return calcLineTotal(item) + calcLineTax(item);
}

/**
 * Composable for reactive invoice calculations.
 */
export function useInvoiceCalculator(items: Ref<LineItem[]>) {
    const totals = computed<InvoiceTotals>(() => {
        let subtotal = 0;
        let tax = 0;

        for (const item of items.value) {
            subtotal += calcLineTotal(item);
            tax += calcLineTax(item);
        }

        return {
            subtotal,
            tax,
            total: subtotal + tax,
        };
    });

    return { totals };
}
