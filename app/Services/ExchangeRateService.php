<?php

namespace App\Services;

use App\Currency;
use App\Models\ExchangeRate;
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

        $url = rtrim(config('services.frankfurter.url'), '/').'/rates/'.$date->toDateString();

        $response = Http::timeout((int) config('services.frankfurter.timeout', 10))
            ->get($url, ['base' => self::BASE, 'quotes' => $quotes]);

        if ($response->failed()) {
            Log::warning('Failed to fetch exchange rates', [
                'date' => $date->toDateString(),
                'status' => $response->status(),
            ]);

            return 0;
        }

        $rows = collect($response->json())
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
