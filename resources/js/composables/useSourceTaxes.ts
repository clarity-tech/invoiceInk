/**
 * Client-side TDS/TCS maths, mirroring {@see \App\Services\TdsTcsCalculator}.
 * Rates are basis points (1000 = 10%); all amounts are integer cents.
 */

/**
 * TDS (Tax Deducted at Source) — withheld by the buyer on the taxable value
 * (excluding GST), so it is computed on the subtotal.
 */
export function calcTdsAmount(
    subtotal: number,
    rateBasisPoints: number,
): number {
    return amount(subtotal, rateBasisPoints);
}

/**
 * TCS (Tax Collected at Source) — collected by the seller on the invoice value
 * (including GST), so it is computed on subtotal + tax.
 */
export function calcTcsAmount(
    subtotal: number,
    tax: number,
    rateBasisPoints: number,
): number {
    return amount(subtotal + tax, rateBasisPoints);
}

function amount(base: number, rateBasisPoints: number): number {
    if (!rateBasisPoints || rateBasisPoints <= 0) {
        return 0;
    }

    return Math.round((base * rateBasisPoints) / 10000);
}
