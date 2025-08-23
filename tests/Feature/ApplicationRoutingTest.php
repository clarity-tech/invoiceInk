<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('root route redirects to invoices page', function () {
    $response = $this->get('/');

    $response->assertStatus(302);
    $response->assertRedirect('/invoices');
});

test('invoices page loads successfully', function () {
    $response = $this->get('/invoices');

    $response->assertStatus(200);
    $response->assertSee('Invoices');
});

test('companies page loads successfully', function () {
    $response = $this->get('/companies');

    $response->assertStatus(200);
    $response->assertSee('Companies');
});

test('customers page loads successfully', function () {
    $response = $this->get('/customers');

    $response->assertStatus(200);
    $response->assertSee('Customers');
});

test('non-existent routes return 404', function () {
    $response = $this->get('/non-existent-route');

    $response->assertStatus(404);
});

test('main application routes are accessible', function () {
    $routes = [
        '/companies' => 'Companies',
        '/customers' => 'Customers',
        '/invoices' => 'Invoices',
    ];

    foreach ($routes as $route => $expectedContent) {
        $response = $this->get($route);

        $response->assertStatus(200);
        $response->assertSee($expectedContent);
    }
});
