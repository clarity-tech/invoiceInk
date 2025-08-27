<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\InvoiceCalculator;
use App\ValueObjects\InvoiceTotals;
use Illuminate\Support\Collection;

test('calculates invoice with no items', function () {
    $invoice = new Invoice;
    $invoice->setRelation('items', new Collection);

    $calculator = new InvoiceCalculator;
    $result = $calculator->calculateInvoice($invoice);

    expect($result)->toBeInstanceOf(InvoiceTotals::class);
    expect($result->subtotal)->toBe(0);
    expect($result->tax)->toBe(0);
    expect($result->total)->toBe(0);
});

test('calculates invoice with items without tax', function () {
    $item1 = new InvoiceItem([
        'quantity' => 2,
        'unit_price' => 1000, // $10.00
        'tax_rate' => null,
    ]);

    $item2 = new InvoiceItem([
        'quantity' => 1,
        'unit_price' => 1500, // $15.00
        'tax_rate' => null,
    ]);

    $invoice = new Invoice;
    $invoice->setRelation('items', new Collection([$item1, $item2]));

    $calculator = new InvoiceCalculator;
    $result = $calculator->calculateInvoice($invoice);

    expect($result)->toBeInstanceOf(InvoiceTotals::class);
    expect($result->subtotal)->toBe(3500); // $35.00
    expect($result->tax)->toBe(0);
    expect($result->total)->toBe(3500);
});

test('calculates invoice with items with tax', function () {
    $item1 = new InvoiceItem([
        'quantity' => 2,
        'unit_price' => 1000, // $10.00
        'tax_rate' => 10, // 10% as users would enter
    ]);

    $item2 = new InvoiceItem([
        'quantity' => 1,
        'unit_price' => 1500, // $15.00
        'tax_rate' => 20, // 20% as users would enter
    ]);

    $invoice = new Invoice;
    $invoice->setRelation('items', new Collection([$item1, $item2]));

    $calculator = new InvoiceCalculator;
    $result = $calculator->calculateInvoice($invoice);

    expect($result)->toBeInstanceOf(InvoiceTotals::class);
    expect($result->subtotal)->toBe(3500); // $35.00
    expect($result->tax)->toBe(500); // $2.00 + $3.00 = $5.00
    expect($result->total)->toBe(4000); // $40.00
});

test('calculates from items collection', function () {
    $items = new Collection([
        new InvoiceItem([
            'quantity' => 1,
            'unit_price' => 1000,
            'tax_rate' => 10, // 10% as users would enter
        ]),
        new InvoiceItem([
            'quantity' => 2,
            'unit_price' => 500,
            'tax_rate' => 5, // 5% as users would enter
        ]),
    ]);

    $calculator = new InvoiceCalculator;
    $result = $calculator->calculateFromItems($items);

    expect($result)->toBeInstanceOf(InvoiceTotals::class);
    expect($result->subtotal)->toBe(2000); // $10.00 + $10.00 = $20.00
    expect($result->tax)->toBe(150); // $1.00 + $0.50 = $1.50
    expect($result->total)->toBe(2150); // $21.50
});

test('updates invoice totals', function () {
    $item = new InvoiceItem([
        'quantity' => 1,
        'unit_price' => 1000,
        'tax_rate' => 10, // 10% as users would enter
    ]);

    $invoice = new Invoice([
        'subtotal' => 0,
        'tax' => 0,
        'total' => 0,
    ]);
    $invoice->setRelation('items', new Collection([$item]));

    $calculator = new InvoiceCalculator;
    $updatedInvoice = $calculator->updateInvoiceTotals($invoice);

    expect($updatedInvoice->subtotal)->toBe(1000);
    expect($updatedInvoice->tax)->toBe(100);
    expect($updatedInvoice->total)->toBe(1100);
});

test('invoice totals value object has zero factory method', function () {
    $totals = InvoiceTotals::zero();

    expect($totals->subtotal)->toBe(0);
    expect($totals->tax)->toBe(0);
    expect($totals->total)->toBe(0);
});

test('invoice totals value object can convert to array', function () {
    $totals = new InvoiceTotals(1000, 100, 1100);

    expect($totals->toArray())->toBe([
        'subtotal' => 1000,
        'tax' => 100,
        'total' => 1100,
    ]);
});

test('recalculate invoice refreshes data and updates totals', function () {
    $invoice = createInvoiceWithItems([
        'subtotal' => 0,
        'tax' => 0,
        'total' => 0,
    ], [
        [
            'description' => 'Test Service',
            'quantity' => 2,
            'unit_price' => 1500,
            'tax_rate' => 18,
        ],
    ]);

    $calculator = new InvoiceCalculator;
    $recalculatedInvoice = $calculator->recalculateInvoice($invoice);

    expect($recalculatedInvoice->subtotal)->toBe(3000);
    expect($recalculatedInvoice->tax)->toBe(540);
    expect($recalculatedInvoice->total)->toBe(3540);
});

test('recalculate invoice handles invoice with modified items', function () {
    $invoice = createInvoiceWithItems([
        'subtotal' => 1000,
        'tax' => 180,
        'total' => 1180,
    ], [
        [
            'description' => 'Original Service',
            'quantity' => 1,
            'unit_price' => 1000,
            'tax_rate' => 18,
        ],
    ]);

    $invoice->items->first()->update([
        'quantity' => 3,
        'unit_price' => 2000,
    ]);

    $calculator = new InvoiceCalculator;
    $recalculatedInvoice = $calculator->recalculateInvoice($invoice);

    expect($recalculatedInvoice->subtotal)->toBe(6000);
    expect($recalculatedInvoice->tax)->toBe(1080);
    expect($recalculatedInvoice->total)->toBe(7080);
});

test('recalculate invoice handles removal of items', function () {
    $invoice = createInvoiceWithItems([
        'subtotal' => 5000,
        'tax' => 900,
        'total' => 5900,
    ], [
        [
            'description' => 'Service 1',
            'quantity' => 1,
            'unit_price' => 2000,
            'tax_rate' => 18,
        ],
        [
            'description' => 'Service 2',
            'quantity' => 1,
            'unit_price' => 3000,
            'tax_rate' => 18,
        ],
    ]);

    $invoice->items->first()->delete();

    $calculator = new InvoiceCalculator;
    $recalculatedInvoice = $calculator->recalculateInvoice($invoice);

    expect($recalculatedInvoice->subtotal)->toBe(3000);
    expect($recalculatedInvoice->tax)->toBe(540);
    expect($recalculatedInvoice->total)->toBe(3540);
});

test('recalculate invoice handles addition of new items', function () {
    $invoice = createInvoiceWithItems([
        'subtotal' => 2000,
        'tax' => 360,
        'total' => 2360,
    ], [
        [
            'description' => 'Original Service',
            'quantity' => 1,
            'unit_price' => 2000,
            'tax_rate' => 18,
        ],
    ]);

    $invoice->items()->create([
        'description' => 'New Service',
        'quantity' => 1,
        'unit_price' => 1500,
        'tax_rate' => 12,
    ]);

    $calculator = new InvoiceCalculator;
    $recalculatedInvoice = $calculator->recalculateInvoice($invoice);

    expect($recalculatedInvoice->subtotal)->toBe(3500);
    expect($recalculatedInvoice->tax)->toBe(540);
    expect($recalculatedInvoice->total)->toBe(4040);
});

test('calculator works with persistent invoice models', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-PERSIST',
        'subtotal' => 1000,
        'tax' => 100,
        'total' => 1100,
    ], [
        [
            'description' => 'Database Service',
            'quantity' => 1,
            'unit_price' => 1000,
            'tax_rate' => 10,
        ],
    ]);

    $calculator = new InvoiceCalculator;

    $freshInvoice = Invoice::find($invoice->id);
    $totals = $calculator->calculateInvoice($freshInvoice);

    expect($totals->subtotal)->toBe(1000);
    expect($totals->tax)->toBe(100);
    expect($totals->total)->toBe(1100);
});

test('calculator handles complex integration scenario', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-COMPLEX',
        'subtotal' => 0,
        'tax' => 0,
        'total' => 0,
    ], [
        [
            'description' => 'Consulting',
            'quantity' => 10,
            'unit_price' => 12500,
            'tax_rate' => 18,
        ],
        [
            'description' => 'Development',
            'quantity' => 5,
            'unit_price' => 25000,
            'tax_rate' => 18,
        ],
        [
            'description' => 'Testing',
            'quantity' => 3,
            'unit_price' => 8000,
            'tax_rate' => 12,
        ],
    ]);

    $calculator = new InvoiceCalculator;

    $calculatedTotals = $calculator->calculateInvoice($invoice);
    $updatedInvoice = $calculator->updateInvoiceTotals($invoice);

    $expectedSubtotal = (10 * 12500) + (5 * 25000) + (3 * 8000);
    $expectedTax = (125000 * 18 / 100) + (125000 * 18 / 100) + (24000 * 12 / 100);
    $expectedTotal = $expectedSubtotal + $expectedTax;

    expect($calculatedTotals->subtotal)->toBe($expectedSubtotal);
    expect($calculatedTotals->tax)->toBe($expectedTax);
    expect($calculatedTotals->total)->toBe($expectedTotal);

    expect($updatedInvoice->subtotal)->toBe($expectedSubtotal);
    expect($updatedInvoice->tax)->toBe($expectedTax);
    expect($updatedInvoice->total)->toBe($expectedTotal);
});

test('calculator handles zero-value items in collections', function () {
    $calculator = new InvoiceCalculator;

    $items = new Collection([
        new InvoiceItem([
            'description' => 'Free consultation',
            'quantity' => 1,
            'unit_price' => 0,
            'tax_rate' => 0,
        ]),
        new InvoiceItem([
            'description' => 'Paid service',
            'quantity' => 1,
            'unit_price' => 1000,
            'tax_rate' => 18,
        ]),
    ]);

    $totals = $calculator->calculateFromItems($items);

    expect($totals->subtotal)->toBe(1000);
    expect($totals->tax)->toBe(180);
    expect($totals->total)->toBe(1180);
});
