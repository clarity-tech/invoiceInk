<?php

use App\Models\User;

it('redirects guests to login', function () {
    $page = $this->visit('/dashboard');

    $page->assertPathIs('/login');
});

it('allows login with valid credentials', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'auth-test@example.test',
        'password' => 'password',
    ]);

    $page = $this->visit('/login');

    $page->fill('#email', $user->email)
        ->fill('#password', 'password')
        ->click('Log in')
        ->assertPathBeginsWith('/dashboard');
});

it('shows error for invalid credentials', function () {
    $page = $this->visit('/login');

    $page->fill('#email', 'nonexistent@example.test')
        ->fill('#password', 'wrongpassword')
        ->click('Log in')
        ->assertSee('These credentials do not match our records');
});
