<?php

beforeEach(function () {
    config(['app.url' => 'https://invoiceink.test']);
});

it('points crawlers at the sitemap on the public domain when reached on another host', function () {
    $response = $this->get('http://invoicing-web-abc123-el.a.run.app/robots.txt');

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    expect($response->getContent())
        ->toBe("User-agent: *\nDisallow:\n\nSitemap: https://invoiceink.test/sitemap.xml\n");
});

it('lists the public pages on the public domain when reached on another host', function () {
    $response = $this->get('http://invoicing-web-abc123-el.a.run.app/sitemap.xml');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    $locations = collect(simplexml_load_string($response->getContent())->xpath('//*[local-name()="loc"]'))
        ->map(fn (SimpleXMLElement $loc): string => (string) $loc)
        ->all();
    expect($locations)->toBe([
        'https://invoiceink.test/',
        'https://invoiceink.test/terms-of-service',
        'https://invoiceink.test/privacy-policy',
    ]);
});
