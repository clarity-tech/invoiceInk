import { describe, it, expect } from 'vitest';
import { calcTdsAmount, calcTcsAmount } from '@/composables/useSourceTaxes';

describe('useSourceTaxes', () => {
    it('computes TDS on the subtotal (excluding GST)', () => {
        expect(calcTdsAmount(100000, 1000)).toBe(10000); // 10% of 1,000.00
    });

    it('computes TCS on the invoice value including GST', () => {
        expect(calcTcsAmount(100000, 18000, 10)).toBe(118); // 0.1% of 1,180.00
    });

    it('returns 0 for a zero or falsy rate', () => {
        expect(calcTdsAmount(100000, 0)).toBe(0);
        expect(calcTcsAmount(100000, 18000, 0)).toBe(0);
    });

    it('rounds to the nearest cent', () => {
        expect(calcTdsAmount(1005, 250)).toBe(25); // 2.5% of 1,005 = 25.125
    });
});
