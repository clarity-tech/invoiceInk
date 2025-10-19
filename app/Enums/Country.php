<?php

namespace App\Enums;

use App\Currency;

enum Country: string
{
    case IN = 'IN';
    case GB = 'GB';
    case US = 'US';
    case AE = 'AE';
    case AU = 'AU';
    case CA = 'CA';
    case SG = 'SG';
    case JP = 'JP';
    case DE = 'DE';

    public function name(): string
    {
        return match ($this) {
            self::IN => 'India',
            self::GB => 'United Kingdom',
            self::US => 'United States',
            self::AE => 'United Arab Emirates',
            self::AU => 'Australia',
            self::CA => 'Canada',
            self::SG => 'Singapore',
            self::JP => 'Japan',
            self::DE => 'Germany',
        };
    }

    public function flag(): string
    {
        return match ($this) {
            self::IN => '🇮🇳',
            self::GB => '🇬🇧',
            self::US => '🇺🇸',
            self::AE => '🇦🇪',
            self::AU => '🇦🇺',
            self::CA => '🇨🇦',
            self::SG => '🇸🇬',
            self::JP => '🇯🇵',
            self::DE => '🇩🇪',
        };
    }

    public function getFinancialYearOptions(): array
    {
        return match ($this) {
            self::IN => [
                'april_march' => 'April - March (Standard)',
                'january_december' => 'January - December (Alternative)',
            ],
            self::GB => [
                'april_march' => 'April - March (Standard)',
                'january_december' => 'January - December (Alternative)',
            ],
            self::US => [
                'january_december' => 'January - December (Calendar Year)',
                'october_september' => 'October - September (Federal FY)',
                'april_march' => 'April - March (Custom)',
            ],
            self::AE => [
                'january_december' => 'January - December (Standard)',
                'april_march' => 'April - March (Alternative)',
            ],
            self::AU => [
                'july_june' => 'July - June (Standard)',
                'january_december' => 'January - December (Alternative)',
            ],
            self::CA => [
                'january_december' => 'January - December (Calendar Year)',
                'april_march' => 'April - March (Federal FY)',
            ],
            self::SG => [
                'january_december' => 'January - December (Standard)',
                'april_march' => 'April - March (Alternative)',
            ],
            self::JP => [
                'april_march' => 'April - March (Standard)',
                'january_december' => 'January - December (Alternative)',
            ],
            self::DE => [
                'january_december' => 'January - December (Standard)',
                'april_march' => 'April - March (Alternative)',
            ],
        };
    }

    public function getDefaultFinancialYearType(): FinancialYearType
    {
        return match ($this) {
            self::IN => FinancialYearType::APRIL_MARCH,
            self::GB => FinancialYearType::APRIL_MARCH,
            self::US => FinancialYearType::JANUARY_DECEMBER,
            self::AE => FinancialYearType::JANUARY_DECEMBER,
            self::AU => FinancialYearType::JULY_JUNE,
            self::CA => FinancialYearType::JANUARY_DECEMBER,
            self::SG => FinancialYearType::JANUARY_DECEMBER,
            self::JP => FinancialYearType::APRIL_MARCH,
            self::DE => FinancialYearType::JANUARY_DECEMBER,
        };
    }

    public function getDefaultCurrency(): Currency
    {
        return match ($this) {
            self::IN => Currency::INR,
            self::GB => Currency::GBP,
            self::US => Currency::USD,
            self::AE => Currency::AED,
            self::AU => Currency::AUD,
            self::CA => Currency::CAD,
            self::SG => Currency::SGD,
            self::JP => Currency::JPY,
            self::DE => Currency::EUR,
        };
    }

    public function getSupportedCurrencies(): array
    {
        return match ($this) {
            self::IN => [Currency::INR],
            self::GB => [Currency::GBP, Currency::EUR],
            self::US => [Currency::USD],
            self::AE => [Currency::AED, Currency::USD],
            self::AU => [Currency::AUD, Currency::USD],
            self::CA => [Currency::CAD, Currency::USD],
            self::SG => [Currency::SGD, Currency::USD],
            self::JP => [Currency::JPY, Currency::USD],
            self::DE => [Currency::EUR, Currency::USD],
        };
    }

    public function getTaxSystemInfo(): array
    {
        return match ($this) {
            self::IN => [
                'name' => 'GST (Goods & Services Tax)',
                'rates' => ['5%', '12%', '18%', '28%'],
                'number_format' => 'GSTIN',
                'number_length' => 15,
            ],
            self::GB => [
                'name' => 'VAT (Value Added Tax)',
                'rates' => ['0%', '5%', '20%'],
                'number_format' => 'VAT Registration Number',
                'number_length' => 9,
            ],
            self::US => [
                'name' => 'Sales Tax',
                'rates' => ['Varies by state'],
                'number_format' => 'EIN',
                'number_length' => 9,
            ],
            self::AE => [
                'name' => 'VAT (Value Added Tax)',
                'rates' => ['0%', '5%'],
                'number_format' => 'TRN',
                'number_length' => 15,
            ],
            self::AU => [
                'name' => 'GST (Goods & Services Tax)',
                'rates' => ['0%', '10%'],
                'number_format' => 'ABN',
                'number_length' => 11,
            ],
            self::CA => [
                'name' => 'GST/HST',
                'rates' => ['5%', '13%', '15%'],
                'number_format' => 'GST/HST Number',
                'number_length' => 15,
            ],
            self::SG => [
                'name' => 'GST (Goods & Services Tax)',
                'rates' => ['0%', '8%'],
                'number_format' => 'GST Registration Number',
                'number_length' => 10,
            ],
            self::JP => [
                'name' => 'Consumption Tax',
                'rates' => ['8%', '10%'],
                'number_format' => 'Invoice Registration Number',
                'number_length' => 13,
            ],
            self::DE => [
                'name' => 'VAT (Mehrwertsteuer)',
                'rates' => ['7%', '19%'],
                'number_format' => 'VAT Registration Number',
                'number_length' => 11,
            ],
        };
    }

    public function getRecommendedNumberingFormat(): string
    {
        return match ($this) {
            self::IN => 'INV-{FY}-{SEQUENCE:4}',
            self::GB => 'INV-{FY:2}-{SEQUENCE:4}',
            self::US => 'INV-{YEAR}-{SEQUENCE:4}',
            self::AE => 'INV-{YEAR}-{SEQUENCE:4}',
            self::AU => 'INV-{FY:2}-{SEQUENCE:4}',
            self::CA => 'INV-{YEAR}-{SEQUENCE:4}',
            self::SG => 'INV-{YEAR}-{SEQUENCE:4}',
            self::JP => 'INV-{FY}-{SEQUENCE:4}',
            self::DE => 'INV-{YEAR}-{SEQUENCE:4}',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($country) => [
                $country->value => $country->flag().' '.$country->name(),
            ])
            ->toArray();
    }

    public static function fromName(string $name): ?self
    {
        return collect(self::cases())
            ->first(fn ($country) => strtolower($country->name()) === strtolower($name));
    }
}
