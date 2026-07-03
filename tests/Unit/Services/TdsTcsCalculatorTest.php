<?php

use App\Services\TdsTcsCalculator;

beforeEach(function () {
    $this->calc = new TdsTcsCalculator;
});

it('computes TDS on the taxable value excluding GST', function () {
    // 10% TDS on a 1,000.00 subtotal (GST is ignored for the TDS base).
    $result = $this->calc->compute(100000, 18000, 1000, null);

    expect($result['tds_amount'])->toBe(10000);
    expect($result['tcs_amount'])->toBe(0);
    expect($result['total'])->toBe(108000); // 100000 + 18000 - 10000
});

it('computes TCS on the invoice value including GST', function () {
    // 0.1% TCS on 1,180.00 (subtotal + GST).
    $result = $this->calc->compute(100000, 18000, null, 10);

    expect($result['tcs_amount'])->toBe(118);
    expect($result['tds_amount'])->toBe(0);
    expect($result['total'])->toBe(118118); // 100000 + 18000 + 118
});

it('applies TDS and TCS together', function () {
    expect($this->calc->compute(100000, 18000, 1000, 10))->toBe([
        'tds_amount' => 10000,
        'tcs_amount' => 118,
        'total' => 108118,
    ]);
});

it('treats null or zero rates as no source tax', function () {
    $expected = ['tds_amount' => 0, 'tcs_amount' => 0, 'total' => 118000];

    expect($this->calc->compute(100000, 18000, null, null))->toBe($expected);
    expect($this->calc->compute(100000, 18000, 0, 0))->toBe($expected);
});

it('rounds source-tax amounts to the nearest cent', function () {
    // 2.5% of 1,005 cents = 25.125 → 25.
    expect($this->calc->compute(1005, 0, 250, null)['tds_amount'])->toBe(25);
});
