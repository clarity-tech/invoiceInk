<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;
use Carbon\Carbon;

class InvoiceSeeder extends ProductionSafeSeeder
{
    private int $invoiceCounter = 1;

    protected function seed(): void
    {
        $this->info('Seeding invoices and invoice items...');

        $organizations = Organization::with(['customers', 'primaryLocation'])->get();

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
            // Create different types of invoices for each customer
            $invoiceCount += $this->createInvoicesForCustomer($organization, $customer);
        }

        $this->info("Created {$invoiceCount} invoices for {$organization->name}");
    }

    private function createInvoicesForCustomer(Organization $organization, Customer $customer): int
    {
        $invoices = [];

        // Create a mix of invoices and estimates with different statuses and dates
        $invoiceScenarios = $this->getInvoiceScenarios($organization, $customer);

        foreach ($invoiceScenarios as $scenario) {
            $invoice = $this->createInvoiceWithItems($organization, $customer, $scenario);
            $invoices[] = $invoice;
        }

        return count($invoices);
    }

    private function getInvoiceScenarios(Organization $organization, Customer $customer): array
    {
        $baseScenarios = [
            // Recent invoice (draft)
            [
                'type' => 'invoice',
                'status' => 'draft',
                'date' => Carbon::now()->subDays(2),
                'due_date' => Carbon::now()->addDays(30),
                'items' => $this->getServiceItems($organization),
            ],
            // Sent invoice (pending payment)
            [
                'type' => 'invoice',
                'status' => 'sent',
                'date' => Carbon::now()->subDays(15),
                'due_date' => Carbon::now()->addDays(15),
                'items' => $this->getProductItems($organization),
            ],
            // Paid invoice (completed)
            [
                'type' => 'invoice',
                'status' => 'paid',
                'date' => Carbon::now()->subDays(45),
                'due_date' => Carbon::now()->subDays(15),
                'items' => $this->getMixedItems($organization),
            ],
            // Overdue invoice
            [
                'type' => 'invoice',
                'status' => 'sent',
                'date' => Carbon::now()->subDays(60),
                'due_date' => Carbon::now()->subDays(30),
                'items' => $this->getConsultingItems($organization),
            ],
            // Current estimate
            [
                'type' => 'estimate',
                'status' => 'draft',
                'date' => Carbon::now()->subDays(5),
                'due_date' => Carbon::now()->addDays(30),
                'items' => $this->getProjectItems($organization),
            ],
            // Sent estimate
            [
                'type' => 'estimate',
                'status' => 'sent',
                'date' => Carbon::now()->subDays(10),
                'due_date' => Carbon::now()->addDays(20),
                'items' => $this->getMaintenanceItems($organization),
            ],
        ];

        // Add company-specific scenarios
        return array_merge($baseScenarios, $this->getCompanySpecificScenarios($organization, $customer));
    }

    private function getCompanySpecificScenarios(Organization $organization, Customer $customer): array
    {
        switch ($organization->name) {
            case 'ACME Manufacturing Corp':
                return $this->getManufacturingScenarios();

            case 'TechStart Innovation Hub':
                return $this->getTechScenarios();

            case 'EuroConsult GmbH':
                return $this->getConsultingScenarios();

            case 'Demo Company Ltd':
                return $this->getDemoScenarios();

            default:
                return [];
        }
    }

    private function getManufacturingScenarios(): array
    {
        return [
            [
                'type' => 'invoice',
                'status' => 'paid',
                'date' => Carbon::now()->subMonths(3),
                'due_date' => Carbon::now()->subMonths(2)->subDays(15),
                'items' => [
                    [
                        'description' => 'Steel Components - Grade A',
                        'quantity' => 1000,
                        'unit_price' => 2500, // $25.00 each
                        'tax_rate' => 8, // 8% sales tax
                    ],
                    [
                        'description' => 'Manufacturing Setup Fee',
                        'quantity' => 1,
                        'unit_price' => 500000, // $5,000.00
                        'tax_rate' => 8,
                    ],
                ],
            ],
            [
                'type' => 'invoice',
                'status' => 'sent',
                'date' => Carbon::now()->subDays(20),
                'due_date' => Carbon::now()->addDays(10),
                'items' => [
                    [
                        'description' => 'Custom Machined Parts',
                        'quantity' => 500,
                        'unit_price' => 7500, // $75.00 each
                        'tax_rate' => 8,
                    ],
                    [
                        'description' => 'Quality Inspection Service',
                        'quantity' => 20,
                        'unit_price' => 15000, // $150.00 per hour
                        'tax_rate' => 8,
                    ],
                ],
            ],
        ];
    }

    private function getTechScenarios(): array
    {
        return [
            [
                'type' => 'invoice',
                'status' => 'paid',
                'date' => Carbon::now()->subMonths(1),
                'due_date' => Carbon::now()->subDays(15),
                'items' => [
                    [
                        'description' => 'Mobile App Development - Phase 1',
                        'quantity' => 1,
                        'unit_price' => 2500000, // $25,000.00
                        'tax_rate' => 0, // No tax on services in some states
                    ],
                    [
                        'description' => 'UI/UX Design Services',
                        'quantity' => 80,
                        'unit_price' => 15000, // $150.00 per hour
                        'tax_rate' => 0,
                    ],
                ],
            ],
            [
                'type' => 'estimate',
                'status' => 'sent',
                'date' => Carbon::now()->subDays(7),
                'due_date' => Carbon::now()->addDays(23),
                'items' => [
                    [
                        'description' => 'Cloud Infrastructure Setup',
                        'quantity' => 1,
                        'unit_price' => 1200000, // $12,000.00
                        'tax_rate' => 0,
                    ],
                    [
                        'description' => 'DevOps Consultation (monthly)',
                        'quantity' => 12,
                        'unit_price' => 300000, // $3,000.00 per month
                        'tax_rate' => 0,
                    ],
                ],
            ],
        ];
    }

    private function getConsultingScenarios(): array
    {
        return [
            [
                'type' => 'invoice',
                'status' => 'paid',
                'date' => Carbon::now()->subMonths(2),
                'due_date' => Carbon::now()->subMonths(1)->subDays(15),
                'items' => [
                    [
                        'description' => 'Strategic Business Consulting',
                        'quantity' => 120,
                        'unit_price' => 20000, // €200.00 per hour
                        'tax_rate' => 19, // German VAT
                    ],
                    [
                        'description' => 'Market Analysis Report',
                        'quantity' => 1,
                        'unit_price' => 750000, // €7,500.00
                        'tax_rate' => 19,
                    ],
                ],
            ],
            [
                'type' => 'invoice',
                'status' => 'sent',
                'date' => Carbon::now()->subDays(14),
                'due_date' => Carbon::now()->addDays(16),
                'items' => [
                    [
                        'description' => 'Digital Transformation Consultation',
                        'quantity' => 40,
                        'unit_price' => 25000, // €250.00 per hour
                        'tax_rate' => 19,
                    ],
                    [
                        'description' => 'Implementation Roadmap',
                        'quantity' => 1,
                        'unit_price' => 500000, // €5,000.00
                        'tax_rate' => 19,
                    ],
                ],
            ],
        ];
    }

    private function getDemoScenarios(): array
    {
        return [
            [
                'type' => 'invoice',
                'status' => 'paid',
                'date' => Carbon::now()->subDays(30),
                'due_date' => Carbon::now()->subDays(15),
                'items' => [
                    [
                        'description' => 'Software License (Annual)',
                        'quantity' => 1,
                        'unit_price' => 12000000, // ₹1,20,000.00
                        'tax_rate' => 18, // GST
                    ],
                    [
                        'description' => 'Training Services',
                        'quantity' => 5,
                        'unit_price' => 1500000, // ₹15,000.00 per day
                        'tax_rate' => 18,
                    ],
                ],
            ],
            [
                'type' => 'estimate',
                'status' => 'draft',
                'date' => Carbon::now()->subDays(3),
                'due_date' => Carbon::now()->addDays(27),
                'items' => [
                    [
                        'description' => 'Custom Integration Development',
                        'quantity' => 160,
                        'unit_price' => 250000, // ₹2,500.00 per hour
                        'tax_rate' => 18,
                    ],
                    [
                        'description' => 'API Development',
                        'quantity' => 1,
                        'unit_price' => 5000000, // ₹50,000.00
                        'tax_rate' => 18,
                    ],
                ],
            ],
        ];
    }

    private function createInvoiceWithItems(Organization $organization, Customer $customer, array $scenario): Invoice
    {
        $items = $scenario['items'];
        $subtotal = 0;
        $taxTotal = 0;

        // Calculate totals
        foreach ($items as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $subtotal += $lineTotal;
            $taxTotal += $lineTotal * ($item['tax_rate'] / 10000); // Convert basis points to decimal
        }

        $total = $subtotal + $taxTotal;

        // Generate invoice number
        $invoiceNumber = $this->generateInvoiceNumber($scenario['type'], $organization);

        // Create invoice
        $invoice = Invoice::create([
            'type' => $scenario['type'],
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'organization_location_id' => $organization->primaryLocation->id,
            'customer_location_id' => $customer->primaryLocation->id,
            'invoice_number' => $invoiceNumber,
            'status' => $scenario['status'],
            'issued_at' => $scenario['date'],
            'due_at' => $scenario['due_date'],
            'subtotal' => $subtotal,
            'tax' => $taxTotal,
            'total' => $total,
            'currency' => $organization->currency,
        ]);

        // Create invoice items
        foreach ($items as $itemData) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $itemData['description'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'tax_rate' => $itemData['tax_rate'],
            ]);
        }

        return $invoice;
    }

    private function getServiceItems(Organization $organization): array
    {
        return [
            [
                'description' => 'Professional Services',
                'quantity' => 20,
                'unit_price' => 15000, // $150.00 per hour
                'tax_rate' => $this->getTaxRate($organization),
            ],
            [
                'description' => 'Project Management',
                'quantity' => 1,
                'unit_price' => 200000, // $2,000.00
                'tax_rate' => $this->getTaxRate($organization),
            ],
        ];
    }

    private function getProductItems(Organization $organization): array
    {
        return [
            [
                'description' => 'Product License',
                'quantity' => 5,
                'unit_price' => 50000, // $500.00 each
                'tax_rate' => $this->getTaxRate($organization),
            ],
            [
                'description' => 'Setup and Configuration',
                'quantity' => 1,
                'unit_price' => 75000, // $750.00
                'tax_rate' => $this->getTaxRate($organization),
            ],
        ];
    }

    private function getMixedItems(Organization $organization): array
    {
        return [
            [
                'description' => 'Hardware Components',
                'quantity' => 10,
                'unit_price' => 25000, // $250.00 each
                'tax_rate' => $this->getTaxRate($organization),
            ],
            [
                'description' => 'Installation Services',
                'quantity' => 8,
                'unit_price' => 12500, // $125.00 per hour
                'tax_rate' => $this->getTaxRate($organization),
            ],
            [
                'description' => 'Extended Warranty',
                'quantity' => 1,
                'unit_price' => 30000, // $300.00
                'tax_rate' => $this->getTaxRate($organization),
            ],
        ];
    }

    private function getConsultingItems(Organization $organization): array
    {
        return [
            [
                'description' => 'Strategic Consulting',
                'quantity' => 40,
                'unit_price' => 20000, // $200.00 per hour
                'tax_rate' => $this->getTaxRate($organization),
            ],
            [
                'description' => 'Report Preparation',
                'quantity' => 1,
                'unit_price' => 100000, // $1,000.00
                'tax_rate' => $this->getTaxRate($organization),
            ],
        ];
    }

    private function getProjectItems(Organization $organization): array
    {
        return [
            [
                'description' => 'Project Phase 1',
                'quantity' => 1,
                'unit_price' => 500000, // $5,000.00
                'tax_rate' => $this->getTaxRate($organization),
            ],
            [
                'description' => 'Project Phase 2',
                'quantity' => 1,
                'unit_price' => 750000, // $7,500.00
                'tax_rate' => $this->getTaxRate($organization),
            ],
            [
                'description' => 'Project Phase 3',
                'quantity' => 1,
                'unit_price' => 600000, // $6,000.00
                'tax_rate' => $this->getTaxRate($organization),
            ],
        ];
    }

    private function getMaintenanceItems(Organization $organization): array
    {
        return [
            [
                'description' => 'Monthly Maintenance',
                'quantity' => 12,
                'unit_price' => 50000, // $500.00 per month
                'tax_rate' => $this->getTaxRate($organization),
            ],
            [
                'description' => 'Emergency Support',
                'quantity' => 1,
                'unit_price' => 150000, // $1,500.00
                'tax_rate' => $this->getTaxRate($organization),
            ],
        ];
    }

    private function getTaxRate(Organization $organization): int
    {
        return match ($organization->currency->value) {
            'USD' => rand(0, 1) ? 8 : 0, // Some states have no sales tax
            'EUR' => 19, // German VAT
            'GBP' => 20, // UK VAT
            'INR' => 18, // GST
            'AED' => 5, // UAE VAT
            default => 10,
        };
    }

    private function generateInvoiceNumber(string $type, Organization $organization): string
    {
        $prefix = $type === 'invoice' ? 'INV' : 'EST';
        $organizationCode = strtoupper(substr(str_replace(' ', '', $organization->name), 0, 3));
        $timestamp = Carbon::now()->format('Ymd');
        $counter = str_pad($this->invoiceCounter++, 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$organizationCode}-{$timestamp}-{$counter}";
    }
}
