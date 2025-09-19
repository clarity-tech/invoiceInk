<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
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
            'organization_id' => null, // Will be set by relationships
            'customer_id' => null, // Will be set by relationships
            'organization_location_id' => null, // Will be set by relationships
            'customer_location_id' => null, // Will be set by relationships
            'invoice_number' => $number,
            'status' => fake()->randomElement(['draft', 'sent', 'paid', 'void']),
            'issued_at' => fake()->optional(0.8)->dateTimeBetween('-6 months', 'now'),
            'due_at' => fake()->optional(0.7)->dateTimeBetween('now', '+3 months'),
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
        return $this->afterMaking(function (Invoice $invoice) {
            $organization = Organization::factory()->withLocation()->create();
            $customer = Customer::factory()->withLocation()->create();

            $invoice->organization_id = $organization->id;
            $invoice->customer_id = $customer->id;
            $invoice->organization_location_id = $organization->primaryLocation->id;
            $invoice->customer_location_id = $customer->primaryLocation->id;
        });
    }

    /**
     * Create an invoice type document
     */
    public function invoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'invoice',
            'invoice_number' => 'INV-'.fake()->unique()->numberBetween(1000, 9999),
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
}
