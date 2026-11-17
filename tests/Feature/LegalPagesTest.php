<?php

it('serves each legal document under its own title, linking to the other', function (string $path, string $title, string $otherPath) {
    $this->get($path)
        ->assertOk()
        ->assertSee("<title>{$title} - ".config('app.name').'</title>', false)
        ->assertSee("<h1>{$title}</h1>", false)
        ->assertSee('href="'.url($otherPath).'"', false);
})->with([
    'terms' => ['/terms-of-service', 'Terms of Service', '/privacy-policy'],
    'privacy' => ['/privacy-policy', 'Privacy Policy', '/terms-of-service'],
]);

it('names the public domain as canonical and describes itself when reached on another host', function (string $path, string $description) {
    config(['app.url' => 'https://invoiceink.test']);

    $this->get('http://invoicing-web-abc123-el.a.run.app'.$path)
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://invoiceink.test'.$path.'">', false)
        ->assertSee('<meta name="description" content="'.e(__($description, ['app' => config('app.name')])).'">', false);
})->with([
    'terms' => ['/terms-of-service', 'marketing.meta.terms_description'],
    'privacy' => ['/privacy-policy', 'marketing.meta.privacy_description'],
]);

it('tells search engines not to index it only when the deployment is not indexable', function (string $path, bool $indexable) {
    config(['app.indexable' => $indexable]);

    $html = $this->get($path)->assertOk()->getContent();

    expect(str_contains($html, '<meta name="robots" content="noindex, nofollow">'))->toBe(! $indexable);
})->with(['/terms-of-service', '/privacy-policy'])->with([
    'staging' => false,
    'production' => true,
]);
