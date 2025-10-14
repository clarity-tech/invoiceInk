<?php

use App\Models\InvoiceItem;
use App\Services\InvoiceCalculator;
use App\ValueObjects\InvoiceTotals;

test('invoice calculator handles mixed tax rates', function () {
    $calculator = new InvoiceCalculator;

    $items = collect([
        new InvoiceItem([
            'description' => 'No Tax Item',
            'quantity' => 1,
            'unit_price' => 10000, // $100
            'tax_rate' => 0,
        ]),
        new InvoiceItem([
            'description' => 'Low Tax Item',
            'quantity' => 2,
            'unit_price' => 5000, // $50 each
            'tax_rate' => 550, // 5.5% in basis points
        ]),
        new InvoiceItem([
            'description' => 'High Tax Item',
            'quantity' => 1,
            'unit_price' => 20000, // $200
            'tax_rate' => 2500, // 25% in basis points
        ]),
    ]);

    $totals = $calculator->calculateFromItems($items);

    // Subtotal: $100 + (2 * $50) + $200 = $400
    expect($totals->subtotal)->toBe(40000);

    // Tax: $0 + (5.5% of $100) + (25% of $200) = $0 + $5.50 + $50 = $55.50
    expect($totals->tax)->toBe(5550);

    // Total: $400 + $55.50 = $455.50
    expect($totals->total)->toBe(45550);
});

test('invoice calculator handles fractional quantities', function () {
    $calculator = new InvoiceCalculator;

    $items = collect([
        new InvoiceItem([
            'description' => 'Fractional Service',
            'quantity' => 2, // InvoiceItem quantity is integer, but unit_price can represent fractions
            'unit_price' => 3333, // $33.33 each
            'tax_rate' => 1000, // 10% in basis points
        ]),
    ]);

    $totals = $calculator->calculateFromItems($items);

    // Subtotal: 2 * $33.33 = $66.66
    expect($totals->subtotal)->toBe(6666);

    // Tax: 10% of $66.66 = $6.666, rounded to $6.67
    expect($totals->tax)->toBe(667);

    // Total: $66.66 + $6.67 = $73.33
    expect($totals->total)->toBe(7333);
});

test('invoice calculator handles null tax rates', function () {
    $calculator = new InvoiceCalculator;

    $items = collect([
        new InvoiceItem([
            'description' => 'Null Tax Item',
            'quantity' => 3,
            'unit_price' => 8000, // $80 each
            'tax_rate' => null,
        ]),
    ]);

    $totals = $calculator->calculateFromItems($items);

    // Subtotal: 3 * $80 = $240
    expect($totals->subtotal)->toBe(24000);

    // Tax: null tax rate should be treated as 0
    expect($totals->tax)->toBe(0);

    // Total: $240 + $0 = $240
    expect($totals->total)->toBe(24000);
});

test('invoice calculator handles very high tax rates', function () {
    $calculator = new InvoiceCalculator;

    $items = collect([
        new InvoiceItem([
            'description' => 'Luxury Tax Item',
            'quantity' => 1,
            'unit_price' => 10000, // $100
            'tax_rate' => 10000, // 100% tax rate in basis points
        ]),
    ]);

    $totals = $calculator->calculateFromItems($items);

    expect($totals->subtotal)->toBe(10000);
    expect($totals->tax)->toBe(10000); // 100% of $100 = $100
    expect($totals->total)->toBe(20000); // $100 + $100 = $200
});

test('invoice calculator precision with small amounts', function () {
    $calculator = new InvoiceCalculator;

    $items = collect([
        new InvoiceItem([
            'description' => 'Small Amount Item',
            'quantity' => 1,
            'unit_price' => 1, // $0.01
            'tax_rate' => 725, // 7.25% in basis points
        ]),
    ]);

    $totals = $calculator->calculateFromItems($items);

    expect($totals->subtotal)->toBe(1);
    // Tax: 7.25% of $0.01 = $0.000725, rounded to $0.00
    expect($totals->tax)->toBe(0);
    expect($totals->total)->toBe(1);
});

test('invoice totals value object can be serialized', function () {
    $totals = new InvoiceTotals(10000, 1800, 11800);

    $array = $totals->toArray();

    expect($array)->toBe([
        'subtotal' => 10000,
        'tax' => 1800,
        'total' => 11800,
    ]);
});

test('invoice totals zero factory creates correct object', function () {
    $totals = InvoiceTotals::zero();

    expect($totals->subtotal)->toBe(0);
    expect($totals->tax)->toBe(0);
    expect($totals->total)->toBe(0);
});
