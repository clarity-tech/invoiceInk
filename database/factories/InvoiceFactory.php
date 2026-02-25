<?php

namespace Database\Factories;

use App\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Services\InvoiceNumberingService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['invoice', 'estimate']);
        $prefix = $type === 'invoice' ? 'INV' : 'EST';
        $number = $prefix.'-'.fake()->unique()->numberBetween(1000, 9999);

        $subtotal = fake()->numberBetween(50000, 500000); // $500 to $5,000 in cents
        $taxRate = fake()->randomElement([0, 5, 12, 18, 28]); // Common GST rates
        $tax = intval($subtotal * $taxRate / 100);
        $total = $subtotal + $tax;

        return [
            'type' => $type,
            'organization_id' => Organization::factory()->withLocation(),
            'customer_id' => Customer::factory()->withLocation(),
            'organization_location_id' => function (array $attributes) {
                return Organization::find($attributes['organization_id'])->primaryLocation->id;
            },
            'customer_location_id' => function (array $attributes) {
                return Customer::find($attributes['customer_id'])->primaryLocation->id;
            },
            'customer_shipping_location_id' => function (array $attributes) {
                // Default shipping location same as billing location
                return Customer::find($attributes['customer_id'])->primaryLocation->id;
            },
            'invoice_number' => $number,
            'status' => fake()->randomElement(['draft', 'sent', 'paid', 'void']),
            'issued_at' => fake()->optional(0.8)->dateTimeBetween('-6 months', 'now'),
            'due_at' => fake()->optional(0.7)->dateTimeBetween('now', '+3 months'),
            'currency' => fake()->randomElement(Currency::cases())->value,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ];
    }

    /**
     * Create an invoice with organization and customer locations
     */
    public function withLocations(): static
    {
        return $this->afterCreating(function (Invoice $invoice) {
            // Use numbering service for invoices after creation
            if ($invoice->type === 'invoice') {
                $numberingService = new InvoiceNumberingService;
                $invoiceNumberData = $numberingService->generateInvoiceNumber(
                    $invoice->organization,
                    $invoice->organizationLocation
                );
                $invoice->update([
                    'invoice_number' => $invoiceNumberData['invoice_number'],
                    'invoice_numbering_series_id' => $invoiceNumberData['series_id'],
                ]);
            }
        });
    }

    /**
     * Create an invoice type document
     */
    public function invoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'invoice',
            'invoice_number' => 'INV-'.fake()->unique()->numberBetween(1000, 9999), // Will be overridden by numbering service when used with withLocations
            'status' => fake()->randomElement(['draft', 'sent', 'paid']),
        ]);
    }

    /**
     * Create an estimate type document
     */
    public function estimate(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'estimate',
            'invoice_number' => 'EST-'.fake()->unique()->numberBetween(1000, 9999),
            'status' => fake()->randomElement(['draft', 'sent']),
        ]);
    }

    /**
     * Create a draft document
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'issued_at' => null,
        ]);
    }

    /**
     * Create a sent document
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'issued_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ]);
    }

    /**
     * Create a paid invoice
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'invoice',
            'status' => 'paid',
            'issued_at' => fake()->dateTimeBetween('-6 months', '-1 month'),
            'due_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Create a voided document
     */
    public function voided(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'void',
        ]);
    }

    /**
     * Create a document with no tax
     */
    public function withoutTax(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'] ?? fake()->numberBetween(50000, 500000);

            return [
                'subtotal' => $subtotal,
                'tax' => 0,
                'total' => $subtotal,
            ];
        });
    }

    /**
     * Create a document with high tax
     */
    public function withHighTax(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'] ?? fake()->numberBetween(50000, 500000);
            $tax = intval($subtotal * 28 / 100); // 28% GST

            return [
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
            ];
        });
    }

    /**
     * Create a document with specific amounts
     */
    public function withAmounts(int $subtotal, float $taxRate = 18): static
    {
        return $this->state(function (array $attributes) use ($subtotal, $taxRate) {
            $tax = intval($subtotal * $taxRate / 100);

            return [
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
            ];
        });
    }

    /**
     * Create a large amount document
     */
    public function largeAmount(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = fake()->numberBetween(1000000, 10000000); // $10,000 to $100,000
            $tax = intval($subtotal * 18 / 100);

            return [
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
            ];
        });
    }

    // =====================================================================
    // INDUSTRY-SPECIFIC INVOICE TYPES
    // =====================================================================

    /**
     * Manufacturing industry invoice (B2B manufacturing items)
     */
    public function manufacturingInvoice(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = fake()->numberBetween(500000, 2000000); // $5,000 to $20,000
            $tax = intval($subtotal * 18 / 100);

            return [
                'type' => 'invoice',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
                'currency' => 'USD',
                'notes' => 'Industrial manufacturing components and services.',
                'terms' => 'Payment due within 30 days. Late payment fees may apply.',
            ];
        });
    }

    /**
     * Auto parts industry invoice
     */
    public function autoPartsInvoice(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = fake()->numberBetween(250000, 1000000); // $2,500 to $10,000
            $tax = intval($subtotal * 8.25 / 100); // US state sales tax

            return [
                'type' => 'invoice',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
                'currency' => 'USD',
                'notes' => 'Automotive parts and components.',
                'terms' => 'Payment due within 45 days.',
            ];
        });
    }

    /**
     * Tech services invoice (Software development, IT services)
     */
    public function techServicesInvoice(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = fake()->numberBetween(150000, 800000); // $1,500 to $8,000
            $tax = intval($subtotal * 0 / 100); // Many tech services are tax-exempt

            return [
                'type' => 'invoice',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
                'currency' => 'USD',
                'notes' => 'Software development and technical consulting services.',
                'terms' => 'Payment due within 15 days. Project milestone payment.',
            ];
        });
    }

    /**
     * Professional consulting invoice
     */
    public function consultingInvoice(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = fake()->numberBetween(300000, 1500000); // $3,000 to $15,000
            $tax = intval($subtotal * 19 / 100); // German VAT

            return [
                'type' => 'invoice',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
                'currency' => 'EUR',
                'notes' => 'Professional business consulting and advisory services.',
                'terms' => 'Payment due within 30 days. Consulting retainer.',
            ];
        });
    }

    /**
     * Digital marketing invoice
     */
    public function digitalMarketingInvoice(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = fake()->numberBetween(100000, 500000); // $1,000 to $5,000
            $tax = intval($subtotal * 18 / 100); // GST

            return [
                'type' => 'invoice',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
                'currency' => 'INR',
                'notes' => 'Digital marketing campaigns and social media management.',
                'terms' => 'Payment due within 30 days. Monthly service fee.',
            ];
        });
    }

    // =====================================================================
    // MULTI-CURRENCY INVOICE STATES
    // =====================================================================

    /**
     * USD invoice configuration
     */
    public function usdInvoice(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'] ?? fake()->numberBetween(100000, 1000000);
            $taxRate = fake()->randomElement([0, 4, 6, 8.25]); // US tax rates
            $tax = intval($subtotal * $taxRate / 100);

            return [
                'currency' => 'USD',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
            ];
        });
    }

    /**
     * EUR invoice configuration
     */
    public function eurInvoice(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'] ?? fake()->numberBetween(100000, 1000000);
            $taxRate = fake()->randomElement([0, 7, 19]); // German VAT rates
            $tax = intval($subtotal * $taxRate / 100);

            return [
                'currency' => 'EUR',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
            ];
        });
    }

    /**
     * INR invoice configuration
     */
    public function inrInvoice(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'] ?? fake()->numberBetween(100000, 1000000);
            $taxRate = fake()->randomElement([0, 5, 12, 18, 28]); // GST rates
            $tax = intval($subtotal * $taxRate / 100);

            return [
                'currency' => 'INR',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
            ];
        });
    }

    /**
     * AED invoice configuration
     */
    public function aedInvoice(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'] ?? fake()->numberBetween(100000, 1000000);
            $taxRate = fake()->randomElement([0, 5]); // UAE VAT rates
            $tax = intval($subtotal * $taxRate / 100);

            return [
                'currency' => 'AED',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
            ];
        });
    }

    // =====================================================================
    // ENHANCED WORKFLOW STATES
    // =====================================================================

    /**
     * Recent draft invoice (just created)
     */
    public function recentDraft(): static
    {
        return $this->state([
            'status' => 'draft',
            'type' => 'invoice',
            'issued_at' => null,
            'due_at' => null,
            'created_at' => fake()->dateTimeBetween('-3 days', 'now'),
        ]);
    }

    /**
     * Overdue invoice (past due date)
     */
    public function overdueInvoice(): static
    {
        return $this->state([
            'type' => 'invoice',
            'status' => 'sent',
            'issued_at' => fake()->dateTimeBetween('-3 months', '-2 months'),
            'due_at' => fake()->dateTimeBetween('-2 months', '-1 week'),
        ]);
    }

    /**
     * Recently paid invoice
     */
    public function recentlyPaid(): static
    {
        return $this->state([
            'type' => 'invoice',
            'status' => 'paid',
            'issued_at' => fake()->dateTimeBetween('-2 months', '-1 month'),
            'due_at' => fake()->dateTimeBetween('-1 month', '-2 weeks'),
        ]);
    }

    /**
     * Approved estimate (client accepted)
     */
    public function approvedEstimate(): static
    {
        return $this->state([
            'type' => 'estimate',
            'status' => 'sent', // Would need new status 'approved' in enum
            'issued_at' => fake()->dateTimeBetween('-1 month', '-1 week'),
            'due_at' => fake()->dateTimeBetween('now', '+2 weeks'),
            'notes' => 'Estimate approved by client. Ready for project initiation.',
        ]);
    }

    /**
     * Rejected estimate
     */
    public function rejectedEstimate(): static
    {
        return $this->state([
            'type' => 'estimate',
            'status' => 'void',
            'issued_at' => fake()->dateTimeBetween('-2 months', '-1 month'),
            'due_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'notes' => 'Estimate declined by client.',
        ]);
    }

    // =====================================================================
    // AMOUNT RANGE STATES
    // =====================================================================

    /**
     * Small invoice (under $1,000)
     */
    public function smallAmount(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = fake()->numberBetween(10000, 100000); // $100 to $1,000
            $taxRate = 18;
            $tax = intval($subtotal * $taxRate / 100);

            return [
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
            ];
        });
    }

    /**
     * Medium invoice ($1,000 to $10,000)
     */
    public function mediumAmount(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = fake()->numberBetween(100000, 1000000); // $1,000 to $10,000
            $taxRate = 18;
            $tax = intval($subtotal * $taxRate / 100);

            return [
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
            ];
        });
    }

    /**
     * Enterprise invoice (over $10,000)
     */
    public function enterpriseAmount(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = fake()->numberBetween(1000000, 5000000); // $10,000 to $50,000
            $taxRate = 18;
            $tax = intval($subtotal * $taxRate / 100);

            return [
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
            ];
        });
    }
}
