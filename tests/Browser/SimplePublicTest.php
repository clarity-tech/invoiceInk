<?php

use Laravel\Dusk\Browser;

test('test basic route access', function () {
    $this->browse(function (Browser $browser) {
        // Test if basic route works
        $browser->visit('/')
            ->screenshot('basic_route_test');
    });
});

test('simple public route test', function () {
    // Create a simple invoice manually
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'status' => 'sent',
        'invoice_number' => 'INV-SIMPLE-001',
    ], [
        [
            'description' => 'Simple Test Service',
            'quantity' => 1,
            'unit_price' => 10000,
            'tax_rate' => 18,
        ],
    ]);

    // Debug: verify the invoice was created
    expect($invoice->ulid)->not()->toBeEmpty();
    expect($invoice->invoice_number)->toBe('INV-SIMPLE-001');

    // Also verify the route exists by checking if it passes basic feature tests
    $response = $this->get("/invoices/{$invoice->ulid}");
    expect($response->status())->toBe(200);

    $this->browse(function (Browser $browser) use ($invoice) {
        $url = "/invoices/{$invoice->ulid}";
        $browser->visit($url)
            ->screenshot('simple_public_invoice_debug')
            ->assertSee($invoice->invoice_number);
    });
});
