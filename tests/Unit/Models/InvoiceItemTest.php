<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create invoice item with all fields', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-001',
        'status' => 'draft',
    ]);

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Test Service',
        'quantity' => 2,
        'unit_price' => 1000,
        'tax_rate' => 1800, // 18% in basis points
    ]);

    expect($item->invoice_id)->toBe($invoice->id);
    expect($item->description)->toBe('Test Service');
    expect($item->quantity)->toBe(2);
    expect($item->unit_price)->toBe(1000);
    expect($item->tax_rate)->toBe(1800); // Should return basis points as integer
    expect($item->formatted_tax_rate)->toBe('18.00%'); // Should format for display
});

test('invoice item belongs to invoice', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-001',
        'status' => 'draft',
    ]);

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Test Service',
        'quantity' => 1,
        'unit_price' => 1000,
        'tax_rate' => 1800, // 18% in basis points
    ]);

    expect($item->invoice)->not->toBeNull();
    expect($item->invoice->id)->toBe($invoice->id);
    expect($item->invoice->invoice_number)->toBe('INV-001');
});

test('invoice item can have zero tax rate', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-001',
        'status' => 'draft',
        'subtotal' => 1000,
        'tax' => 0,
        'total' => 1000,
    ]);

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Tax-free Service',
        'quantity' => 1,
        'unit_price' => 1000,
        'tax_rate' => 0,
    ]);

    expect($item->tax_rate)->toBe(0);
});

test('invoice item can have null tax rate', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-001',
        'status' => 'draft',
        'subtotal' => 1000,
        'tax' => 0,
        'total' => 1000,
    ]);

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Service without tax',
        'quantity' => 1,
        'unit_price' => 1000,
        'tax_rate' => null,
    ]);

    expect($item->tax_rate)->toBeNull();
});

test('invoice item fillable attributes work correctly', function () {
    $data = [
        'invoice_id' => 1,
        'description' => 'Test Product',
        'quantity' => 3,
        'unit_price' => 2500,
        'tax_rate' => 1200, // 12% in basis points
    ];

    $item = new InvoiceItem($data);

    expect($item->invoice_id)->toBe(1);
    expect($item->description)->toBe('Test Product');
    expect($item->quantity)->toBe(3);
    expect($item->unit_price)->toBe(2500);
    expect($item->tax_rate)->toBe(1200); // 12% in basis points
});

test('invoice item calculates line total correctly', function () {
    $invoice = createInvoiceWithItems();

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Test Item',
        'quantity' => 2,
        'unit_price' => 1000,
        'tax_rate' => 1800, // 18% in basis points
    ]);

    $lineSubtotal = $item->quantity * $item->unit_price;
    // Tax calculation: use the model's method which handles the conversion properly
    $lineTax = $item->getTaxAmount();
    $lineTotal = $lineSubtotal + $lineTax;

    expect($lineSubtotal)->toBe(2000);
    expect($lineTax)->toBe(360);
    expect($lineTotal)->toBe(2360);
});

test('invoice item handles large quantities and prices', function () {
    $invoice = createInvoiceWithItems();

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Bulk Service',
        'quantity' => 1000,
        'unit_price' => 500000, // $5000.00 in cents
        'tax_rate' => 1800, // 18% in basis points
    ]);

    expect($item->quantity)->toBe(1000);
    expect($item->unit_price)->toBe(500000);

    $lineSubtotal = $item->quantity * $item->unit_price;
    expect($lineSubtotal)->toBe(500000000); // $5,000,000.00 in cents
});

test('invoice item can have fractional tax rates', function () {
    $invoice = createInvoiceWithItems();

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Service with custom tax',
        'quantity' => 1,
        'unit_price' => 10000,
        'tax_rate' => 1250, // 12.5% in basis points
    ]);

    expect($item->tax_rate)->toBe(1250); // 12.50% in basis points
});

test('invoice item has correct fillable attributes', function () {
    $item = new InvoiceItem;
    $fillable = $item->getFillable();

    $expectedFillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
    ];

    foreach ($expectedFillable as $field) {
        expect($fillable)->toContain($field);
    }
});

test('invoice item casts method returns correct array', function () {
    $item = new InvoiceItem;
    $casts = $item->getCasts();

    expect($casts['tax_rate'])->toBe('integer');
});

test('invoice item uses HasFactory trait', function () {
    $item = new InvoiceItem;
    expect(in_array(\Illuminate\Database\Eloquent\Factories\HasFactory::class, class_uses($item)))->toBeTrue();
});

test('invoice item factory creates valid instances', function () {
    $invoice = createInvoiceWithItems();

    $item = InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
    ]);

    expect($item)->toBeInstanceOf(InvoiceItem::class);
    expect($item->description)->not->toBeEmpty();
    expect($item->quantity)->toBeInt();
    expect($item->unit_price)->toBeInt();
    expect($item->invoice_id)->toBeInt();
});

test('invoice item relationship is correctly configured', function () {
    $item = new InvoiceItem;

    // Test invoice relationship
    $invoiceRelation = $item->invoice();
    expect($invoiceRelation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});

test('invoice item getLineTotal calculates correctly', function () {
    $invoice = createInvoiceWithItems();

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Test Item',
        'quantity' => 5,
        'unit_price' => 2000,
        'tax_rate' => 0,
    ]);

    expect($item->getLineTotal())->toBe(10000); // 5 * 2000 = 10000
});

test('invoice item getTaxAmount calculates correctly with tax', function () {
    $invoice = createInvoiceWithItems();

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Taxed Item',
        'quantity' => 1,
        'unit_price' => 10000,
        'tax_rate' => 1800, // 18% in basis points
    ]);

    $expectedTax = (int) round((10000 * 18.00) / 100);
    expect($item->getTaxAmount())->toBe($expectedTax);
    expect($item->getTaxAmount())->toBe(1800);
});

test('invoice item getTaxAmount returns zero with null tax rate', function () {
    $invoice = createInvoiceWithItems();

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'No Tax Item',
        'quantity' => 1,
        'unit_price' => 10000,
        'tax_rate' => null,
    ]);

    expect($item->getTaxAmount())->toBe(0);
});

test('invoice item getTaxAmount returns zero with zero tax rate', function () {
    $invoice = createInvoiceWithItems();

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Zero Tax Item',
        'quantity' => 1,
        'unit_price' => 10000,
        'tax_rate' => 0, // 0% in basis points
    ]);

    expect($item->getTaxAmount())->toBe(0);
});

test('invoice item getLineTotalWithTax calculates correctly', function () {
    $invoice = createInvoiceWithItems();

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Full Test Item',
        'quantity' => 2,
        'unit_price' => 5000,
        'tax_rate' => 1000, // 10% in basis points
    ]);

    $lineTotal = $item->getLineTotal(); // 2 * 5000 = 10000
    $taxAmount = $item->getTaxAmount(); // (10000 * 10) / 100 = 1000
    $totalWithTax = $item->getLineTotalWithTax(); // 10000 + 1000 = 11000

    expect($lineTotal)->toBe(10000);
    expect($taxAmount)->toBe(1000);
    expect($totalWithTax)->toBe(11000);
});

test('invoice item handles fractional tax calculations', function () {
    $invoice = createInvoiceWithItems();

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Fractional Tax Item',
        'quantity' => 1,
        'unit_price' => 10000,
        'tax_rate' => 1250, // 12.5% in basis points
    ]);

    $expectedTax = (int) round((10000 * 12.50) / 100);
    expect($item->getTaxAmount())->toBe($expectedTax);
    expect($item->getTaxAmount())->toBe(1250);
    expect($item->getLineTotalWithTax())->toBe(11250);
});

test('invoice item handles complex tax calculations', function () {
    $testCases = [
        ['quantity' => 3, 'unit_price' => 3333, 'tax_rate' => 1800, 'expected_line_total' => 9999, 'expected_tax' => 1800],
        ['quantity' => 1, 'unit_price' => 99999, 'tax_rate' => 550, 'expected_line_total' => 99999, 'expected_tax' => 5500],
        ['quantity' => 7, 'unit_price' => 1428, 'tax_rate' => 2800, 'expected_line_total' => 9996, 'expected_tax' => 2799],
    ];

    foreach ($testCases as $testCase) {
        $invoice = createInvoiceWithItems();

        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Complex Tax Test',
            'quantity' => $testCase['quantity'],
            'unit_price' => $testCase['unit_price'],
            'tax_rate' => $testCase['tax_rate'],
        ]);

        expect($item->getLineTotal())->toBe($testCase['expected_line_total']);
        expect($item->getTaxAmount())->toBe($testCase['expected_tax']);
        expect($item->getLineTotalWithTax())->toBe($testCase['expected_line_total'] + $testCase['expected_tax']);
    }
});

test('invoice item can handle very large quantities and prices', function () {
    $invoice = createInvoiceWithItems();

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Large Values Item',
        'quantity' => 999999,
        'unit_price' => 999999,
        'tax_rate' => 1800, // 18% in basis points
    ]);

    $expectedLineTotal = 999999 * 999999;
    $expectedTax = (int) round(($expectedLineTotal * 18.00) / 100);

    expect($item->getLineTotal())->toBe($expectedLineTotal);
    expect($item->getTaxAmount())->toBe($expectedTax);
    expect($item->getLineTotalWithTax())->toBe($expectedLineTotal + $expectedTax);
});

test('invoice item can handle zero values', function () {
    $invoice = createInvoiceWithItems();

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Free Item',
        'quantity' => 1,
        'unit_price' => 0,
        'tax_rate' => 1800, // 18% in basis points
    ]);

    expect($item->getLineTotal())->toBe(0);
    expect($item->getTaxAmount())->toBe(0);
    expect($item->getLineTotalWithTax())->toBe(0);
});

test('invoice item can handle zero quantity', function () {
    $invoice = createInvoiceWithItems();

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Zero Quantity Item',
        'quantity' => 0,
        'unit_price' => 5000,
        'tax_rate' => 1800, // 18% in basis points
    ]);

    expect($item->getLineTotal())->toBe(0);
    expect($item->getTaxAmount())->toBe(0);
    expect($item->getLineTotalWithTax())->toBe(0);
});

test('invoice item tax rate precision is maintained', function () {
    $invoice = createInvoiceWithItems();

    // Test various rates stored as basis points
    $testCases = [
        ['rate' => 1, 'percentage' => 0.01],    // 0.01% = 1 basis point
        ['rate' => 10, 'percentage' => 0.10],   // 0.10% = 10 basis points
        ['rate' => 125, 'percentage' => 1.25],  // 1.25% = 125 basis points
        ['rate' => 1250, 'percentage' => 12.50], // 12.50% = 1250 basis points
        ['rate' => 2800, 'percentage' => 28.00], // 28.00% = 2800 basis points
        ['rate' => 9999, 'percentage' => 99.99], // 99.99% = 9999 basis points
    ];

    foreach ($testCases as $test) {
        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => "Precision Test {$test['percentage']}%",
            'quantity' => 1,
            'unit_price' => 1000,
            'tax_rate' => $test['rate'], // Store as basis points
        ]);

        expect($item->tax_rate)->toBe($test['rate']); // Should return basis points as integer
        expect($item->formatted_tax_rate)->toBe(number_format($test['percentage'], 2).'%'); // Should format for display
    }
});

test('invoice item can be created without tax rate', function () {
    $invoice = createInvoiceWithItems();

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'No Tax Rate Item',
        'quantity' => 1,
        'unit_price' => 1000,
        // tax_rate not provided
    ]);

    expect($item->tax_rate)->toBeNull();
    expect($item->getTaxAmount())->toBe(0);
    expect($item->getLineTotalWithTax())->toBe(1000);
});

test('invoice item can be updated after creation', function () {
    $invoice = createInvoiceWithItems();

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Original Description',
        'quantity' => 1,
        'unit_price' => 1000,
        'tax_rate' => 10.00,
    ]);

    $item->update([
        'description' => 'Updated Description',
        'quantity' => 3,
        'unit_price' => 2000,
        'tax_rate' => 1800, // 18% in basis points
    ]);

    expect($item->description)->toBe('Updated Description');
    expect($item->quantity)->toBe(3);
    expect($item->unit_price)->toBe(2000);
    expect($item->tax_rate)->toBe(1800); // 18% in basis points
    expect($item->getLineTotal())->toBe(6000);
    expect($item->getTaxAmount())->toBe(1080);
    expect($item->getLineTotalWithTax())->toBe(7080);
});

test('invoice item belongs to invoice correctly', function () {
    $invoice = createInvoiceWithItems();

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Relationship Test Item',
        'quantity' => 1,
        'unit_price' => 1000,
        'tax_rate' => 0,
    ]);

    expect($item->invoice)->toBeInstanceOf(Invoice::class);
    expect($item->invoice->id)->toBe($invoice->id);
    expect($item->invoice_id)->toBe($invoice->id);
});

test('invoice item handles empty description', function () {
    $invoice = createInvoiceWithItems();

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => '', // Use empty string instead of null
        'quantity' => 1,
        'unit_price' => 1000,
        'tax_rate' => 0,
    ]);

    expect($item->description)->toBe('');
});

test('invoice item mass assignment works correctly', function () {
    $invoice = createInvoiceWithItems();

    $data = [
        'invoice_id' => $invoice->id,
        'description' => 'Mass Assignment Test',
        'quantity' => 5,
        'unit_price' => 1500,
        'tax_rate' => 1575, // 15.75% in basis points
    ];

    $item = new InvoiceItem($data);

    expect($item->invoice_id)->toBe($invoice->id);
    expect($item->description)->toBe('Mass Assignment Test');
    expect($item->quantity)->toBe(5);
    expect($item->unit_price)->toBe(1500);
    expect($item->tax_rate)->toBe(1575); // 15.75% in basis points
});

test('invoice item business logic methods work with edge cases', function () {
    $invoice = createInvoiceWithItems();

    // Test with very small tax rate
    $item1 = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Small Tax',
        'quantity' => 1,
        'unit_price' => 100,
        'tax_rate' => 1, // 0.01% in basis points
    ]);

    expect($item1->getTaxAmount())->toBe(0); // Should round to 0

    // Test with high precision calculation
    $item2 = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'High Precision',
        'quantity' => 3,
        'unit_price' => 3333,
        'tax_rate' => 1833, // 18.33% in basis points
    ]);

    $expectedTax = (int) round((9999 * 18.33) / 100);
    expect($item2->getTaxAmount())->toBe($expectedTax);
});
