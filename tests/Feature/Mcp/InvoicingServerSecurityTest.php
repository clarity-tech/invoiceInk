<?php

use App\Mcp\Servers\InvoicingServer;
use App\Mcp\Tools\GetInvoiceTool;
use App\Mcp\Tools\ListCustomersTool;
use App\Mcp\Tools\ListInvoicesTool;
use App\Mcp\Tools\ListOrganizationsTool;
use App\Mcp\Tools\OutstandingReportTool;
use App\Mcp\Tools\RevenueSummaryTool;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Passport\Passport;

/**
 * Builds two organizations owned by different users, each with one invoice and
 * one payment, so cross-organization leakage is detectable.
 *
 * @return array{0: Organization, 1: Organization}
 */
function twoRivalOrganizations(): array
{
    $mine = createOrganizationWithLocation(['name' => 'Mine Ltd']);
    $theirs = createOrganizationWithLocation(['name' => 'Theirs Ltd']);

    $myCustomer = createCustomerWithLocation(['name' => 'My Customer'], [], $mine);
    $theirCustomer = createCustomerWithLocation(['name' => 'Their Customer'], [], $theirs);

    $myInvoice = createInvoiceWithItems([
        'invoice_number' => 'INV-MINE-001',
        'status' => 'partially_paid',
        'issued_at' => now(),
        'due_at' => now()->addDays(15),
        'total' => 11800,
        'amount_paid' => 5000,
    ], null, $mine, $myCustomer);

    $theirInvoice = createInvoiceWithItems([
        'invoice_number' => 'INV-THEIRS-001',
        'status' => 'partially_paid',
        'issued_at' => now(),
        'due_at' => now()->addDays(15),
        'total' => 99999,
        'amount_paid' => 77777,
    ], null, $theirs, $theirCustomer);

    Payment::create([
        'invoice_id' => $myInvoice->id,
        'amount' => 5000,
        'currency' => 'INR',
        'payment_date' => now(),
        'payment_method' => 'bank_transfer',
    ]);

    Payment::create([
        'invoice_id' => $theirInvoice->id,
        'amount' => 77777,
        'currency' => 'INR',
        'payment_date' => now(),
        'payment_method' => 'bank_transfer',
    ]);

    return [$mine, $theirs];
}

/*
|--------------------------------------------------------------------------
| No authenticated user at all
|--------------------------------------------------------------------------
|
| This is the stdio condition, and the only case that exercises the explicit
| scoping. OrganizationScope applies no filter when auth()->check() is false,
| and PendingTestResponse::actingAs() always authenticates, so every actingAs
| test below would still pass with the guard removed. These would not.
|
*/

$allTools = [
    'list-organizations' => ListOrganizationsTool::class,
    'list-invoices' => ListInvoicesTool::class,
    'get-invoice' => GetInvoiceTool::class,
    'list-customers' => ListCustomersTool::class,
    'outstanding-report' => OutstandingReportTool::class,
    'revenue-summary' => RevenueSummaryTool::class,
];

foreach ($allTools as $toolName => $toolClass) {
    test("{$toolName} refuses to return data when no user is authenticated", function () use ($toolClass) {
        twoRivalOrganizations();

        expect(auth()->check())->toBeFalse();

        $arguments = $toolClass === GetInvoiceTool::class
            ? ['identifier' => 'INV-MINE-001']
            : [];

        InvoicingServer::tool($toolClass, $arguments)
            ->assertHasErrors()
            ->assertDontSee('INV-MINE-001')
            ->assertDontSee('INV-THEIRS-001');
    });
}

test('local stdio server refuses to resolve a user in production', function () {
    [$mine] = twoRivalOrganizations();

    config()->set('mcp.local_user_email', $mine->owner->email);
    app()->detectEnvironment(fn () => 'production');

    InvoicingServer::tool(ListInvoicesTool::class)
        ->assertHasErrors()
        ->assertDontSee('INV-MINE-001');
});

test('local stdio server resolves the configured user outside production', function () {
    [$mine] = twoRivalOrganizations();

    config()->set('mcp.local_user_email', $mine->owner->email);

    InvoicingServer::tool(ListInvoicesTool::class)
        ->assertOk()
        ->assertSee('INV-MINE-001')
        ->assertDontSee('INV-THEIRS-001');
});

/*
|--------------------------------------------------------------------------
| Cross-organization isolation
|--------------------------------------------------------------------------
*/

test('list-invoices never returns another organization\'s invoices', function () {
    [$mine] = twoRivalOrganizations();

    InvoicingServer::actingAs($mine->owner)
        ->tool(ListInvoicesTool::class, ['type' => 'all', 'limit' => 100])
        ->assertOk()
        ->assertSee('INV-MINE-001')
        ->assertDontSee('INV-THEIRS-001');
});

test('get-invoice cannot reach an invoice belonging to another organization', function () {
    [$mine] = twoRivalOrganizations();

    InvoicingServer::actingAs($mine->owner)
        ->tool(GetInvoiceTool::class, ['identifier' => 'INV-THEIRS-001'])
        ->assertHasErrors()
        ->assertDontSee('99999');
});

test('list-customers never returns another organization\'s customers', function () {
    [$mine] = twoRivalOrganizations();

    InvoicingServer::actingAs($mine->owner)
        ->tool(ListCustomersTool::class, ['limit' => 100])
        ->assertOk()
        ->assertSee('My Customer')
        ->assertDontSee('Their Customer');
});

test('list-organizations only lists organizations the user belongs to', function () {
    [$mine] = twoRivalOrganizations();

    InvoicingServer::actingAs($mine->owner)
        ->tool(ListOrganizationsTool::class)
        ->assertOk()
        ->assertSee('Mine Ltd')
        ->assertDontSee('Theirs Ltd');
});

/*
| The aggregate tools are the dangerous ones: Payment carries no organization_id
| and no global scope, so a cross-organization total is only detectable by the
| number being wrong. A string assertion would not catch it.
*/

test('revenue-summary totals only this organization\'s payments', function () {
    [$mine] = twoRivalOrganizations();

    InvoicingServer::actingAs($mine->owner)
        ->tool(RevenueSummaryTool::class, ['period' => 'this_month'])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json
            ->where('by_currency.0.currency', 'INR')
            ->where('by_currency.0.invoiced_cents', 11800)
            ->where('by_currency.0.collected_cents', 5000)
            ->where('by_currency.0.collected_payment_count', 1)
            ->etc());
});

test('outstanding-report totals only this organization\'s balances', function () {
    [$mine] = twoRivalOrganizations();

    InvoicingServer::actingAs($mine->owner)
        ->tool(OutstandingReportTool::class)
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json
            ->where('open_invoice_count', 1)
            ->where('by_currency.0.total_outstanding_cents', 6800)
            ->etc());
});

/*
|--------------------------------------------------------------------------
| Fail closed on a user without a current organization
|--------------------------------------------------------------------------
*/

test('a user belonging to no organization gets an error rather than every invoice', function () {
    twoRivalOrganizations();

    // Clearing current_team_id is not enough to reach this state: HasTeams::currentTeam()
    // heals a null by falling back to an owned team, then to a membership. Only a user
    // with neither stays null — a fresh signup, or someone removed from every org.
    $orphan = User::create([
        'name' => 'No Org User',
        'email' => 'orphan@example.test',
        'email_verified_at' => now(),
        'password' => 'password',
    ]);

    expect($orphan->fresh()->currentTeam)->toBeNull();

    InvoicingServer::actingAs($orphan->fresh())
        ->tool(ListInvoicesTool::class, ['type' => 'all'])
        ->assertHasErrors()
        ->assertDontSee('INV-MINE-001')
        ->assertDontSee('INV-THEIRS-001');
});

/*
|--------------------------------------------------------------------------
| HTTP transport
|--------------------------------------------------------------------------
*/

test('the web MCP endpoint rejects unauthenticated requests', function () {
    $this->postJson('/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/list',
    ])->assertUnauthorized();
});

test('a signed-in client reaches the web MCP endpoint', function () {
    $organization = createOrganizationWithLocation();
    Passport::actingAs($organization->owner, ['mcp:use']);

    // Covers what a 401-only test cannot: that the route is reachable by a real
    // client. If routes/ai.php were ever loaded inside the "web" group,
    // VerifyCsrfToken would 419 every external POST while staying silent here,
    // because that middleware short-circuits during unit tests.
    $this->postJson('/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/list',
    ])
        ->assertOk()
        ->assertSee('list-invoices');
});

test('the web endpoint is not registered at all when the flag is off', function () {
    // The suite runs with MCP_WEB_ENABLED=true (phpunit.xml) so the other HTTP
    // tests exercise a real route. This one rebuilds the application with the
    // flag off to prove the gate genuinely gates — a flag nothing tests is
    // decoration, and this is the switch that keeps the endpoint off the public
    // internet after deploy.
    $original = getenv('MCP_WEB_ENABLED');

    try {
        putenv('MCP_WEB_ENABLED=false');
        $_ENV['MCP_WEB_ENABLED'] = 'false';
        $_SERVER['MCP_WEB_ENABLED'] = 'false';

        $this->refreshApplication();

        expect(config('mcp.web_enabled'))->toBeFalse();

        $mcpRoutes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route): bool => $route->uri() === 'mcp');

        expect($mcpRoutes)->toBeEmpty();

        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
            ->assertNotFound();
    } finally {
        putenv('MCP_WEB_ENABLED='.($original === false ? '' : $original));
        $_ENV['MCP_WEB_ENABLED'] = $original === false ? 'true' : $original;
        $_SERVER['MCP_WEB_ENABLED'] = $_ENV['MCP_WEB_ENABLED'];
        $this->refreshApplication();
    }
});

test('the local stdio server is registered regardless of the web flag', function () {
    // The two transports are independent: turning the public endpoint off must
    // not take away local development access.
    expect(array_keys(Mcp::servers()))->toContain('invoicing');
});

test('the web MCP endpoint is not wrapped in session or CSRF middleware', function () {
    $middleware = collect(app('router')->getRoutes()->getRoutes())
        ->first(fn ($route) => $route->uri() === 'mcp' && in_array('POST', $route->methods(), true))
        ->gatherMiddleware();

    expect($middleware)->toContain('auth:api')
        ->and($middleware)->not->toContain('web')
        ->and($middleware)->not->toContain(VerifyCsrfToken::class)
        ->and($middleware)->not->toContain(StartSession::class);
});
