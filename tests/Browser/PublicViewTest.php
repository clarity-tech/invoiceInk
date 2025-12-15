<?php

it('loads public invoice view without authentication', function () {
    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-PUB-VIEW-001']);

    $page = $this->visit("/invoices/view/{$invoice->ulid}");

    $page->assertSee('INV-PUB-VIEW-001')
        ->assertNoJavascriptErrors();
});

it('loads public estimate view without authentication', function () {
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-PUB-VIEW-001',
    ]);

    $page = $this->visit("/estimates/view/{$estimate->ulid}");

    $page->assertSee('EST-PUB-VIEW-001')
        ->assertNoJavascriptErrors();
});

it('shows correct invoice data on public view', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-PUB-DATA-001',
    ]);

    $page = $this->visit("/invoices/view/{$invoice->ulid}");

    $page->assertSee('INV-PUB-DATA-001')
        ->assertSee($invoice->customer->name);
});
