import { describe, it, expect } from 'vitest';
import { buildGstBreakdown } from '@/composables/useGstBreakdown';
import type { LineItem } from '@/composables/useInvoiceCalculator';

function item(unitPrice: number, taxRate: number, quantity = 1): LineItem {
    return {
        description: 'Item',
        sac_code: null,
        quantity,
        unit_price: unitPrice,
        tax_rate: taxRate,
    };
}

describe('buildGstBreakdown', () => {
    it('splits intra-state GST into equal CGST and SGST', () => {
        const rows = buildGstBreakdown({
            items: [item(100000, 1800)],
            supplierStateCode: '27',
            placeOfSupplyCode: '27',
        });

        expect(rows).toEqual([
            { label: 'CGST @ 9%', amount: 9000 },
            { label: 'SGST @ 9%', amount: 9000 },
        ]);
    });

    it('uses UTGST for an intra-union-territory supply', () => {
        const rows = buildGstBreakdown({
            items: [item(100000, 1800)],
            supplierStateCode: '04',
            placeOfSupplyCode: '04',
        });

        expect(rows).toEqual([
            { label: 'CGST @ 9%', amount: 9000 },
            { label: 'UTGST @ 9%', amount: 9000 },
        ]);
    });

    it('uses SGST for Delhi despite it being a union territory', () => {
        const rows = buildGstBreakdown({
            items: [item(100000, 1800)],
            supplierStateCode: '07',
            placeOfSupplyCode: '07',
        });

        expect(rows.map((r) => r.label)).toEqual(['CGST @ 9%', 'SGST @ 9%']);
    });

    it('uses IGST for an inter-state supply', () => {
        const rows = buildGstBreakdown({
            items: [item(100000, 1800)],
            supplierStateCode: '27',
            placeOfSupplyCode: '29',
        });

        expect(rows).toEqual([{ label: 'IGST @ 18%', amount: 18000 }]);
    });

    it('produces per-rate rows for mixed GST rates', () => {
        const rows = buildGstBreakdown({
            items: [item(100000, 1800), item(100000, 500)],
            supplierStateCode: '27',
            placeOfSupplyCode: '27',
        });

        expect(rows).toEqual([
            { label: 'CGST @ 9%', amount: 9000 },
            { label: 'SGST @ 9%', amount: 9000 },
            { label: 'CGST @ 2.5%', amount: 2500 },
            { label: 'SGST @ 2.5%', amount: 2500 },
        ]);
    });

    it('gives the odd cent to the state component so the split reconciles', () => {
        const rows = buildGstBreakdown({
            items: [item(505, 1800)],
            supplierStateCode: '27',
            placeOfSupplyCode: '27',
        });

        expect(rows).toEqual([
            { label: 'CGST @ 9%', amount: 45 },
            { label: 'SGST @ 9%', amount: 46 },
        ]);
        expect(rows.reduce((sum, r) => sum + r.amount, 0)).toBe(91);
    });

    it('applies IGST for an overseas customer regardless of place of supply', () => {
        const rows = buildGstBreakdown({
            items: [item(100000, 1800)],
            supplierStateCode: '27',
            placeOfSupplyCode: null,
            isForeignCustomer: true,
        });

        expect(rows).toEqual([{ label: 'IGST @ 18%', amount: 18000 }]);
    });

    it('never splits an overseas supply into CGST/SGST', () => {
        // Even if a place of supply matching the supplier state is passed, an
        // export stays inter-state.
        const rows = buildGstBreakdown({
            items: [item(100000, 1800)],
            supplierStateCode: '27',
            placeOfSupplyCode: '27',
            isForeignCustomer: true,
        });

        expect(rows).toEqual([{ label: 'IGST @ 18%', amount: 18000 }]);
    });

    it('returns no rows when the state cannot be determined', () => {
        expect(
            buildGstBreakdown({
                items: [item(100000, 1800)],
                supplierStateCode: null,
                placeOfSupplyCode: '27',
            }),
        ).toEqual([]);

        expect(
            buildGstBreakdown({
                items: [item(100000, 1800)],
                supplierStateCode: '27',
                placeOfSupplyCode: null,
            }),
        ).toEqual([]);
    });

    it('omits zero-rated items', () => {
        expect(
            buildGstBreakdown({
                items: [item(100000, 0)],
                supplierStateCode: '27',
                placeOfSupplyCode: '27',
            }),
        ).toEqual([]);
    });
});
