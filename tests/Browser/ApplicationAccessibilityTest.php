<?php

use Laravel\Dusk\Browser;

test('application homepage loads and displays main content', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->pause(3000)  // Wait for page to load
            ->assertSee('Invoices & Estimates')
            ->screenshot('application_home_page');
    });
});
