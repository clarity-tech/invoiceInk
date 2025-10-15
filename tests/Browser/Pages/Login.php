<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class Login extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/login';
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@email' => 'input[name="email"]',
            '@password' => 'input[name="password"]',
            '@login-button' => 'button[type="submit"]',
        ];
    }

    /**
     * Login with the given credentials.
     */
    public function loginWith(Browser $browser, string $email, string $password): void
    {
        $browser->type('@email', $email)
            ->type('@password', $password)
            ->press('@login-button')
            ->pause(2000); // Wait for redirect
    }
}
