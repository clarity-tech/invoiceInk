<?php

namespace App\Enums;

use App\Currency;
use App\Models\InvoiceItem;
use App\Models\TaxTemplate;

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
                'rates' => ['0%', '5%', '18%', '40%'],
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

    /**
     * Default tax templates created for an organization of this country.
     *
     * Rates are stored in basis points where 1% = 100 (e.g. 18% = 1800), matching
     * the scale used by {@see TaxTemplate::$rate} and
     * {@see InvoiceItem::$tax_rate}.
     *
     * @return array<int, array{name: string, type: string, rate: int, category: string, description: string}>
     */
    public function getDefaultTaxTemplates(): array
    {
        return match ($this) {
            self::IN => [
                ['name' => 'GST 0%', 'type' => 'GST', 'rate' => 0, 'category' => 'Exempt', 'description' => 'GST exempt / nil-rated goods and services'],
                ['name' => 'GST 5%', 'type' => 'GST', 'rate' => 500, 'category' => 'Standard Rate', 'description' => 'Goods and Services Tax at 5%'],
                ['name' => 'GST 18%', 'type' => 'GST', 'rate' => 1800, 'category' => 'Standard Rate', 'description' => 'Goods and Services Tax at 18%'],
                ['name' => 'GST 40%', 'type' => 'GST', 'rate' => 4000, 'category' => 'Special Rate', 'description' => 'Goods and Services Tax at 40% (sin & luxury goods)'],
            ],
            self::GB => [
                ['name' => 'VAT 20%', 'type' => 'VAT', 'rate' => 2000, 'category' => 'Standard Rate', 'description' => 'Value Added Tax at the standard rate of 20%'],
                ['name' => 'VAT 5%', 'type' => 'VAT', 'rate' => 500, 'category' => 'Reduced Rate', 'description' => 'Value Added Tax at the reduced rate of 5%'],
                ['name' => 'VAT 0%', 'type' => 'VAT', 'rate' => 0, 'category' => 'Zero Rate', 'description' => 'Zero-rated VAT for certain goods and services'],
            ],
            self::US => [
                ['name' => 'No Tax', 'type' => 'Exempt', 'rate' => 0, 'category' => 'Tax Exempt', 'description' => 'No sales tax applied'],
                ['name' => 'Sales Tax 6%', 'type' => 'Sales Tax', 'rate' => 600, 'category' => 'Standard Rate', 'description' => 'State Sales Tax at 6%'],
                ['name' => 'Sales Tax 8.25%', 'type' => 'Sales Tax', 'rate' => 825, 'category' => 'Sample Rate', 'description' => 'State Sales Tax at 8.25%'],
            ],
            self::AE => [
                ['name' => 'VAT 5%', 'type' => 'VAT', 'rate' => 500, 'category' => 'Standard Rate', 'description' => 'Value Added Tax at the standard rate of 5%'],
                ['name' => 'VAT 0%', 'type' => 'VAT', 'rate' => 0, 'category' => 'Zero Rate', 'description' => 'Zero-rated VAT for exports and certain goods/services'],
                ['name' => 'VAT Exempt', 'type' => 'VAT', 'rate' => 0, 'category' => 'Exempt', 'description' => 'VAT exempt goods and services'],
            ],
            self::AU => [
                ['name' => 'GST 10%', 'type' => 'GST', 'rate' => 1000, 'category' => 'Standard Rate', 'description' => 'Goods and Services Tax at 10%'],
                ['name' => 'GST 0%', 'type' => 'GST', 'rate' => 0, 'category' => 'Zero Rate', 'description' => 'GST-free goods and services'],
            ],
            self::CA => [
                ['name' => 'GST 5%', 'type' => 'GST', 'rate' => 500, 'category' => 'Federal Rate', 'description' => 'Federal Goods and Services Tax at 5%'],
                ['name' => 'HST 13%', 'type' => 'HST', 'rate' => 1300, 'category' => 'Harmonized Rate', 'description' => 'Harmonized Sales Tax at 13%'],
                ['name' => 'HST 15%', 'type' => 'HST', 'rate' => 1500, 'category' => 'Harmonized Rate', 'description' => 'Harmonized Sales Tax at 15%'],
            ],
            self::SG => [
                ['name' => 'GST 8%', 'type' => 'GST', 'rate' => 800, 'category' => 'Standard Rate', 'description' => 'Goods and Services Tax at 8%'],
                ['name' => 'GST 0%', 'type' => 'GST', 'rate' => 0, 'category' => 'Zero Rate', 'description' => 'Zero-rated GST for exports and international services'],
            ],
            self::JP => [
                ['name' => 'Consumption Tax 10%', 'type' => 'Consumption Tax', 'rate' => 1000, 'category' => 'Standard Rate', 'description' => 'Consumption Tax at the standard rate of 10%'],
                ['name' => 'Consumption Tax 8%', 'type' => 'Consumption Tax', 'rate' => 800, 'category' => 'Reduced Rate', 'description' => 'Consumption Tax at the reduced rate of 8%'],
            ],
            self::DE => [
                ['name' => 'VAT 19%', 'type' => 'VAT', 'rate' => 1900, 'category' => 'Standard Rate', 'description' => 'Value Added Tax at the standard rate of 19%'],
                ['name' => 'VAT 7%', 'type' => 'VAT', 'rate' => 700, 'category' => 'Reduced Rate', 'description' => 'Value Added Tax at the reduced rate of 7%'],
                ['name' => 'VAT 0%', 'type' => 'VAT', 'rate' => 0, 'category' => 'Zero Rate', 'description' => 'Zero-rated VAT for exports and certain services'],
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

    /**
     * Map of country code => tax-id label (e.g. "GSTIN", "VAT Registration Number", "TRN").
     *
     * Used in customer/organization location forms and on invoice PDFs to render the
     * country-appropriate name for the tax identification number.
     *
     * @return array<string, string>
     */
    public static function taxLabels(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($country) => [
                $country->value => $country->getTaxSystemInfo()['number_format'],
            ])
            ->toArray();
    }

    public static function fromName(string $name): ?self
    {
        return collect(self::cases())
            ->first(fn ($country) => strtolower($country->name()) === strtolower($name));
    }
}
