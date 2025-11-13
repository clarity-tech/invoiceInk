<?php

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Dashboard;

// Note: RefreshDatabase is already applied to all Browser tests via Pest.php configuration

test('dusk can connect to selenium and access homepage', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');
    });
});

test('dusk can login user successfully', function () {
    // Create test user inline for authentication test
    $user = User::factory()->withPersonalTeam()->create([
        'name' => 'Dusk Connection Test User',
        'email' => 'dusktest'.uniqid().'@example.test', // Unique email with .test TLD
        'password' => 'password',
        'email_verified_at' => now(),
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        // Test authentication using inline-created user
        loginUserInBrowser($browser, $user);

        // Use Dashboard page object for clean verification
        $dashboardPage = new Dashboard;
        $browser->visit($dashboardPage);

        $currentUrl = $browser->driver->getCurrentURL();

        if (str_contains($currentUrl, 'login')) {
            $browser->screenshot('inline_login_failed');
            expect(false)->toBeTrue("Inline user login failed: $currentUrl");
        } else {
            $browser->screenshot('inline_login_success');
            expect(true)->toBeTrue("SUCCESS! Inline user authentication works: $currentUrl");
        }
    });
});
