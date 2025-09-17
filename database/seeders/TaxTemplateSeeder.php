<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\TaxTemplate;
use Illuminate\Database\Seeder;

class TaxTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating tax templates for all organizations...');

        $organizations = Organization::where('personal_team', false)->get();

        foreach ($organizations as $organization) {
            $this->createTaxTemplatesForOrganization($organization);
        }

        $this->command->info('Tax templates created successfully!');
    }

    private function createTaxTemplatesForOrganization(Organization $organization): void
    {
        $countryTaxes = $this->getTaxTemplatesByCountry($organization->currency);

        foreach ($countryTaxes as $taxData) {
            TaxTemplate::create([
                'organization_id' => $organization->id,
                'name' => $taxData['name'],
                'type' => $taxData['type'],
                'rate' => $taxData['rate'],
                'category' => $taxData['category'],
                'country_code' => $taxData['country_code'],
                'description' => $taxData['description'],
                'is_active' => true,
                'metadata' => $taxData['metadata'] ?? null,
            ]);
        }
    }

    private function getTaxTemplatesByCountry(string $currency): array
    {
        return match ($currency) {
            'INR' => $this->getIndiaTaxTemplates(),
            'USD' => $this->getUSATaxTemplates(),
            'EUR' => $this->getGermanyTaxTemplates(),
            'GBP' => $this->getUKTaxTemplates(),
            default => $this->getGenericTaxTemplates(),
        };
    }

    private function getIndiaTaxTemplates(): array
    {
        return [
            [
                'name' => 'CGST 9%',
                'type' => 'CGST',
                'rate' => 9.000,
                'category' => 'Central GST',
                'country_code' => 'IN',
                'description' => 'Central Goods and Services Tax at 9%',
                'metadata' => ['gst_component' => 'central'],
            ],
            [
                'name' => 'SGST 9%',
                'type' => 'SGST',
                'rate' => 9.000,
                'category' => 'State GST',
                'country_code' => 'IN',
                'description' => 'State Goods and Services Tax at 9%',
                'metadata' => ['gst_component' => 'state'],
            ],
            [
                'name' => 'IGST 18%',
                'type' => 'IGST',
                'rate' => 18.000,
                'category' => 'Integrated GST',
                'country_code' => 'IN',
                'description' => 'Integrated Goods and Services Tax at 18%',
                'metadata' => ['gst_component' => 'integrated'],
            ],
            [
                'name' => 'GST 5%',
                'type' => 'GST',
                'rate' => 5.000,
                'category' => 'Standard Rate',
                'country_code' => 'IN',
                'description' => 'Goods and Services Tax at 5%',
            ],
            [
                'name' => 'GST 12%',
                'type' => 'GST',
                'rate' => 12.000,
                'category' => 'Standard Rate',
                'country_code' => 'IN',
                'description' => 'Goods and Services Tax at 12%',
            ],
            [
                'name' => 'GST 28%',
                'type' => 'GST',
                'rate' => 28.000,
                'category' => 'Luxury Rate',
                'country_code' => 'IN',
                'description' => 'Goods and Services Tax at 28%',
            ],
            [
                'name' => 'TDS 10%',
                'type' => 'TDS',
                'rate' => 10.000,
                'category' => 'Tax Deducted at Source',
                'country_code' => 'IN',
                'description' => 'Tax Deducted at Source at 10%',
            ],
        ];
    }

    private function getUSATaxTemplates(): array
    {
        return [
            [
                'name' => 'Sales Tax 6%',
                'type' => 'Sales Tax',
                'rate' => 6.000,
                'category' => 'Standard Rate',
                'country_code' => 'US',
                'description' => 'State Sales Tax at 6%',
            ],
            [
                'name' => 'Sales Tax 8.25%',
                'type' => 'Sales Tax',
                'rate' => 8.250,
                'category' => 'California Rate',
                'country_code' => 'US',
                'description' => 'California State Sales Tax at 8.25%',
            ],
            [
                'name' => 'Sales Tax 4%',
                'type' => 'Sales Tax',
                'rate' => 4.000,
                'category' => 'Low Rate',
                'country_code' => 'US',
                'description' => 'State Sales Tax at 4%',
            ],
            [
                'name' => 'No Tax',
                'type' => 'Exempt',
                'rate' => 0.000,
                'category' => 'Tax Exempt',
                'country_code' => 'US',
                'description' => 'Tax Exempt Items',
            ],
        ];
    }

    private function getGermanyTaxTemplates(): array
    {
        return [
            [
                'name' => 'VAT 19%',
                'type' => 'VAT',
                'rate' => 19.000,
                'category' => 'Standard Rate',
                'country_code' => 'DE',
                'description' => 'Value Added Tax at standard rate of 19%',
            ],
            [
                'name' => 'VAT 7%',
                'type' => 'VAT',
                'rate' => 7.000,
                'category' => 'Reduced Rate',
                'country_code' => 'DE',
                'description' => 'Value Added Tax at reduced rate of 7%',
            ],
            [
                'name' => 'VAT 0%',
                'type' => 'VAT',
                'rate' => 0.000,
                'category' => 'Zero Rate',
                'country_code' => 'DE',
                'description' => 'Zero-rated VAT for exports and certain services',
            ],
        ];
    }

    private function getUKTaxTemplates(): array
    {
        return [
            [
                'name' => 'VAT 20%',
                'type' => 'VAT',
                'rate' => 20.000,
                'category' => 'Standard Rate',
                'country_code' => 'GB',
                'description' => 'Value Added Tax at standard rate of 20%',
            ],
            [
                'name' => 'VAT 5%',
                'type' => 'VAT',
                'rate' => 5.000,
                'category' => 'Reduced Rate',
                'country_code' => 'GB',
                'description' => 'Value Added Tax at reduced rate of 5%',
            ],
            [
                'name' => 'VAT 0%',
                'type' => 'VAT',
                'rate' => 0.000,
                'category' => 'Zero Rate',
                'country_code' => 'GB',
                'description' => 'Zero-rated VAT for certain goods and services',
            ],
        ];
    }

    private function getGenericTaxTemplates(): array
    {
        return [
            [
                'name' => 'Standard Tax 10%',
                'type' => 'Standard Tax',
                'rate' => 10.000,
                'category' => 'Standard Rate',
                'country_code' => 'XX',
                'description' => 'Standard tax rate at 10%',
            ],
            [
                'name' => 'No Tax',
                'type' => 'Exempt',
                'rate' => 0.000,
                'category' => 'Tax Exempt',
                'country_code' => 'XX',
                'description' => 'Tax exempt items',
            ],
        ];
    }
}
