<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class PublicInvoice extends Page
{
    private string $invoiceUlid;

    public function __construct(string $invoiceUlid)
    {
        $this->invoiceUlid = $invoiceUlid;
    }

    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return "/invoices/{$this->invoiceUlid}";
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
        // Note: Don't assert 'Invoice' text as it might not be visible on the page
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@invoice-number' => '[data-test="invoice-number"]',
            '@invoice-details' => '[data-test="invoice-details"]',
            '@organization-info' => '[data-test="organization-info"]',
            '@customer-info' => '[data-test="customer-info"]',
            '@invoice-items' => '[data-test="invoice-items"]',
            '@totals' => '[data-test="totals"]',
        ];
    }

    /**
     * Assert that the invoice contains specific details.
     */
    public function assertInvoiceDetails(Browser $browser, string $invoiceNumber, string $itemDescription): void
    {
        $browser->assertSee($invoiceNumber)
            ->assertSee($itemDescription);
    }
}
