import { describe, it, expect } from 'vitest';
import { ref } from 'vue';
import {
    calcLineTotal,
    calcLineTax,
    calcLineTotalWithTax,
    useInvoiceCalculator,
} from '@/composables/useInvoiceCalculator';
import type { LineItem } from '@/composables/useInvoiceCalculator';

function makeItem(overrides: Partial<LineItem> = {}): LineItem {
    return {
        description: 'Test item',
        sac_code: null,
        quantity: 1,
        unit_price: 10000, // ₹100.00
        tax_rate: 1800, // 18%
        ...overrides,
    };
}

describe('calcLineTotal', () => {
    it('calculates quantity × unit_price', () => {
        expect(calcLineTotal(makeItem({ quantity: 2, unit_price: 5000 }))).toBe(
            10000,
        );
    });

    it('returns 0 when quantity is 0', () => {
        expect(calcLineTotal(makeItem({ quantity: 0 }))).toBe(0);
    });

    it('returns 0 when unit_price is 0', () => {
        expect(calcLineTotal(makeItem({ unit_price: 0 }))).toBe(0);
    });

    it('handles large numbers', () => {
        expect(
            calcLineTotal(makeItem({ quantity: 1000, unit_price: 9999999 })),
        ).toBe(9999999000);
    });

    it('rounds fractional quantities', () => {
        // 1.5 × 333 = 499.5 → rounds to 500
        expect(
            calcLineTotal(makeItem({ quantity: 1.5, unit_price: 333 })),
        ).toBe(500);
    });

    it('handles quantity of 1', () => {
        expect(calcLineTotal(makeItem({ quantity: 1, unit_price: 7777 }))).toBe(
            7777,
        );
    });
});

describe('calcLineTax', () => {
    it('calculates 18% tax (1800 basis points)', () => {
        // lineTotal = 10000, tax = 10000 * 1800 / 10000 = 1800
        expect(
            calcLineTax(
                makeItem({ quantity: 1, unit_price: 10000, tax_rate: 1800 }),
            ),
        ).toBe(1800);
    });

    it('calculates 0% tax', () => {
        expect(calcLineTax(makeItem({ tax_rate: 0 }))).toBe(0);
    });

    it('calculates 5% tax (500 basis points)', () => {
        // lineTotal = 10000, tax = 10000 * 500 / 10000 = 500
        expect(
            calcLineTax(
                makeItem({ quantity: 1, unit_price: 10000, tax_rate: 500 }),
            ),
        ).toBe(500);
    });

    it('rounds tax amount', () => {
        // lineTotal = 333, tax = 333 * 1800 / 10000 = 59.94 → 60
        expect(
            calcLineTax(
                makeItem({ quantity: 1, unit_price: 333, tax_rate: 1800 }),
            ),
        ).toBe(60);
    });

    it('handles high tax rates (50%)', () => {
        // lineTotal = 10000, tax = 10000 * 5000 / 10000 = 5000
        expect(
            calcLineTax(
                makeItem({ quantity: 1, unit_price: 10000, tax_rate: 5000 }),
            ),
        ).toBe(5000);
    });

    it('handles 100% tax', () => {
        expect(
            calcLineTax(
                makeItem({ quantity: 1, unit_price: 10000, tax_rate: 10000 }),
            ),
        ).toBe(10000);
    });
});

describe('calcLineTotalWithTax', () => {
    it('adds line total and tax', () => {
        // lineTotal = 10000, tax = 1800, total = 11800
        expect(
            calcLineTotalWithTax(
                makeItem({ quantity: 1, unit_price: 10000, tax_rate: 1800 }),
            ),
        ).toBe(11800);
    });

    it('returns line total when tax is 0', () => {
        expect(
            calcLineTotalWithTax(
                makeItem({ quantity: 2, unit_price: 5000, tax_rate: 0 }),
            ),
        ).toBe(10000);
    });

    it('handles zero amount', () => {
        expect(
            calcLineTotalWithTax(
                makeItem({ quantity: 0, unit_price: 0, tax_rate: 1800 }),
            ),
        ).toBe(0);
    });
});

describe('useInvoiceCalculator', () => {
    it('computes totals for a single item', () => {
        const items = ref([
            makeItem({ quantity: 1, unit_price: 10000, tax_rate: 1800 }),
        ]);
        const { totals } = useInvoiceCalculator(items);
        expect(totals.value).toEqual({
            subtotal: 10000,
            tax: 1800,
            total: 11800,
        });
    });

    it('computes totals for multiple items', () => {
        const items = ref([
            makeItem({ quantity: 2, unit_price: 5000, tax_rate: 1800 }),
            makeItem({ quantity: 1, unit_price: 20000, tax_rate: 500 }),
        ]);
        const { totals } = useInvoiceCalculator(items);
        // item1: subtotal=10000, tax=1800; item2: subtotal=20000, tax=1000
        expect(totals.value).toEqual({
            subtotal: 30000,
            tax: 2800,
            total: 32800,
        });
    });

    it('returns zeros for empty items', () => {
        const items = ref<LineItem[]>([]);
        const { totals } = useInvoiceCalculator(items);
        expect(totals.value).toEqual({ subtotal: 0, tax: 0, total: 0 });
    });

    it('reacts to item changes', () => {
        const items = ref([
            makeItem({ quantity: 1, unit_price: 10000, tax_rate: 0 }),
        ]);
        const { totals } = useInvoiceCalculator(items);
        expect(totals.value.total).toBe(10000);

        items.value = [
            makeItem({ quantity: 2, unit_price: 10000, tax_rate: 0 }),
        ];
        expect(totals.value.total).toBe(20000);
    });

    it('reacts to adding items', () => {
        const items = ref([
            makeItem({ quantity: 1, unit_price: 5000, tax_rate: 0 }),
        ]);
        const { totals } = useInvoiceCalculator(items);
        expect(totals.value.subtotal).toBe(5000);

        items.value.push(
            makeItem({ quantity: 1, unit_price: 3000, tax_rate: 0 }),
        );
        expect(totals.value.subtotal).toBe(8000);
    });

    it('handles items with different tax rates', () => {
        const items = ref([
            makeItem({ quantity: 1, unit_price: 10000, tax_rate: 1800 }), // 18%
            makeItem({ quantity: 1, unit_price: 10000, tax_rate: 500 }), // 5%
            makeItem({ quantity: 1, unit_price: 10000, tax_rate: 0 }), // 0%
        ]);
        const { totals } = useInvoiceCalculator(items);
        expect(totals.value).toEqual({
            subtotal: 30000,
            tax: 2300,
            total: 32300,
        });
    });
});

describe('edge cases', () => {
    it('handles negative quantity', () => {
        // Credit notes may have negative quantities
        expect(
            calcLineTotal(makeItem({ quantity: -1, unit_price: 10000 })),
        ).toBe(-10000);
    });

    it('handles negative unit_price', () => {
        expect(
            calcLineTotal(makeItem({ quantity: 1, unit_price: -5000 })),
        ).toBe(-5000);
    });

    it('maintains precision with many decimal places in quantity', () => {
        // 0.333 × 30000 = 9990 (rounded)
        expect(
            calcLineTotal(makeItem({ quantity: 0.333, unit_price: 30000 })),
        ).toBe(9990);
    });
});
