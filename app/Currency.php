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

    public static function isValid(string $code): bool
    {
        $currencies = \Akaunting\Money\Currency::getCurrencies();

        return array_key_exists($code, $currencies);
    }
}
