<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('root route redirects to login when unauthenticated', function () {
    $response = $this->get('/');

    $response->assertStatus(302);
    $response->assertRedirect('/login');
});

test('root route redirects to dashboard when authenticated', function () {
    $user = createUserWithTeam();

    $response = $this->actingAs($user)->get('/');

    $response->assertStatus(302);
    $response->assertRedirect('/dashboard');
});

test('protected routes require authentication', function () {
    $routes = ['/companies', '/customers', '/invoices', '/dashboard'];

    foreach ($routes as $route) {
        $response = $this->get($route);
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
});

test('protected routes load successfully when authenticated', function () {
    $user = createUserWithTeam();

    $routes = [
        '/companies' => 'Companies',
        '/customers' => 'Customers',
        '/invoices' => 'Invoices',
    ];

    foreach ($routes as $route => $expectedContent) {
        $response = $this->actingAs($user)->get($route);

        $response->assertStatus(200);
        $response->assertSee($expectedContent);
    }
});

test('dashboard loads successfully when authenticated', function () {
    $user = createUserWithTeam();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Dashboard');
});

test('non-existent routes return 404', function () {
    $response = $this->get('/non-existent-route');

    $response->assertStatus(404);
});
