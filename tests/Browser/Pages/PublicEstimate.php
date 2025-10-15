<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class PublicEstimate extends Page
{
    private string $estimateUlid;

    public function __construct(string $estimateUlid)
    {
        $this->estimateUlid = $estimateUlid;
    }

    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return "/estimates/{$this->estimateUlid}";
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
            '@estimate-number' => '[data-test="estimate-number"]',
            '@estimate-details' => '[data-test="estimate-details"]',
            '@organization-info' => '[data-test="organization-info"]',
            '@customer-info' => '[data-test="customer-info"]',
            '@estimate-items' => '[data-test="estimate-items"]',
            '@totals' => '[data-test="totals"]',
        ];
    }

    /**
     * Assert that the estimate contains specific details.
     */
    public function assertEstimateDetails(Browser $browser, string $estimateNumber, string $itemDescription): void
    {
        $browser->assertSee($estimateNumber)
            ->assertSee($itemDescription);
    }
}
