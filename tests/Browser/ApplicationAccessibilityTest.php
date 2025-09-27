<?php

use Laravel\Dusk\Browser;

test('application homepage loads and displays main content', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->visit('/dashboard')
            ->pause(2000)  // Wait for page to load
            ->waitForText('Dashboard', 10)  // Wait up to 10 seconds for Dashboard text
            ->screenshot('application_home_page');
    });
});
