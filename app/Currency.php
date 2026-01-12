<?php

namespace App;

enum Currency: string
{
    case INR = 'INR';
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case AUD = 'AUD';
    case CAD = 'CAD';
    case SGD = 'SGD';
    case JPY = 'JPY';
    case AED = 'AED';

    public function symbol(): string
    {
        return match ($this) {
            self::INR => '₹',
            self::USD => '$',
            self::EUR => '€',
            self::GBP => '£',
            self::AUD => 'A$',
            self::CAD => 'C$',
            self::SGD => 'S$',
            self::JPY => '¥',
            self::AED => 'د.إ',
        };
    }

    public function name(): string
    {
        return match ($this) {
            self::INR => 'Indian Rupee',
            self::USD => 'US Dollar',
            self::EUR => 'Euro',
            self::GBP => 'British Pound',
            self::AUD => 'Australian Dollar',
            self::CAD => 'Canadian Dollar',
            self::SGD => 'Singapore Dollar',
            self::JPY => 'Japanese Yen',
            self::AED => 'UAE Dirham',
        };
    }

    public static function default(): self
    {
        return self::INR;
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($currency) => [$currency->value => $currency->name().' ('.$currency->symbol().')'])
            ->toArray();
    }

    /**
     * Format a monetary amount in cents using the correct grouping for this currency.
     *
     * INR uses Indian grouping (lakh/crore): 1,00,00,000.00
     * All other currencies use standard 3-digit grouping via Money::format().
     */
    public function formatAmount(int $amountInCents): string
    {
        if ($this !== self::INR) {
            return \Akaunting\Money\Money::{$this->value}($amountInCents)->format();
        }

        $isNegative = $amountInCents < 0;
        $absoluteCents = abs($amountInCents);

        $integerPart = intdiv($absoluteCents, 100);
        $decimalPart = $absoluteCents % 100;

        $formatted = sprintf('%02d', $decimalPart);

        if ($integerPart <= 999) {
            $formatted = $integerPart.'.'.$formatted;
        } else {
            // Last 3 digits as first group, then groups of 2
            $lastThree = $integerPart % 1000;
            $remaining = intdiv($integerPart, 1000);
            $groups = [sprintf('%03d', $lastThree)];

            while ($remaining > 0) {
                $groups[] = sprintf('%02d', $remaining % 100);
                $remaining = intdiv($remaining, 100);
            }

            // Reverse and trim leading zeros on the leftmost group
            $groups = array_reverse($groups);
            $groups[0] = ltrim($groups[0], '0') ?: '0';

            $formatted = implode(',', $groups).'.'.$formatted;
        }

        return ($isNegative ? '-' : '').'₹'.$formatted;
    }

    public function subunitName(): string
    {
        return match ($this) {
            self::INR => 'Paise',
            self::USD, self::AUD, self::CAD, self::SGD => 'Cents',
            self::EUR => 'Cents',
            self::GBP => 'Pence',
            self::JPY => '',
            self::AED => 'Fils',
        };
    }

    public function amountToWords(int $amountInCents): string
    {
        $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        $majorUnit = intdiv(abs($amountInCents), 100);
        $minorUnit = abs($amountInCents) % 100;
        $majorWords = ucwords($formatter->format($majorUnit), ' -');
        $currencyName = $this->name();

        if ($this === self::JPY) {
            // JPY has no subunit
            return "{$currencyName} {$majorWords} Only";
        }

        if ($minorUnit > 0) {
            $minorWords = ucwords($formatter->format($minorUnit), ' -');

            return "{$currencyName} {$majorWords} and {$minorWords} {$this->subunitName()} Only";
        }

        return "{$currencyName} {$majorWords} Only";
    }

    public static function isValid(string $code): bool
    {
        $currencies = \Akaunting\Money\Currency::getCurrencies();

        return array_key_exists($code, $currencies);
    }
}
