<?php

use Laravel\Dusk\Browser;

test('basic application loads correctly', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->assertSee('Invoicing')
            ->screenshot('application_home_page');
    });
});
