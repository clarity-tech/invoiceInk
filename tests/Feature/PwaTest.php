<?php

test('the web app manifest is installable', function () {
    $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest)
        ->name->toBe('InvoiceInk')
        ->display->toBe('standalone')
        ->start_url->toStartWith('/')
        ->and(collect($manifest['icons'])->pluck('sizes'))->toContain('192x192', '512x512')
        ->and(collect($manifest['icons'])->pluck('purpose'))->toContain('maskable');

    foreach ($manifest['icons'] as $icon) {
        expect(public_path($icon['src']))->toBeFile();
    }
});

test('the service worker is served from the site root without starting a session', function () {
    $built = public_path('build/sw.js');
    $created = ! is_file($built);

    if ($created) {
        @mkdir(dirname($built), 0755, true);
        file_put_contents($built, '// stub');
    }

    try {
        $this->get('/sw.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/javascript; charset=utf-8')
            ->assertCookieMissing('XSRF-TOKEN');
    } finally {
        if ($created) {
            unlink($built);
        }
    }
});

test('the app shell links the manifest and theme colour', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false)
        ->assertSee('<meta name="theme-color" content="#7c3aed">', false);
});
