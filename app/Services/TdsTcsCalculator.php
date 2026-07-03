<?php

namespace App\Services;

class TdsTcsCalculator
{
    /**
     * Compute the source-tax amounts and the resulting net total for an invoice.
     *
     * TDS (Tax Deducted at Source) is withheld by the buyer on the taxable
     * value (excluding GST) and reduces the amount payable. TCS (Tax Collected
     * at Source) is collected by the seller on the invoice value (including
     * GST) and adds to it. Rates are basis points (1000 = 10%); all amounts are
     * integer cents.
     *
     * @return array{tds_amount: int, tcs_amount: int, total: int}
     */
    public function compute(int $subtotal, int $tax, ?int $tdsRate, ?int $tcsRate): array
    {
        $tdsAmount = $this->amount($subtotal, $tdsRate);
        $tcsAmount = $this->amount($subtotal + $tax, $tcsRate);

        return [
            'tds_amount' => $tdsAmount,
            'tcs_amount' => $tcsAmount,
            'total' => $subtotal + $tax + $tcsAmount - $tdsAmount,
        ];
    }

    private function amount(int $base, ?int $rateBasisPoints): int
    {
        if (! $rateBasisPoints || $rateBasisPoints <= 0) {
            return 0;
        }

        return (int) round($base * $rateBasisPoints / 10000);
    }
}
