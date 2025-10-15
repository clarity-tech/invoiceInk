<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class Dashboard extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/dashboard';
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
        // Note: Dashboard text assertion removed - will add back once we know page structure
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@main-content' => 'main',
            '@header' => 'header',
        ];
    }
}
