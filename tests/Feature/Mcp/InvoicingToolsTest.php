<?php

use App\Mcp\Servers\InvoicingServer;
use App\Mcp\Tools\GetInvoiceTool;
use App\Mcp\Tools\ListCustomersTool;
use App\Mcp\Tools\ListInvoicesTool;
use App\Mcp\Tools\ListOrganizationsTool;
use App\Mcp\Tools\OutstandingReportTool;
use App\Mcp\Tools\RevenueSummaryTool;
use App\Models\Payment;
use Illuminate\Testing\Fluent\AssertableJson;

/*
|--------------------------------------------------------------------------
| list-invoices
|--------------------------------------------------------------------------
*/

test('list-invoices returns the organization\'s invoices', function () {
    $organization = createOrganizationWithLocation();
    createInvoiceWithItems(['invoice_number' => 'INV-1001'], null, $organization);

    InvoicingServer::actingAs($organization->owner)
        ->tool(ListInvoicesTool::class)
        ->assertOk()
        ->assertSee('INV-1001');
});

test('list-invoices excludes estimates unless asked for them', function () {
    $organization = createOrganizationWithLocation();
    createInvoiceWithItems(['invoice_number' => 'INV-2001'], null, $organization);
    createInvoiceWithItems(['invoice_number' => 'EST-2001', 'type' => 'estimate'], null, $organization);

    $server = InvoicingServer::actingAs($organization->owner);

    $server->tool(ListInvoicesTool::class)
        ->assertOk()
        ->assertSee('INV-2001')
        ->assertDontSee('EST-2001');

    InvoicingServer::actingAs($organization->owner)
        ->tool(ListInvoicesTool::class, ['type' => 'estimate'])
        ->assertOk()
        ->assertSee('EST-2001')
        ->assertDontSee('INV-2001');

    InvoicingServer::actingAs($organization->owner)
        ->tool(ListInvoicesTool::class, ['type' => 'all'])
        ->assertOk()
        ->assertSee('INV-2001')
        ->assertSee('EST-2001');
});

test('list-invoices filters by status', function () {
    $organization = createOrganizationWithLocation();
    createInvoiceWithItems(['invoice_number' => 'INV-DRAFT', 'status' => 'draft'], null, $organization);
    createInvoiceWithItems(['invoice_number' => 'INV-PAID', 'status' => 'paid'], null, $organization);

    InvoicingServer::actingAs($organization->owner)
        ->tool(ListInvoicesTool::class, ['status' => 'paid'])
        ->assertOk()
        ->assertSee('INV-PAID')
        ->assertDontSee('INV-DRAFT');
});

test('list-invoices can return only overdue invoices', function () {
    $organization = createOrganizationWithLocation();

    createInvoiceWithItems([
        'invoice_number' => 'INV-LATE',
        'status' => 'sent',
        'due_at' => now()->subDays(10),
    ], null, $organization);

    createInvoiceWithItems([
        'invoice_number' => 'INV-FUTURE',
        'status' => 'sent',
        'due_at' => now()->addDays(10),
    ], null, $organization);

    InvoicingServer::actingAs($organization->owner)
        ->tool(ListInvoicesTool::class, ['overdue' => true])
        ->assertOk()
        ->assertSee('INV-LATE')
        ->assertDontSee('INV-FUTURE');
});

test('list-invoices filters by customer name', function () {
    $organization = createOrganizationWithLocation();
    $acme = createCustomerWithLocation(['name' => 'Acme Industries'], [], $organization);
    $globex = createCustomerWithLocation(['name' => 'Globex Corp'], [], $organization);

    createInvoiceWithItems(['invoice_number' => 'INV-ACME'], null, $organization, $acme);
    createInvoiceWithItems(['invoice_number' => 'INV-GLOBEX'], null, $organization, $globex);

    InvoicingServer::actingAs($organization->owner)
        ->tool(ListInvoicesTool::class, ['customer' => 'Acme'])
        ->assertOk()
        ->assertSee('INV-ACME')
        ->assertDontSee('INV-GLOBEX');
});

test('list-invoices reports when results were truncated by the limit', function () {
    $organization = createOrganizationWithLocation();

    foreach (range(1, 5) as $i) {
        createInvoiceWithItems(['invoice_number' => "INV-30{$i}"], null, $organization);
    }

    InvoicingServer::actingAs($organization->owner)
        ->tool(ListInvoicesTool::class, ['limit' => 2])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json
            ->where('matching_count', 5)
            ->where('returned_count', 2)
            ->where('truncated', true)
            ->etc());
});

test('list-invoices rejects a limit above the maximum with a usable message', function () {
    $organization = createOrganizationWithLocation();

    InvoicingServer::actingAs($organization->owner)
        ->tool(ListInvoicesTool::class, ['limit' => 5000])
        ->assertHasErrors();
});

/*
|--------------------------------------------------------------------------
| get-invoice
|--------------------------------------------------------------------------
*/

test('get-invoice returns line items and payments', function () {
    $organization = createOrganizationWithLocation();

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-4001',
        'status' => 'partially_paid',
        'total' => 11800,
        'amount_paid' => 3000,
    ], [
        ['description' => 'Consulting retainer', 'quantity' => 2, 'unit_price' => 5000, 'tax_rate' => 18.00],
    ], $organization);

    Payment::create([
        'invoice_id' => $invoice->id,
        'amount' => 3000,
        'currency' => 'INR',
        'payment_date' => now(),
        'payment_method' => 'bank_transfer',
        'reference' => 'NEFT-99',
    ]);

    InvoicingServer::actingAs($organization->owner)
        ->tool(GetInvoiceTool::class, ['identifier' => 'INV-4001'])
        ->assertOk()
        ->assertSee('Consulting retainer')
        ->assertSee('NEFT-99')
        ->assertStructuredContent(fn (AssertableJson $json) => $json
            ->where('invoice_number', 'INV-4001')
            ->where('totals.total_cents', 11800)
            ->where('totals.amount_paid_cents', 3000)
            ->where('totals.balance_cents', 8800)
            ->etc());
});

test('get-invoice can be looked up by ulid', function () {
    $organization = createOrganizationWithLocation();
    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-4002'], null, $organization);

    InvoicingServer::actingAs($organization->owner)
        ->tool(GetInvoiceTool::class, ['identifier' => $invoice->ulid])
        ->assertOk()
        ->assertSee('INV-4002');
});

test('get-invoice explains itself when the identifier matches nothing', function () {
    $organization = createOrganizationWithLocation();

    InvoicingServer::actingAs($organization->owner)
        ->tool(GetInvoiceTool::class, ['identifier' => 'INV-NOPE'])
        ->assertHasErrors()
        ->assertSee('list-invoices');
});

/*
|--------------------------------------------------------------------------
| list-customers
|--------------------------------------------------------------------------
*/

test('list-customers returns customers with their invoice counts', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation(['name' => 'Northwind Traders'], [], $organization);

    createInvoiceWithItems(['invoice_number' => 'INV-5001'], null, $organization, $customer);
    createInvoiceWithItems(['invoice_number' => 'INV-5002'], null, $organization, $customer);
    createInvoiceWithItems(['invoice_number' => 'EST-5001', 'type' => 'estimate'], null, $organization, $customer);

    InvoicingServer::actingAs($organization->owner)
        ->tool(ListCustomersTool::class)
        ->assertOk()
        ->assertSee('Northwind Traders')
        ->assertStructuredContent(fn (AssertableJson $json) => $json
            ->where('customers.0.name', 'Northwind Traders')
            // Estimates are not invoices and must not inflate the count.
            ->where('customers.0.invoice_count', 2)
            ->etc());
});

test('list-customers searches by name', function () {
    $organization = createOrganizationWithLocation();
    createCustomerWithLocation(['name' => 'Contoso Ltd'], [], $organization);
    createCustomerWithLocation(['name' => 'Fabrikam Inc'], [], $organization);

    InvoicingServer::actingAs($organization->owner)
        ->tool(ListCustomersTool::class, ['search' => 'Contoso'])
        ->assertOk()
        ->assertSee('Contoso Ltd')
        ->assertDontSee('Fabrikam Inc');
});

/*
|--------------------------------------------------------------------------
| list-organizations
|--------------------------------------------------------------------------
*/

test('list-organizations marks which organization is current', function () {
    $organization = createOrganizationWithLocation(['name' => 'Primary Org']);

    InvoicingServer::actingAs($organization->owner)
        ->tool(ListOrganizationsTool::class)
        ->assertOk()
        ->assertSee('Primary Org')
        ->assertStructuredContent(fn (AssertableJson $json) => $json
            ->where('current_organization_id', $organization->id)
            ->where('organizations.0.is_current', true)
            ->etc());
});

/*
|--------------------------------------------------------------------------
| outstanding-report
|--------------------------------------------------------------------------
*/

test('outstanding-report buckets invoices by how overdue they are', function () {
    $organization = createOrganizationWithLocation();

    $ages = [
        'INV-NOTDUE' => now()->addDays(5),
        'INV-15D' => now()->subDays(15),
        'INV-45D' => now()->subDays(45),
        'INV-75D' => now()->subDays(75),
        'INV-200D' => now()->subDays(200),
    ];

    foreach ($ages as $number => $dueAt) {
        createInvoiceWithItems([
            'invoice_number' => $number,
            'status' => 'sent',
            'due_at' => $dueAt,
            'total' => 10000,
            'amount_paid' => 0,
        ], null, $organization);
    }

    InvoicingServer::actingAs($organization->owner)
        ->tool(OutstandingReportTool::class)
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json
            ->where('open_invoice_count', 5)
            ->where('by_currency.0.total_outstanding_cents', 50000)
            ->where('by_currency.0.buckets.0.key', 'not_yet_due')
            ->where('by_currency.0.buckets.0.invoice_count', 1)
            ->where('by_currency.0.buckets.1.key', 'overdue_1_30')
            ->where('by_currency.0.buckets.1.invoice_count', 1)
            ->where('by_currency.0.buckets.2.key', 'overdue_31_60')
            ->where('by_currency.0.buckets.2.invoice_count', 1)
            ->where('by_currency.0.buckets.3.key', 'overdue_61_90')
            ->where('by_currency.0.buckets.3.invoice_count', 1)
            ->where('by_currency.0.buckets.4.key', 'overdue_90_plus')
            ->where('by_currency.0.buckets.4.invoice_count', 1)
            ->etc());
});

test('outstanding-report counts partially paid invoices, not just wholly unpaid ones', function () {
    $organization = createOrganizationWithLocation();

    // Invoice::unpaid() means "amount_paid = 0"; using it here would drop this
    // invoice entirely and understate what is owed.
    createInvoiceWithItems([
        'invoice_number' => 'INV-PART',
        'status' => 'partially_paid',
        'due_at' => now()->subDays(5),
        'total' => 10000,
        'amount_paid' => 4000,
    ], null, $organization);

    InvoicingServer::actingAs($organization->owner)
        ->tool(OutstandingReportTool::class)
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json
            ->where('open_invoice_count', 1)
            ->where('by_currency.0.total_outstanding_cents', 6000)
            ->etc());
});

test('outstanding-report excludes paid, void, and estimate documents', function () {
    $organization = createOrganizationWithLocation();

    createInvoiceWithItems([
        'invoice_number' => 'INV-SETTLED', 'status' => 'paid',
        'total' => 10000, 'amount_paid' => 10000, 'due_at' => now()->subDays(5),
    ], null, $organization);

    createInvoiceWithItems([
        'invoice_number' => 'INV-CANCELLED', 'status' => 'void',
        'total' => 10000, 'amount_paid' => 0, 'due_at' => now()->subDays(5),
    ], null, $organization);

    createInvoiceWithItems([
        'invoice_number' => 'EST-OPEN', 'type' => 'estimate', 'status' => 'sent',
        'total' => 10000, 'amount_paid' => 0, 'due_at' => now()->subDays(5),
    ], null, $organization);

    InvoicingServer::actingAs($organization->owner)
        ->tool(OutstandingReportTool::class)
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json
            ->where('open_invoice_count', 0)
            ->etc());
});

/*
|--------------------------------------------------------------------------
| revenue-summary
|--------------------------------------------------------------------------
*/

test('revenue-summary separates what was invoiced from what was collected', function () {
    $organization = createOrganizationWithLocation();

    // Raised last year, paid this month: collected counts it, invoiced does not.
    $old = createInvoiceWithItems([
        'invoice_number' => 'INV-OLD',
        'status' => 'paid',
        'issued_at' => now()->subYear(),
        'total' => 20000,
        'amount_paid' => 20000,
    ], null, $organization);

    Payment::create([
        'invoice_id' => $old->id,
        'amount' => 20000,
        'currency' => 'INR',
        'payment_date' => now(),
        'payment_method' => 'bank_transfer',
    ]);

    // Raised this month, unpaid: invoiced counts it, collected does not.
    createInvoiceWithItems([
        'invoice_number' => 'INV-NEW',
        'status' => 'sent',
        'issued_at' => now(),
        'total' => 30000,
        'amount_paid' => 0,
    ], null, $organization);

    InvoicingServer::actingAs($organization->owner)
        ->tool(RevenueSummaryTool::class, ['period' => 'this_month'])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json
            ->where('by_currency.0.invoiced_cents', 30000)
            ->where('by_currency.0.collected_cents', 20000)
            ->where('by_currency.0.outstanding_on_period_invoices_cents', 30000)
            ->etc());
});

test('revenue-summary excludes void invoices and estimates', function () {
    $organization = createOrganizationWithLocation();

    createInvoiceWithItems([
        'invoice_number' => 'INV-OK', 'status' => 'sent',
        'issued_at' => now(), 'total' => 10000, 'amount_paid' => 0,
    ], null, $organization);

    createInvoiceWithItems([
        'invoice_number' => 'INV-VOID', 'status' => 'void',
        'issued_at' => now(), 'total' => 55000, 'amount_paid' => 0,
    ], null, $organization);

    createInvoiceWithItems([
        'invoice_number' => 'EST-1', 'type' => 'estimate', 'status' => 'sent',
        'issued_at' => now(), 'total' => 77000, 'amount_paid' => 0,
    ], null, $organization);

    InvoicingServer::actingAs($organization->owner)
        ->tool(RevenueSummaryTool::class, ['period' => 'this_month'])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json
            ->where('by_currency.0.invoiced_cents', 10000)
            ->etc());
});

test('revenue-summary honours an explicit date range over the named period', function () {
    $organization = createOrganizationWithLocation();

    createInvoiceWithItems([
        'invoice_number' => 'INV-JAN', 'status' => 'sent',
        'issued_at' => now()->subMonths(6), 'total' => 12345, 'amount_paid' => 0,
    ], null, $organization);

    InvoicingServer::actingAs($organization->owner)
        ->tool(RevenueSummaryTool::class, [
            'from' => now()->subMonths(7)->toDateString(),
            'to' => now()->subMonths(5)->toDateString(),
        ])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json
            ->where('by_currency.0.invoiced_cents', 12345)
            ->etc());
});

test('revenue-summary rejects a range that ends before it starts', function () {
    $organization = createOrganizationWithLocation();

    InvoicingServer::actingAs($organization->owner)
        ->tool(RevenueSummaryTool::class, [
            'from' => now()->toDateString(),
            'to' => now()->subMonth()->toDateString(),
        ])
        ->assertHasErrors();
});
