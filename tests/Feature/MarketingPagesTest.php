<?php

beforeEach(function () {
    config(['app.url' => 'https://invoiceink.test']);
});

it('renders each marketing page with its own canonical URL and no missing copy', function (string $path, string $headline) {
    $response = $this->get('http://invoicing-web-abc123-el.a.run.app'.$path);

    $response->assertOk()
        ->assertSee('<link rel="canonical" href="https://invoiceink.test'.$path.'">', false)
        ->assertSeeText($headline)
        ->assertDontSee('marketing.')
        ->assertDontSee('marketing/');
})->with([
    'GST invoicing' => ['/gst-invoicing', 'GST invoices,'],
    'GST invoice format' => ['/gst-invoice-format', 'The Rule 46 checklist'],
    'HSN and SAC codes' => ['/hsn-sac-codes', 'How many digits?'],
    'Claude' => ['/claude', 'What Claude can look up'],
    'open source' => ['/open-source', 'Run it yourself'],
]);

it('shows the connector URL on the public domain when the MCP server is enabled', function () {
    config(['mcp.web_enabled' => true]);

    $response = $this->get('/claude');

    $response->assertOk()
        ->assertSee('claude mcp add --transport http invoicing https://invoiceink.test/mcp', false)
        ->assertDontSeeText('The connector is switched off on this server.');
});

it('tells self-hosters how to switch the connector on when the MCP server is disabled', function () {
    config(['mcp.web_enabled' => false]);

    $response = $this->get('/claude');

    $response->assertOk()
        ->assertSeeText('The connector is switched off on this server.')
        ->assertDontSee('claude mcp add');
});
