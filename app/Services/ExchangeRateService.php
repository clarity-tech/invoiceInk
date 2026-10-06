<?php

namespace App\Services;

use App\Currency;
use App\Models\ExchangeRate;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private const BASE = 'USD';

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
            if ($this->fetchAndStore($date) === 0) {
                Log::warning('Falling back to rate=1 (no rates available)', [
                    'from' => $from, 'to' => $to, 'date' => $date->toDateString(),
                ]);

                return 1.0;
            }
            $rates = $this->loadUsdRatesFor($date);
        }

        if ($rates === null || ! isset($rates[$from], $rates[$to])) {
            Log::warning('Falling back to rate=1 (missing pair)', [
                'from' => $from, 'to' => $to, 'date' => $date->toDateString(),
            ]);

            return 1.0;
        }

        return $rates[$to] / $rates[$from];
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
