<?php

it('loads the login page without javascript errors', function () {
    $page = $this->visit('/login');

    $page->assertSee('Log in')
        ->assertNoJavascriptErrors();
});

it('loads the register page without javascript errors', function () {
    $page = $this->visit('/register');

    $page->assertSee('Register')
        ->assertNoJavascriptErrors();
});

it('has no smoke on public pages', function () {
    $this->visit(['/login', '/register'])->assertNoSmoke();
});

// The pages search engines are sent to, in a real browser. This catches
// script errors and console output, not a missing file: the icons and share
// image are checked in SearchIndexingTest.
it('has no smoke on the pages in the sitemap', function () {
    $this->visit([
        '/',
        '/gst-invoicing',
        '/gst-invoice-format',
        '/hsn-sac-codes',
        '/claude',
        '/open-source',
        '/terms-of-service',
        '/privacy-policy',
    ])->assertNoSmoke();
});
