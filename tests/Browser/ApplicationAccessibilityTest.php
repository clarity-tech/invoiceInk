<?php

use Laravel\Dusk\Browser;

test('application homepage loads and displays main content', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->visit('/dashboard')
            ->pause(3000)  // Wait for page to load
            ->assertSee('Dashboard')
            ->screenshot('application_home_page');
    });
});
