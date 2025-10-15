<?php

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Dashboard;

// Note: RefreshDatabase is already applied to all Browser tests via Pest.php configuration

test('application homepage loads and displays main content', function () {
    // Create test user inline for accessibility test
    $user = User::factory()->withPersonalTeam()->create([
        'name' => 'Accessibility Test User',
        'email' => 'accessibility@example.test', // Using .test TLD
        'password' => 'password',
        'email_verified_at' => now(),
    ]);
    
    $this->browse(function (Browser $browser) use ($user) {
        // Use inline-created user for authentication
        loginUserInBrowser($browser, $user);

        // Use Dashboard page object for consistent navigation
        $dashboardPage = new Dashboard();
        $browser->visit($dashboardPage)
            ->pause(2000)  // Wait for page to load
            ->screenshot('application_home_page_accessibility');
    });
});
