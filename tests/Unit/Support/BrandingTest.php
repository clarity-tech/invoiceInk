<?php

use App\Support\Branding;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('app.url', 'https://invoiceink.test');
    Config::set('branding.utm_medium', 'referral');
    Config::set('branding.utm_campaign', 'brand_attribution');
});

it('builds a powered-by url with utm attribution for the given channel', function () {
    $url = Branding::poweredByUrl('pdf');

    expect($url)->toBe('https://invoiceink.test?utm_source=pdf&utm_medium=referral&utm_campaign=brand_attribution&utm_content=powered_by');
});

it('builds an attributed url with an explicit placement as utm_content', function () {
    $url = Branding::attributedUrl('email', 'brand_header');

    expect($url)->toBe('https://invoiceink.test?utm_source=email&utm_medium=referral&utm_campaign=brand_attribution&utm_content=brand_header');
});

it('omits the content when none is supplied to attributedUrl', function () {
    $url = Branding::attributedUrl('email', null);

    expect($url)->toBe('https://invoiceink.test?utm_source=email&utm_medium=referral&utm_campaign=brand_attribution');
});

it('uses the channel as the utm_source', function () {
    expect(Branding::poweredByUrl('email'))->toContain('utm_source=email');
});

it('omits utm params that are configured empty', function () {
    Config::set('branding.utm_medium', '');
    Config::set('branding.utm_campaign', '');

    expect(Branding::poweredByUrl('web'))->toBe('https://invoiceink.test?utm_source=web&utm_content=powered_by');
});

it('returns the bare url when no utm params resolve', function () {
    Config::set('branding.utm_medium', '');
    Config::set('branding.utm_campaign', '');

    expect(Branding::attributedUrl('', null))->toBe('https://invoiceink.test');
});
