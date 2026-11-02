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

it('describes the app as free software in its structured data', function () {
    $html = $this->get('/')->assertOk()->getContent();

    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $match);
    expect(json_decode($match[1], true))->toMatchArray([
        '@type' => 'SoftwareApplication',
        'url' => 'https://invoiceink.test/',
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'INR'],
    ]);
});

it('renders every line of copy from the translation file', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSeeTextInOrder(['GST invoices', 'at the speed of thought.'])
        ->assertDontSee('marketing.');
});
