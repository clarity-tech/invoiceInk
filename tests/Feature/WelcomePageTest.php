<?php

beforeEach(function () {
    config(['app.url' => 'https://invoiceink.test']);
});

it('names the public domain as canonical when reached on another host', function () {
    $response = $this->get('http://invoicing-web-abc123-el.a.run.app/');

    $response->assertOk()
        ->assertSee('<link rel="canonical" href="https://invoiceink.test/">', false)
        ->assertSee('<meta property="og:image" content="https://invoiceink.test/images/og-image.png">', false);
});

it('tells search engines not to index it only when the deployment is not indexable', function (bool $indexable, bool $expectsNoindex) {
    config(['app.indexable' => $indexable]);

    $response = $this->get('/');

    $response->assertOk();
    expect(str_contains($response->getContent(), '<meta name="robots" content="noindex, nofollow">'))->toBe($expectsNoindex);
})->with([
    'staging' => [false, true],
    'production' => [true, false],
]);

it('describes the app as free software in its structured data', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect(structuredData($html)['SoftwareApplication'])->toMatchArray([
        'url' => 'https://invoiceink.test/',
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'INR'],
    ])->not->toHaveKey('aggregateRating')->not->toHaveKey('review');
});

it('gives search results the site name rather than the domain', function () {
    $html = $this->get('http://invoicing-web-abc123-el.a.run.app/')->assertOk()->getContent();

    expect(array_keys(structuredData($html)))->toBe(['SoftwareApplication', 'WebSite'])
        ->and(structuredData($html)['WebSite'])->toBe([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('app.name'),
            'url' => 'https://invoiceink.test/',
        ]);
});

it('renders every line of copy from the translation file', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSeeTextInOrder(['Create invoices', 'at the speed of thought.'])
        ->assertDontSee('marketing.');
});
