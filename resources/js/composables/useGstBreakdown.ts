import { calcLineTax } from './useInvoiceCalculator';
import type { LineItem } from './useInvoiceCalculator';

/**
 * GST state codes that levy UTGST for an intra-territory supply (Union
 * Territories without their own legislature). Delhi (07), Puducherry (34) and
 * Jammu and Kashmir (01) are excluded — they have legislatures and levy SGST.
 * Mirrors {@see \App\Enums\IndianState::leviesUtgst()}.
 */
const UTGST_STATE_CODES = new Set(['04', '26', '31', '35', '38', '97']);

export interface GstBreakdownRow {
    label: string;
    amount: number; // cents
}

export interface GstBreakdownParams {
    items: LineItem[];
    supplierStateCode: string | null;
    placeOfSupplyCode: string | null;
    // A supply to an overseas customer is an export → inter-state → IGST.
    isForeignCustomer?: boolean;
}

/**
 * Format a basis-point rate as a trimmed, parenthesised percentage
 * (900 → "(9%)", 250 → "(2.5%)").
 * Mirrors PHP's `sprintf('%g', ...)` used server-side.
 */
function formatRate(basisPoints: number): string {
    return `(${parseFloat((basisPoints / 100).toPrecision(6))}%)`;
}

/**
 * Split the aggregate GST into CGST + SGST/UTGST (intra-state) or IGST
 * (inter-state), grouped per rate. An overseas customer is an export, which is
 * inter-state and therefore IGST. Returns an empty array when the supply is not
 * a determinable Indian GST supply, so callers fall back to a single tax line.
 * Mirrors {@see \App\Services\GstBreakdownService}.
 */
export function buildGstBreakdown(
    params: GstBreakdownParams,
): GstBreakdownRow[] {
    const {
        items,
        supplierStateCode,
        placeOfSupplyCode,
        isForeignCustomer = false,
    } = params;

    if (!supplierStateCode) {
        return [];
    }

    if (!isForeignCustomer && !placeOfSupplyCode) {
        return [];
    }

    const isIntraState =
        !isForeignCustomer && supplierStateCode === placeOfSupplyCode;

    // Aggregate each line's rounded tax by GST rate (basis points).
    const taxByRate = new Map<number, number>();
    for (const item of items) {
        const rate = Number(item.tax_rate) || 0;

        if (rate <= 0) {
            continue;
        }

        taxByRate.set(rate, (taxByRate.get(rate) ?? 0) + calcLineTax(item));
    }

    const rows = new Map<string, number>();

    for (const [rate, taxAmount] of taxByRate) {
        if (taxAmount <= 0) {
            continue;
        }

        if (isIntraState) {
            const stateLabel = UTGST_STATE_CODES.has(placeOfSupplyCode)
                ? 'UTGST'
                : 'SGST';
            const halfLabel = formatRate(rate / 2);

            // Split the already-rounded line tax so components reconcile to the
            // total; any odd cent goes to the state component.
            const cgst = Math.floor(taxAmount / 2);
            const stateTax = taxAmount - cgst;

            const cgstKey = `CGST @ ${halfLabel}`;
            const stateKey = `${stateLabel} @ ${halfLabel}`;
            rows.set(cgstKey, (rows.get(cgstKey) ?? 0) + cgst);
            rows.set(stateKey, (rows.get(stateKey) ?? 0) + stateTax);

            continue;
        }

        const key = `IGST @ ${formatRate(rate)}`;
        rows.set(key, (rows.get(key) ?? 0) + taxAmount);
    }

    return Array.from(rows, ([label, amount]) => ({ label, amount }));
}
