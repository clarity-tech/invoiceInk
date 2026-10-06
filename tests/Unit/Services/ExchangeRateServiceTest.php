<?php

use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.frankfurter.url' => 'https://api.frankfurter.dev/v2']);
});

it('fetches rates from Frankfurter and stores them', function () {
    Http::fake([
        'api.frankfurter.dev/v2/rates*' => Http::response([
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

it('sends the date as a query parameter, not a path segment', function () {
    // Regression: the service built `/rates/{date}`. Frankfurter v2 served that
    // until ~2026-08-19 and now 404s it, so every fetch returned 0 and every
    // conversion silently fell back to 1.0 -- for two months, unnoticed.
    //
    // The old test faked `.../rates/2026-05-28*`, i.e. it asserted the service's
    // own URL rather than the API's contract, so it passed either way. This
    // asserts the shape the API actually documents.
    Http::fake(['api.frankfurter.dev/v2/rates*' => Http::response([
        ['date' => '2026-05-28', 'base' => 'USD', 'quote' => 'INR', 'rate' => 95.67],
    ])]);

    (new ExchangeRateService)->fetchAndStore(Carbon::parse('2026-05-28'));

    Http::assertSent(function (Request $request) {
        return parse_url($request->url(), PHP_URL_PATH) === '/v2/rates'
            && $request['date'] === '2026-05-28'
            && $request['base'] === 'USD';
    });
});

it('stores nothing when the upstream has no data for the date', function () {
    // A future date answers 200 with an empty body rather than an error, so
    // `$response->failed()` is false. Storing that would persist the USD
    // self-rate alone, which reads to getRate() like a successful fetch.
    Http::fake(['api.frankfurter.dev/v2/rates*' => Http::response([])]);

    $count = (new ExchangeRateService)->fetchAndStore(Carbon::parse('2099-01-01'));

    expect($count)->toBe(0);
    expect(ExchangeRate::whereDate('date', '2099-01-01')->count())->toBe(0);
});

it('returns 0 instead of throwing when the API is unreachable', function () {
    // The upstream times out intermittently. A ConnectionException is thrown, not
    // returned, so it would otherwise escape through getRate() into invoice
    // creation as a 500.
    Http::fake(fn () => throw new ConnectionException('cURL error 28: timed out'));

    $count = (new ExchangeRateService)->fetchAndStore(Carbon::parse('2026-05-28'));

    expect($count)->toBe(0);
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
        'api.frankfurter.dev/v2/rates*' => Http::response([
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
        'api.frankfurter.dev/v2/rates*' => Http::response([
            ['date' => '2026-05-28', 'base' => 'USD', 'quote' => 'INR', 'rate' => 95.0],
        ]),
    ]);

    $service = new ExchangeRateService;
    $service->fetchAndStore(Carbon::parse('2026-05-28'));
    $service->fetchAndStore(Carbon::parse('2026-05-28'));

    expect(ExchangeRate::where('quote', 'INR')->count())->toBe(1);
});
