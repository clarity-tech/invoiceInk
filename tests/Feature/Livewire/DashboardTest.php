<?php

use App\Livewire\Dashboard;
use App\Models\User;
use Livewire\Livewire;

it('renders dashboard for authenticated user', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertOk()
        ->assertSee('Business overview and analytics');
});

it('shows zero stats when no invoices exist', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    $component = Livewire::test(Dashboard::class);
    $stats = $component->get('stats');

    expect($stats['total_revenue'])->toBe(0)
        ->and($stats['total_collected'])->toBe(0)
        ->and($stats['total_outstanding'])->toBe(0)
        ->and($stats['invoice_count'])->toBe(0)
        ->and($stats['overdue_count'])->toBe(0);
});

it('calculates stats from invoices', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $org = createOrganizationWithLocation([], [], $user);
    $user->switchTeam($org);

    $this->actingAs($user);

    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'status' => 'sent',
        'issued_at' => now(),
        'amount_paid' => 50000,
    ], null, $org);

    $component = Livewire::test(Dashboard::class);
    $stats = $component->get('stats');

    expect($stats['invoice_count'])->toBe(1)
        ->and($stats['total_revenue'])->toBeGreaterThan(0)
        ->and($stats['total_collected'])->toBe(50000);
});

it('can switch period filter', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->set('period', 'this_year')
        ->assertOk()
        ->set('period', 'all_time')
        ->assertOk();
});

it('shows customer count', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $org = $user->currentTeam;

    $this->actingAs($user);

    createCustomerWithLocation([], [], $org);
    createCustomerWithLocation([], [], $org);

    $component = Livewire::test(Dashboard::class);

    expect($component->get('customerCount'))->toBe(2);
});
