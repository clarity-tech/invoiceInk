<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $services = [
            'Website Development',
            'Mobile App Development',
            'Software Consulting',
            'UI/UX Design',
            'Digital Marketing',
            'SEO Services',
            'Content Writing',
            'Database Development',
            'API Integration',
            'Quality Assurance',
            'Project Management',
            'Technical Support',
            'Cloud Services',
            'DevOps Services',
            'Security Audit',
        ];

        return [
            'invoice_id' => null, // Will be set by relationships
            'description' => fake()->randomElement($services),
            'quantity' => fake()->numberBetween(1, 10),
            'unit_price' => fake()->numberBetween(5000, 50000), // $50 to $500 in cents
            'tax_rate' => fake()->randomElement([0, 5, 12, 18, 28]), // Common GST rates as percentages
        ];
    }

    /**
     * Create an item for a specific invoice
     */
    public function forInvoice(Invoice $invoice): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_id' => $invoice->id,
        ]);
    }

    /**
     * Create an item with no tax
     */
    public function withoutTax(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_rate' => 0,
        ]);
    }

    /**
     * Create an item with null tax rate
     */
    public function withNullTax(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_rate' => null,
        ]);
    }

    /**
     * Create an item with high tax rate
     */
    public function withHighTax(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_rate' => 28, // 28% as users would enter
        ]);
    }

    /**
     * Create an item with fractional tax rate
     */
    public function withFractionalTax(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_rate' => fake()->randomElement([2.5, 7.5, 12.5, 18.5]), // Fractional rates as percentages
        ]);
    }

    /**
     * Create a consulting service item
     */
    public function consultingService(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => fake()->randomElement([
                'Software Architecture Consulting',
                'Technical Strategy Consulting',
                'Digital Transformation Consulting',
                'Technology Advisory Services',
                'IT Infrastructure Consulting',
            ]),
            'quantity' => fake()->numberBetween(1, 5),
            'unit_price' => fake()->numberBetween(15000, 75000), // $150 to $750 per hour
            'tax_rate' => 18, // 18% as users would enter
        ]);
    }

    /**
     * Create a development service item
     */
    public function developmentService(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => fake()->randomElement([
                'Custom Web Application Development',
                'Mobile App Development (iOS/Android)',
                'E-commerce Platform Development',
                'CRM System Development',
                'API Development and Integration',
            ]),
            'quantity' => fake()->numberBetween(10, 100),
            'unit_price' => fake()->numberBetween(8000, 25000), // $80 to $250 per hour
            'tax_rate' => 18, // 18% as users would enter
        ]);
    }

    /**
     * Create a design service item
     */
    public function designService(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => fake()->randomElement([
                'UI/UX Design for Web Application',
                'Mobile App UI Design',
                'Brand Identity Design',
                'Website Redesign',
                'User Experience Research',
            ]),
            'quantity' => fake()->numberBetween(5, 20),
            'unit_price' => fake()->numberBetween(6000, 20000), // $60 to $200 per hour
            'tax_rate' => 18, // 18% as users would enter
        ]);
    }

    /**
     * Create a large quantity item
     */
    public function largeQuantity(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => fake()->numberBetween(50, 500),
            'unit_price' => fake()->numberBetween(1000, 5000), // Lower unit price for bulk
        ]);
    }

    /**
     * Create a high-value item
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => fake()->randomElement([
                'Enterprise Software License',
                'Complete System Implementation',
                'Annual Maintenance Contract',
                'Cloud Infrastructure Setup',
            ]),
            'quantity' => 1,
            'unit_price' => fake()->numberBetween(100000, 1000000), // $1,000 to $10,000
            'tax_rate' => 18, // 18% as users would enter
        ]);
    }

    /**
     * Create an item with zero price (free service)
     */
    public function freeService(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => fake()->randomElement([
                'Initial Consultation (Complimentary)',
                'Project Discovery Session',
                'Technical Assessment',
                'Requirement Analysis',
            ]),
            'quantity' => 1,
            'unit_price' => 0,
            'tax_rate' => 0,
        ]);
    }

    /**
     * Create an item with custom specifications
     */
    public function custom(string $description, int $quantity, int $unitPrice, ?float $taxRate = 18): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => $description,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate, // Expected as percentage (will be auto-converted)
        ]);
    }
}
