<?php

use App\Services\SvgSanitizer;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->sanitizer = new SvgSanitizer;
});

function fakeSvg(string $contents): UploadedFile
{
    return UploadedFile::fake()->createWithContent('logo.svg', $contents);
}

test('strips script tags from uploaded svg', function () {
    $malicious = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="10" height="10"/></svg>';
    $file = fakeSvg($malicious);

    expect($this->sanitizer->sanitizeInPlace($file))->toBeTrue();

    $cleaned = file_get_contents($file->getRealPath());
    expect($cleaned)
        ->not->toContain('<script')
        ->not->toContain('alert(1)')
        ->toContain('<rect');
});

test('strips inline event handlers from uploaded svg', function () {
    $malicious = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><rect width="10" height="10" onclick="steal()"/></svg>';
    $file = fakeSvg($malicious);

    expect($this->sanitizer->sanitizeInPlace($file))->toBeTrue();

    $cleaned = file_get_contents($file->getRealPath());
    expect($cleaned)
        ->not->toContain('onload')
        ->not->toContain('onclick');
});

test('strips javascript href vectors from uploaded svg', function () {
    $malicious = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:href="javascript:alert(1)"><rect width="10" height="10"/></a></svg>';
    $file = fakeSvg($malicious);

    expect($this->sanitizer->sanitizeInPlace($file))->toBeTrue();

    $cleaned = file_get_contents($file->getRealPath());
    expect($cleaned)
        ->not->toContain('javascript:')
        ->toContain('<rect');
});

test('preserves a clean svg', function () {
    $clean = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#000"/></svg>';
    $file = fakeSvg($clean);

    expect($this->sanitizer->sanitizeInPlace($file))->toBeTrue();

    $cleaned = file_get_contents($file->getRealPath());
    expect($cleaned)->toContain('<rect');
});

test('leaves non-svg uploads untouched', function () {
    $file = UploadedFile::fake()->image('logo.png', 50, 50);
    $before = file_get_contents($file->getRealPath());

    expect($this->sanitizer->sanitizeInPlace($file))->toBeTrue();

    expect(file_get_contents($file->getRealPath()))->toBe($before);
});

test('rejects an svg that cannot be parsed', function () {
    $file = fakeSvg('this is not valid xml at all <<<');

    expect($this->sanitizer->sanitizeInPlace($file))->toBeFalse();
});
