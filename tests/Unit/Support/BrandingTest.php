<?php

use App\Support\Branding;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('app.url', 'https://invoiceink.test');
    Config::set('branding.utm_source', 'invoiceink');
    Config::set('branding.utm_campaign', 'powered_by');
});

it('builds a powered-by url with utm attribution for the given medium', function () {
    $url = Branding::poweredByUrl('pdf');

    expect($url)->toBe('https://invoiceink.test?utm_source=invoiceink&utm_medium=pdf&utm_campaign=powered_by');
});

it('uses the supplied medium', function () {
    expect(Branding::poweredByUrl('email'))->toContain('utm_medium=email');
});

it('omits utm params that are configured empty', function () {
    Config::set('branding.utm_source', '');
    Config::set('branding.utm_campaign', '');

    expect(Branding::poweredByUrl('web'))->toBe('https://invoiceink.test?utm_medium=web');
});

it('returns the bare url when no utm params resolve', function () {
    Config::set('branding.utm_source', '');
    Config::set('branding.utm_campaign', '');

    expect(Branding::poweredByUrl(''))->toBe('https://invoiceink.test');
});
