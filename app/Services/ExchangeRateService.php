<?php

namespace App\Services;

use App\Currency;
use App\Exceptions\ExchangeRateUnavailable;
use App\Models\ExchangeRate;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private const BASE = 'USD';

    /**
     * How far back to reach for a usable rate. Two weeks covers a long holiday
     * or a sustained upstream outage while staying close enough that the rate
     * is still broadly right; beyond that, a stale rate stops being an
     * approximation and starts being a guess.
     */
    private const LOOKBACK_DAYS = 14;

    public function fetchAndStore(Carbon $date): int
    {
        $date = $date->copy()->startOfDay();
        $quotes = collect(Currency::cases())
            ->map(fn (Currency $c) => $c->value)
            ->reject(fn (string $code) => $code === self::BASE)
            ->values()
            ->implode(',');

        // The date is a QUERY parameter, not a path segment. Frankfurter v2 served
        // `/rates/{date}` until around 2026-08-19 -- the last date this app ever
        // stored a rate for -- and now answers it with a 404, so every fetch since
        // has returned 0 and every conversion has silently fallen back to 1.0.
        $url = rtrim(config('services.frankfurter.url'), '/').'/rates';

        // The upstream is intermittently slow -- observed timing out on one call
        // and answering the next in 0.1s. A ConnectionException is thrown rather
        // than returned, so without this it escapes `fetchAndStore()` and, through
        // `getRate()`, reaches invoice creation as a 500.
        try {
            $response = Http::timeout((int) config('services.frankfurter.timeout', 10))
                ->get($url, [
                    'date' => $date->toDateString(),
                    'base' => self::BASE,
                    'quotes' => $quotes,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Could not reach the exchange rate API', [
                'date' => $date->toDateString(),
                'error' => $e->getMessage(),
            ]);

            return 0;
        }

        if ($response->failed()) {
            Log::warning('Failed to fetch exchange rates', [
                'date' => $date->toDateString(),
                'status' => $response->status(),
            ]);

            return 0;
        }

        // A date the upstream has no data for -- a future date, say -- answers 200
        // with an empty body rather than an error. Storing that would persist the
        // USD self-rate alone and leave every real pair missing, which reads to
        // `getRate()` exactly like a successful fetch.
        $quoted = collect($response->json());

        if ($quoted->isEmpty()) {
            Log::warning('Exchange rate API returned no rates', [
                'date' => $date->toDateString(),
            ]);

            return 0;
        }

        $rows = $quoted
            ->map(fn (array $row) => [
                'date' => $date->toDateString(),
                'base' => self::BASE,
                'quote' => $row['quote'],
                'rate' => $row['rate'],
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->push([
                'date' => $date->toDateString(),
                'base' => self::BASE,
                'quote' => self::BASE,
                'rate' => 1.0,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        ExchangeRate::upsert($rows, ['date', 'base', 'quote'], ['rate', 'updated_at']);

        return count($rows);
    }

    public function getRate(string $from, string $to, Carbon $date): float
    {
        if ($from === $to) {
            return 1.0;
        }

        $date = $date->copy()->startOfDay();
        $rates = $this->loadUsdRatesFor($date);

        if ($rates === null) {
            $this->fetchAndStore($date);
            $rates = $this->loadUsdRatesFor($date);
        }

        if ($rates !== null && isset($rates[$from], $rates[$to])) {
            return $rates[$to] / $rates[$from];
        }

        // Nothing for the requested date. A rate from a few days ago is wrong
        // by a fraction of a percent; 1.0 is wrong by a factor of 26 for AED
        // and 96 for USD, and is indistinguishable afterwards from a genuine
        // same-currency invoice. So reach backwards rather than default.
        //
        // In practice this only fires on an upstream outage or a future-dated
        // invoice: the provider already carries weekends and holidays forward,
        // so ordinary non-trading days resolve exactly.
        if ($prior = $this->nearestPriorRate($from, $to, $date)) {
            Log::warning('Using an earlier exchange rate; none published for this date', [
                'from' => $from, 'to' => $to,
                'date' => $date->toDateString(),
                'as_of' => $prior['as_of'],
            ]);

            return $prior['rate'];
        }

        // Nothing usable within the window. Guessing here corrupts every
        // converted figure derived from the invoice, permanently and
        // invisibly, so fail instead.
        // Reporting lives on the exception's report() method, so it happens
        // once wherever this is thrown rather than being repeated here.
        throw new ExchangeRateUnavailable($from, $to, $date->toDateString(), self::LOOKBACK_DAYS);
    }

    /**
     * The most recent stored rate at or before $date that carries both sides of
     * the pair, or null if the window holds none.
     *
     * @return array{rate: float, as_of: string}|null
     */
    private function nearestPriorRate(string $from, string $to, Carbon $date): ?array
    {
        $earliest = $date->copy()->subDays(self::LOOKBACK_DAYS)->toDateString();

        $dates = ExchangeRate::query()
            ->whereDate('date', '<=', $date->toDateString())
            ->whereDate('date', '>=', $earliest)
            ->where('base', self::BASE)
            ->orderByDesc('date')
            ->pluck('date')
            ->unique();

        foreach ($dates as $candidate) {
            $rates = $this->loadUsdRatesFor(Carbon::parse($candidate));

            if ($rates !== null && isset($rates[$from], $rates[$to])) {
                return [
                    'rate' => $rates[$to] / $rates[$from],
                    'as_of' => Carbon::parse($candidate)->toDateString(),
                ];
            }
        }

        return null;
    }

    /**
     * @return array<string, float>|null
     */
    private function loadUsdRatesFor(Carbon $date): ?array
    {
        $rows = ExchangeRate::query()
            ->whereDate('date', $date->toDateString())
            ->where('base', self::BASE)
            ->get(['quote', 'rate']);

        if ($rows->isEmpty()) {
            return null;
        }

        return $rows->mapWithKeys(fn ($row) => [$row->quote => (float) $row->rate])->all();
    }
}
