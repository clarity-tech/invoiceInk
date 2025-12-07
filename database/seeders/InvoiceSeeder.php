<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;

class InvoiceSeeder extends ProductionSafeSeeder
{
    protected function seed(): void
    {
        $this->info('Seeding invoices using factory states...');

        $organizations = Organization::with(['customers', 'primaryLocation'])
            ->where('personal_team', false)
            ->get();

        foreach ($organizations as $organization) {
            if ($organization->customers->count() > 0 && $organization->primaryLocation) {
                $this->createInvoicesForOrganization($organization);
            }
        }

        $this->info('Created invoices and invoice items successfully!');
    }

    private function createInvoicesForOrganization(Organization $organization): void
    {
        $customers = $organization->customers;
        $invoiceCount = 0;

        foreach ($customers as $customer) {
            $invoiceCount += $this->createInvoicesForCustomer($organization, $customer);
        }

        $this->info("✓ Created {$invoiceCount} invoices for {$organization->name}");
    }

    private function createInvoicesForCustomer(Organization $organization, Customer $customer): int
    {
        $invoiceCount = 0;

        // Create invoices based on customer location and organization type
        if ($this->isForeignCustomer($customer, $organization)) {
            // Create foreign currency invoices for foreign customers
            $invoiceCount += $this->createForeignCurrencyInvoices($organization, $customer);
        }

        // Always create some domestic currency invoices
        $domesticInvoices = $this->createDomesticCurrencyInvoices($organization, $customer);
        $invoiceCount += $domesticInvoices;

        return $invoiceCount;
    }

    private function isForeignCustomer(Customer $customer, Organization $organization): bool
    {
        $customerCountry = $customer->primaryLocation?->country ?? 'IN';
        $orgCountry = $organization->country_code?->value ?? 'IN';

        return $customerCountry !== $orgCountry;
    }

    private function createForeignCurrencyInvoices(Organization $organization, Customer $customer): int
    {
        $customerCountry = $customer->primaryLocation?->country ?? 'IN';
        $invoices = [];

        // Create foreign currency invoices based on customer location
        if ($customerCountry === 'AE') {
            $invoices = [
                Invoice::factory()->consultingInvoice()->aedInvoice()->sent(),
                Invoice::factory()->techServicesInvoice()->aedInvoice()->recentlyPaid(),
            ];
        } elseif ($customerCountry === 'US') {
            $invoices = [
                Invoice::factory()->techServicesInvoice()->usdInvoice()->recentlyPaid(),
                Invoice::factory()->consultingInvoice()->usdInvoice()->overdueInvoice(),
            ];
        } elseif ($customerCountry === 'DE') {
            $invoices = [
                Invoice::factory()->consultingInvoice()->eurInvoice()->sent(),
                Invoice::factory()->techServicesInvoice()->eurInvoice()->overdueInvoice(),
            ];
        }

        $invoiceCount = 0;
        foreach ($invoices as $factory) {
            $invoice = $factory
                ->for($organization)
                ->for($customer)
                ->state([
                    'organization_location_id' => $organization->primaryLocation->id,
                    'customer_location_id' => $customer->primaryLocation->id,
                    'customer_shipping_location_id' => $customer->primaryLocation->id,
                ])
                ->withLocations()
                ->create();

            $this->createInvoiceItemsForInvoice($invoice, $organization);
            $invoiceCount++;
        }

        return $invoiceCount;
    }

    private function createDomesticCurrencyInvoices(Organization $organization, Customer $customer): int
    {
        // Get a subset of domestic invoices to avoid overwhelming data
        $allFactories = $this->getInvoiceFactoriesForOrganization($organization);
        $domesticFactories = array_slice($allFactories, 0, 2); // Take first 2 patterns

        $invoiceCount = 0;
        foreach ($domesticFactories as $factory) {
            $invoice = $factory
                ->for($organization)
                ->for($customer)
                ->state([
                    'organization_location_id' => $organization->primaryLocation->id,
                    'customer_location_id' => $customer->primaryLocation->id,
                    'customer_shipping_location_id' => $customer->primaryLocation->id,
                    'currency' => $organization->currency,
                ])
                ->withLocations()
                ->create();

            $this->createInvoiceItemsForInvoice($invoice, $organization);
            $invoiceCount++;
        }

        return $invoiceCount;
    }

    private function getInvoiceFactoriesForOrganization(Organization $organization): array
    {
        return match (true) {
            str_contains($organization->name, 'Manufacturing') => [
                Invoice::factory()->manufacturingInvoice()->recentDraft(),
                Invoice::factory()->autoPartsInvoice()->sent(),
                Invoice::factory()->manufacturingInvoice()->recentlyPaid(),
                Invoice::factory()->manufacturingInvoice()->overdueInvoice(),
                Invoice::factory()->estimate()->manufacturingInvoice()->draft(),
                Invoice::factory()->estimate()->manufacturingInvoice()->sent(),
            ],

            str_contains($organization->name, 'TechStart') => [
                Invoice::factory()->techServicesInvoice()->recentDraft(),
                Invoice::factory()->techServicesInvoice()->sent(),
                Invoice::factory()->techServicesInvoice()->recentlyPaid(),
                Invoice::factory()->digitalMarketingInvoice()->sent(),
                Invoice::factory()->estimate()->techServicesInvoice()->approvedEstimate(),
                Invoice::factory()->estimate()->techServicesInvoice()->draft(),
            ],

            str_contains($organization->name, 'EuroConsult') => [
                Invoice::factory()->consultingInvoice()->eurInvoice()->recentlyPaid(),
                Invoice::factory()->consultingInvoice()->eurInvoice()->sent(),
                Invoice::factory()->consultingInvoice()->eurInvoice()->overdueInvoice(),
                Invoice::factory()->estimate()->consultingInvoice()->eurInvoice()->sent(),
                Invoice::factory()->estimate()->consultingInvoice()->eurInvoice()->rejectedEstimate(),
            ],

            str_contains($organization->name, 'Demo Company') => [
                Invoice::factory()->digitalMarketingInvoice()->inrInvoice()->recentlyPaid(),
                Invoice::factory()->techServicesInvoice()->inrInvoice()->sent(),
                Invoice::factory()->consultingInvoice()->inrInvoice()->draft(),
                Invoice::factory()->estimate()->techServicesInvoice()->inrInvoice()->draft(),
                Invoice::factory()->estimate()->digitalMarketingInvoice()->inrInvoice()->sent(),
                // Foreign customer AED invoices (Dubai customers)
                Invoice::factory()->consultingInvoice()->aedInvoice()->sent(),
                Invoice::factory()->techServicesInvoice()->aedInvoice()->recentlyPaid(),
                Invoice::factory()->estimate()->consultingInvoice()->aedInvoice()->draft(),
            ],

            str_contains($organization->name, 'Dubai Trading') => [
                Invoice::factory()->consultingInvoice()->aedInvoice()->sent(),
                Invoice::factory()->techServicesInvoice()->aedInvoice()->recentlyPaid(),
                Invoice::factory()->estimate()->consultingInvoice()->aedInvoice()->draft(),
            ],

            // GlobalCorp organizations
            str_contains($organization->name, 'GlobalCorp Holdings') => [
                Invoice::factory()->enterpriseAmount()->consultingInvoice()->inrInvoice()->recentlyPaid(),
                Invoice::factory()->largeAmount()->manufacturingInvoice()->inrInvoice()->sent(),
                Invoice::factory()->estimate()->enterpriseAmount()->consultingInvoice()->inrInvoice()->approvedEstimate(),
                // International customers with different currencies
                Invoice::factory()->consultingInvoice()->usdInvoice()->overdueInvoice(),
                Invoice::factory()->techServicesInvoice()->eurInvoice()->sent(),
                Invoice::factory()->estimate()->manufacturingInvoice()->aedInvoice()->draft(),
            ],

            str_contains($organization->name, 'GlobalCorp Tech') => [
                Invoice::factory()->techServicesInvoice()->inrInvoice()->recentlyPaid(),
                Invoice::factory()->digitalMarketingInvoice()->inrInvoice()->sent(),
                Invoice::factory()->estimate()->techServicesInvoice()->inrInvoice()->draft(),
                // Foreign customers
                Invoice::factory()->techServicesInvoice()->usdInvoice()->recentlyPaid(),
                Invoice::factory()->consultingInvoice()->eurInvoice()->overdueInvoice(),
            ],

            str_contains($organization->name, 'GlobalCorp Business') => [
                Invoice::factory()->consultingInvoice()->inrInvoice()->overdueInvoice(),
                Invoice::factory()->techServicesInvoice()->inrInvoice()->recentlyPaid(),
                Invoice::factory()->estimate()->consultingInvoice()->inrInvoice()->sent(),
            ],

            default => [
                Invoice::factory()->mediumAmount()->sent(),
                Invoice::factory()->smallAmount()->recentlyPaid(),
                Invoice::factory()->estimate()->draft(),
            ],
        };
    }

    private function createInvoiceItemsForInvoice(Invoice $invoice, Organization $organization): void
    {
        // Create appropriate invoice items based on invoice type and organization
        $itemsData = $this->getInvoiceItemsForType($invoice->type, $organization);

        foreach ($itemsData as $itemData) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $itemData['description'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'tax_rate' => $itemData['tax_rate'],
            ]);
        }
    }

    private function getInvoiceItemsForType(string $invoiceType, Organization $organization): array
    {
        return match (true) {
            str_contains($organization->name, 'Manufacturing') => $this->getManufacturingItems($organization),
            str_contains($organization->name, 'TechStart') => $this->getTechItems($organization),
            str_contains($organization->name, 'EuroConsult') => $this->getConsultingItems($organization),
            str_contains($organization->name, 'Demo Company') => $this->getDemoItems($organization),
            str_contains($organization->name, 'Dubai Trading') => $this->getTradingItems($organization),
            str_contains($organization->name, 'GlobalCorp') => $this->getEnterpriseItems($organization),
            default => $this->getGeneralServiceItems($organization),
        };
    }

    private function getManufacturingItems(Organization $organization): array
    {
        return fake()->randomElement([
            // Steel manufacturing items
            [
                ['description' => 'Steel Components - Grade A', 'quantity' => 1000, 'unit_price' => 2500, 'tax_rate' => $this->getTaxRate($organization)],
                ['description' => 'Manufacturing Setup Fee', 'quantity' => 1, 'unit_price' => 500000, 'tax_rate' => $this->getTaxRate($organization)],
            ],
            // Machined parts
            [
                ['description' => 'Custom Machined Parts', 'quantity' => 500, 'unit_price' => 7500, 'tax_rate' => $this->getTaxRate($organization)],
                ['description' => 'Quality Inspection Service', 'quantity' => 20, 'unit_price' => 15000, 'tax_rate' => $this->getTaxRate($organization)],
            ],
        ]);
    }

    private function getTechItems(Organization $organization): array
    {
        return fake()->randomElement([
            // Software development
            [
                ['description' => 'Mobile App Development - Phase 1', 'quantity' => 1, 'unit_price' => 2500000, 'tax_rate' => $this->getTaxRate($organization)],
                ['description' => 'UI/UX Design Services', 'quantity' => 80, 'unit_price' => 15000, 'tax_rate' => $this->getTaxRate($organization)],
            ],
            // Infrastructure
            [
                ['description' => 'Cloud Infrastructure Setup', 'quantity' => 1, 'unit_price' => 1200000, 'tax_rate' => $this->getTaxRate($organization)],
                ['description' => 'DevOps Consultation (monthly)', 'quantity' => 12, 'unit_price' => 300000, 'tax_rate' => $this->getTaxRate($organization)],
            ],
        ]);
    }

    private function getConsultingItems(Organization $organization): array
    {
        return fake()->randomElement([
            // Strategic consulting
            [
                ['description' => 'Strategic Business Consulting', 'quantity' => 120, 'unit_price' => 20000, 'tax_rate' => $this->getTaxRate($organization)],
                ['description' => 'Market Analysis Report', 'quantity' => 1, 'unit_price' => 750000, 'tax_rate' => $this->getTaxRate($organization)],
            ],
            // Digital transformation
            [
                ['description' => 'Digital Transformation Consultation', 'quantity' => 40, 'unit_price' => 25000, 'tax_rate' => $this->getTaxRate($organization)],
                ['description' => 'Implementation Roadmap', 'quantity' => 1, 'unit_price' => 500000, 'tax_rate' => $this->getTaxRate($organization)],
            ],
        ]);
    }

    private function getDemoItems(Organization $organization): array
    {
        return fake()->randomElement([
            // Software license
            [
                ['description' => 'Software License (Annual)', 'quantity' => 1, 'unit_price' => 12000000, 'tax_rate' => $this->getTaxRate($organization)],
                ['description' => 'Training Services', 'quantity' => 5, 'unit_price' => 1500000, 'tax_rate' => $this->getTaxRate($organization)],
            ],
            // Development
            [
                ['description' => 'Custom Integration Development', 'quantity' => 160, 'unit_price' => 250000, 'tax_rate' => $this->getTaxRate($organization)],
                ['description' => 'API Development', 'quantity' => 1, 'unit_price' => 5000000, 'tax_rate' => $this->getTaxRate($organization)],
            ],
        ]);
    }

    private function getTradingItems(Organization $organization): array
    {
        return fake()->randomElement([
            // Trading services
            [
                ['description' => 'Import/Export Consultation', 'quantity' => 20, 'unit_price' => 25000, 'tax_rate' => $this->getTaxRate($organization)],
                ['description' => 'Trade Documentation', 'quantity' => 1, 'unit_price' => 150000, 'tax_rate' => $this->getTaxRate($organization)],
            ],
            // Logistics
            [
                ['description' => 'Logistics Coordination', 'quantity' => 1, 'unit_price' => 350000, 'tax_rate' => $this->getTaxRate($organization)],
                ['description' => 'Customs Clearance Service', 'quantity' => 3, 'unit_price' => 75000, 'tax_rate' => $this->getTaxRate($organization)],
            ],
        ]);
    }

    private function getEnterpriseItems(Organization $organization): array
    {
        return fake()->randomElement([
            // Enterprise consulting
            [
                ['description' => 'Enterprise Strategy Consultation', 'quantity' => 200, 'unit_price' => 30000, 'tax_rate' => $this->getTaxRate($organization)],
                ['description' => 'Business Process Analysis', 'quantity' => 1, 'unit_price' => 2000000, 'tax_rate' => $this->getTaxRate($organization)],
            ],
            // Technology solutions
            [
                ['description' => 'Enterprise Software License', 'quantity' => 1, 'unit_price' => 5000000, 'tax_rate' => $this->getTaxRate($organization)],
                ['description' => 'Implementation Services', 'quantity' => 300, 'unit_price' => 25000, 'tax_rate' => $this->getTaxRate($organization)],
            ],
        ]);
    }

    private function getGeneralServiceItems(Organization $organization): array
    {
        return [
            ['description' => 'Professional Services', 'quantity' => 20, 'unit_price' => 15000, 'tax_rate' => $this->getTaxRate($organization)],
            ['description' => 'Project Management', 'quantity' => 1, 'unit_price' => 200000, 'tax_rate' => $this->getTaxRate($organization)],
        ];
    }

    private function getTaxRate(Organization $organization): int
    {
        return match ($organization->currency->value) {
            'USD' => fake()->randomElement([0, 4, 6, 8]), // US state sales tax rates
            'EUR' => fake()->randomElement([0, 7, 19]), // German VAT rates
            'GBP' => fake()->randomElement([0, 5, 20]), // UK VAT rates
            'INR' => fake()->randomElement([0, 5, 12, 18, 28]), // GST rates
            'AED' => fake()->randomElement([0, 5]), // UAE VAT rates
            default => 10,
        };
    }
}
