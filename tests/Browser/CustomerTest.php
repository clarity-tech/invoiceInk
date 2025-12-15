<?php

use App\Models\User;

it('loads the customers page for authenticated users', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'cust-list@example.test',
        'password' => 'password',
    ]);

    $organization = $user->currentTeam;
    $organization->update([
        'company_name' => 'Customer List Test Corp',
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ]);

    $this->actingAs($user);

    $page = $this->visit('/customers');

    $page->assertPathIs('/customers')
        ->assertNoJavascriptErrors();
});

it('shows customer list when customers exist', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'cust-show@example.test',
        'password' => 'password',
    ]);

    $organization = $user->currentTeam;
    $organization->update([
        'company_name' => 'Customer Show Corp',
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ]);

    $customer = createCustomerWithLocation(
        ['name' => 'Acme Industries'],
        [],
        $organization
    );

    $this->actingAs($user);

    $page = $this->visit('/customers');

    $page->assertSee('Acme Industries');
});
