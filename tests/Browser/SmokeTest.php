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
