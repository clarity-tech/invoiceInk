<?php

use App\Models\User;
use Laravel\Dusk\Browser;

// Note: RefreshDatabase is already applied to all Browser tests via Pest.php configuration

test('user can login and access dashboard', function () {
    // Create test user within the test itself - no dependency on seeders
    $user = User::factory()->withPersonalTeam()->create([
        'name' => 'Test User',
        'email' => 'testuser'.uniqid().'@example.test', // Unique email with .test TLD
        'password' => 'password',
        'email_verified_at' => now(),
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        // Use existing authentication helper with our inline-created user
        loginUserInBrowser($browser, $user);

        // Verify we can access the dashboard (proves authentication worked)
        $browser->visit('/dashboard')
            ->assertPathIs('/dashboard'); // If we can access /dashboard, auth worked
    });
});
