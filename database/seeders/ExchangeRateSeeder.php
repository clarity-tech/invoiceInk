<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use Illuminate\Support\Carbon;

class ExchangeRateSeeder extends ProductionSafeSeeder
{
    /**
     * How many days of history to seed. Demo invoices are issued up to six
     * months back, and rates are looked up by the date of issue.
     */
    private const DAYS_OF_HISTORY = 190;

    /**
     * Indicative rates expressed as units of the currency per 1 USD, matching
     * how ExchangeRateService stores and reads them.
     */
    private const USD_RATES = [
        'USD' => 1.0,
        'INR' => 83.5,
        'EUR' => 0.925,
        'GBP' => 0.789,
        'AUD' => 1.507,
        'CAD' => 1.364,
        'SGD' => 1.345,
        'JPY' => 149.2,
        'AED' => 3.6725,
    ];

    /**
     * The rate to convert an amount from one currency into another, on the
     * same basis the app uses (quote per base, both against USD).
     */
    public static function rateFor(string $from, string $to): float
    {
        if ($from === $to || ! isset(self::USD_RATES[$from], self::USD_RATES[$to])) {
            return 1.0;
        }

        return self::USD_RATES[$to] / self::USD_RATES[$from];
    }

    protected function seed(): void
    {
        $this->info('Seeding exchange rates...');

        $rows = [];
        $today = Carbon::today();

        for ($daysAgo = 0; $daysAgo < self::DAYS_OF_HISTORY; $daysAgo++) {
            $date = $today->copy()->subDays($daysAgo)->toDateString();

            // A small deterministic wobble so the history isn't a flat line.
            $drift = 1 + (($daysAgo % 11) - 5) / 1000;

            foreach (self::USD_RATES as $quote => $rate) {
                $rows[] = [
                    'date' => $date,
                    'base' => 'USD',
                    'quote' => $quote,
                    'rate' => $quote === 'USD' ? 1.0 : round($rate * $drift, 10),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            ExchangeRate::upsert($chunk, ['date', 'base', 'quote'], ['rate', 'updated_at']);
        }

        $this->info('✓ Created '.count($rows).' daily rates across '.count(self::USD_RATES).' currencies');
    }
}
