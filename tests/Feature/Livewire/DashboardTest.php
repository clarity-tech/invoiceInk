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
