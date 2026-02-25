<?php

use App\Models\User;

beforeEach(function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'smoke-'.uniqid().'@example.test',
        'password' => 'password',
    ]);

    $organization = $user->currentTeam;
    $organization->update([
        'company_name' => 'Smoke Test Corp',
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ]);

    $this->actingAs($user);
    $this->testUser = $user;
    $this->testOrg = $organization;
});

it('renders dashboard without JS errors', function () {
    $this->visit('/dashboard')
        ->assertPathIs('/dashboard')
        ->assertSee('Smoke Test Corp')
        ->assertNoJavascriptErrors();
});

it('renders invoices index without JS errors', function () {
    $this->visit('/invoices')
        ->assertPathIs('/invoices')
        ->assertSee('Invoices')
        ->assertNoJavascriptErrors();
});

it('renders invoice create form without JS errors', function () {
    $this->visit('/invoices/create')
        ->assertSee('Create Invoice')
        ->assertNoJavascriptErrors();
});

it('renders estimate create form without JS errors', function () {
    $this->visit('/estimates/create')
        ->assertSee('Create Estimate')
        ->assertNoJavascriptErrors();
});

it('renders customers index without JS errors', function () {
    $this->visit('/customers')
        ->assertPathIs('/customers')
        ->assertSee('Customers')
        ->assertNoJavascriptErrors();
});

it('renders organizations index without JS errors', function () {
    $this->visit('/organizations')
        ->assertPathIs('/organizations')
        ->assertSee('Organizations')
        ->assertNoJavascriptErrors();
});

it('renders numbering series index without JS errors', function () {
    $this->visit('/numbering-series')
        ->assertPathIs('/numbering-series')
        ->assertNoJavascriptErrors();
});

it('renders profile page without JS errors', function () {
    $this->visit('/user/profile')
        ->assertSee('Profile')
        ->assertNoJavascriptErrors();
});

it('navigation bar is visible on all authenticated pages', function () {
    $pages = ['/dashboard', '/invoices', '/customers', '/organizations', '/numbering-series'];

    foreach ($pages as $path) {
        $page = $this->visit($path);
        $page->assertSee('Invoicing')  // brand logo text
            ->assertSee('Dashboard')    // nav link
            ->assertSee('Customers')    // nav link
            ->assertNoJavascriptErrors();
    }
});
