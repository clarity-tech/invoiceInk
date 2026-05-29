<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('exchange-rates:fetch {--date= : Date to fetch in YYYY-MM-DD (defaults to today)}')]
#[Description('Fetch and store daily USD-base FX rates from Frankfurter')]
class FetchExchangeRates extends Command
{
    public function handle(ExchangeRateService $service): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : now();

        $count = $service->fetchAndStore($date);

        if ($count === 0) {
            $this->error("Failed to fetch rates for {$date->toDateString()}.");

            return self::FAILURE;
        }

        $this->info("Stored {$count} exchange rate(s) for {$date->toDateString()}.");

        return self::SUCCESS;
    }
}
