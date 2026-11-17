<?php

beforeEach(function () {
    config(['app.url' => 'https://invoiceink.test']);
});

it('renders each marketing page with its own canonical URL and no missing copy', function (string $path, string $headline) {
    $response = $this->get('http://invoicing-web-abc123-el.a.run.app'.$path);

    $response->assertOk()
        ->assertSee('<html lang="en-IN">', false)
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

it('places each page one step below the home page in its breadcrumb trail', function (string $path, string $label) {
    $html = $this->get('http://invoicing-web-abc123-el.a.run.app'.$path)->assertOk()->getContent();

    expect(structuredData($html)['BreadcrumbList']['itemListElement'] ?? null)->toBe([
        ['@type' => 'ListItem', 'position' => 1, 'name' => config('app.name'), 'item' => 'https://invoiceink.test/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $label, 'item' => 'https://invoiceink.test'.$path],
    ]);
})->with([
    'GST invoicing' => ['/gst-invoicing', 'GST invoicing'],
    'GST invoice format' => ['/gst-invoice-format', 'GST invoice format'],
    'HSN and SAC codes' => ['/hsn-sac-codes', 'HSN & SAC codes'],
    'Claude' => ['/claude', 'Works with Claude'],
    'open source' => ['/open-source', 'Open source'],
]);

it('marks up as FAQs exactly the questions the page shows', function (string $path) {
    $response = $this->get($path)->assertOk();
    $questions = structuredData($response->getContent())['FAQPage']['mainEntity'] ?? [];

    expect($questions)->not->toBeEmpty()
        ->and($questions)->toHaveCount(substr_count($response->getContent(), '<details'));
    foreach ($questions as $question) {
        expect($question['@type'])->toBe('Question');
        $response->assertSeeText($question['name'])
            ->assertSeeText($question['acceptedAnswer']['text']);
    }
})->with(['/gst-invoicing', '/gst-invoice-format', '/hsn-sac-codes', '/claude', '/open-source']);

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
