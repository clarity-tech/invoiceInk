<?php

use App\Models\User;

it('renders dashboard for authenticated user', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('stats')
            ->has('statusBreakdown')
            ->has('recentInvoices')
            ->has('overdueInvoices')
            ->has('topCustomers')
            ->has('monthlyTrend')
            ->has('customerCount')
            ->has('estimateStats')
            ->where('period', 'this_month')
        );
});

it('shows zero stats when no invoices exist', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('stats.total_revenue', 0)
            ->where('stats.total_collected', 0)
            ->where('stats.total_outstanding', 0)
            ->where('stats.invoice_count', 0)
            ->where('stats.overdue_count', 0)
        );
});

it('calculates stats from invoices', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $org = createOrganizationWithLocation([], [], $user);
    $user->switchTeam($org);

    $this->actingAs($user);

    createInvoiceWithItems([
        'type' => 'invoice',
        'status' => 'sent',
        'issued_at' => now(),
        'amount_paid' => 50000,
    ], null, $org);

    $response = $this->get('/dashboard');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('stats.invoice_count', 1)
            ->where('stats.total_collected', 50000)
        );
});

it('can switch period filter', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    $this->get('/dashboard?period=this_year')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('period', 'this_year'));

    $this->get('/dashboard?period=all_time')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('period', 'all_time'));
});

it('shows customer count', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $org = $user->currentTeam;

    $this->actingAs($user);

    createCustomerWithLocation([], [], $org);
    createCustomerWithLocation([], [], $org);

    $response = $this->get('/dashboard');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->where('customerCount', 2));
});

it('converts multi-currency invoice totals to org currency using stored exchange rate', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $org = createOrganizationWithLocation(['currency' => 'INR'], [], $user);
    $user->switchTeam($org);
    $this->actingAs($user);

    // USD invoice: $100.00 with rate 80 INR/USD => 8000.00 INR = 800,000 paise
    createInvoiceWithItems([
        'type' => 'invoice',
        'status' => 'paid',
        'issued_at' => now(),
        'currency' => 'USD',
        'exchange_rate' => 80.0,
        'subtotal' => 10000,
        'tax' => 0,
        'total' => 10000,
        'amount_paid' => 10000,
    ], null, $org);

    // INR invoice: ₹500.00 with rate 1 (same currency)
    createInvoiceWithItems([
        'type' => 'invoice',
        'status' => 'paid',
        'issued_at' => now(),
        'currency' => 'INR',
        'exchange_rate' => 1.0,
        'subtotal' => 50000,
        'tax' => 0,
        'total' => 50000,
        'amount_paid' => 50000,
    ], null, $org);

    $response = $this->get('/dashboard');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.invoice_count', 2)
            ->where('stats.total_revenue', 850000)       // 800,000 + 50,000 paise
            ->where('stats.total_collected', 850000)
            ->where('stats.currency', 'INR')
        );
});

it('exposes per-currency breakdown sorted by total', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $org = createOrganizationWithLocation(['currency' => 'INR'], [], $user);
    $user->switchTeam($org);
    $this->actingAs($user);

    createInvoiceWithItems([
        'type' => 'invoice',
        'status' => 'sent',
        'issued_at' => now(),
        'currency' => 'USD',
        'exchange_rate' => 80.0,
        'total' => 10000,
        'amount_paid' => 0,
    ], null, $org);

    createInvoiceWithItems([
        'type' => 'invoice',
        'status' => 'paid',
        'issued_at' => now(),
        'currency' => 'INR',
        'exchange_rate' => 1.0,
        'total' => 50000,
        'amount_paid' => 50000,
    ], null, $org);

    $response = $this->get('/dashboard');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('currencyBreakdown', 2)
            ->where('currencyBreakdown.0.currency', 'INR')   // 50000 > 10000 in raw totals
            ->where('currencyBreakdown.0.total', 50000)
            ->where('currencyBreakdown.0.collected', 50000)
            ->where('currencyBreakdown.1.currency', 'USD')
            ->where('currencyBreakdown.1.total', 10000)
            ->where('currencyBreakdown.1.collected', 0)
        );
});
