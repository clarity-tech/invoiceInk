<?php

use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.frankfurter.url' => 'https://api.frankfurter.dev/v2']);
});

it('fetches rates from Frankfurter and stores them', function () {
    Http::fake([
        'api.frankfurter.dev/v2/rates/2026-05-28*' => Http::response([
            ['date' => '2026-05-28', 'base' => 'USD', 'quote' => 'INR', 'rate' => 95.67],
            ['date' => '2026-05-28', 'base' => 'USD', 'quote' => 'EUR', 'rate' => 0.85905],
            ['date' => '2026-05-28', 'base' => 'USD', 'quote' => 'AED', 'rate' => 3.6725],
            ['date' => '2026-05-28', 'base' => 'USD', 'quote' => 'GBP', 'rate' => 0.78],
            ['date' => '2026-05-28', 'base' => 'USD', 'quote' => 'AUD', 'rate' => 1.5],
            ['date' => '2026-05-28', 'base' => 'USD', 'quote' => 'CAD', 'rate' => 1.36],
            ['date' => '2026-05-28', 'base' => 'USD', 'quote' => 'SGD', 'rate' => 1.34],
            ['date' => '2026-05-28', 'base' => 'USD', 'quote' => 'JPY', 'rate' => 155.0],
        ]),
    ]);

    $service = new ExchangeRateService;
    $count = $service->fetchAndStore(Carbon::parse('2026-05-28'));

    expect($count)->toBe(9); // 8 quotes + USD self-rate
    expect(ExchangeRate::where('quote', 'INR')->value('rate'))->toBe(95.67);
    expect(ExchangeRate::where('quote', 'USD')->value('rate'))->toBe(1.0);
});

it('returns 1.0 when from == to', function () {
    $rate = (new ExchangeRateService)->getRate('USD', 'USD', Carbon::parse('2026-05-28'));

    expect($rate)->toBe(1.0);
});

it('cross-converts via USD when both rates are stored', function () {
    ExchangeRate::insert([
        ['date' => '2026-05-28', 'base' => 'USD', 'quote' => 'USD', 'rate' => 1.0, 'created_at' => now(), 'updated_at' => now()],
        ['date' => '2026-05-28', 'base' => 'USD', 'quote' => 'INR', 'rate' => 100.0, 'created_at' => now(), 'updated_at' => now()],
        ['date' => '2026-05-28', 'base' => 'USD', 'quote' => 'EUR', 'rate' => 0.85, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $service = new ExchangeRateService;

    // USD -> INR
    expect($service->getRate('USD', 'INR', Carbon::parse('2026-05-28')))->toBe(100.0);

    // INR -> USD
    expect($service->getRate('INR', 'USD', Carbon::parse('2026-05-28')))->toBe(0.01);

    // EUR -> INR via USD: (100 / 0.85)
    expect($service->getRate('EUR', 'INR', Carbon::parse('2026-05-28')))->toBe(100.0 / 0.85);
});

it('fetches rates on demand when missing for the requested date', function () {
    Http::fake([
        'api.frankfurter.dev/v2/rates/2026-05-28*' => Http::response([
            ['date' => '2026-05-28', 'base' => 'USD', 'quote' => 'INR', 'rate' => 95.0],
        ]),
    ]);

    $service = new ExchangeRateService;
    $rate = $service->getRate('USD', 'INR', Carbon::parse('2026-05-28'));

    expect($rate)->toBe(95.0);
    expect(ExchangeRate::where('quote', 'INR')->count())->toBe(1);
});

it('falls back to 1.0 and logs when API fails', function () {
    Http::fake(['*' => Http::response([], 500)]);

    $rate = (new ExchangeRateService)->getRate('USD', 'INR', Carbon::parse('2026-05-28'));

    expect($rate)->toBe(1.0);
});

it('falls back to 1.0 when a currency pair is missing from stored rates', function () {
    Http::fake(['*' => Http::response([], 500)]);

    ExchangeRate::insert([
        ['date' => '2026-05-28', 'base' => 'USD', 'quote' => 'USD', 'rate' => 1.0, 'created_at' => now(), 'updated_at' => now()],
        ['date' => '2026-05-28', 'base' => 'USD', 'quote' => 'INR', 'rate' => 95.0, 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Pretend an exotic currency was requested
    $rate = (new ExchangeRateService)->getRate('XYZ', 'INR', Carbon::parse('2026-05-28'));

    expect($rate)->toBe(1.0);
});

it('upserts rates on repeated fetch (no duplicate rows)', function () {
    Http::fake([
        'api.frankfurter.dev/v2/rates/2026-05-28*' => Http::response([
            ['date' => '2026-05-28', 'base' => 'USD', 'quote' => 'INR', 'rate' => 95.0],
        ]),
    ]);

    $service = new ExchangeRateService;
    $service->fetchAndStore(Carbon::parse('2026-05-28'));
    $service->fetchAndStore(Carbon::parse('2026-05-28'));

    expect(ExchangeRate::where('quote', 'INR')->count())->toBe(1);
});
