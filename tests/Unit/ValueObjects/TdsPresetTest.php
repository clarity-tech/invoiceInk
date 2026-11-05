<?php

use App\ValueObjects\TdsPreset;

test('the 194C rate follows the payee type in the PAN', function (?string $pan, array $contractRates) {
    $presets = collect(TdsPreset::forPayeePan($pan));

    expect($presets->where('section', '194C')->pluck('rateBasisPoints')->all())->toBe($contractRates)
        ->and($presets->where('section', '194J')->pluck('rateBasisPoints')->all())->toBe([200, 1000]);
})->with([
    'individual' => ['ABCPE1234F', [100]],
    'HUF' => ['ABCHE1234F', [100]],
    'company' => ['ABCCE1234F', [200]],
    'firm or LLP' => ['ABCFE1234F', [200]],
    'unknown offers both' => [null, [100, 200]],
]);
