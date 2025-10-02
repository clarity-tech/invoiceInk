<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\TaxTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaxTemplate>
 */
class TaxTemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TaxTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $taxTypes = ['GST', 'VAT', 'CGST', 'SGST', 'IGST', 'Service Tax', 'Excise Tax'];
        $categories = ['standard', 'reduced', 'zero', 'exempt', 'luxury'];
        $countryCodes = ['IN', 'US', 'GB', 'AE', 'DE', 'FR'];

        return [
            'organization_id' => Organization::factory(),
            'name' => $this->faker->randomElement([
                'GST 18%',
                'VAT 5%',
                'Service Tax 15%',
                'CGST 9%',
                'SGST 9%',
                'IGST 18%',
                'Excise Tax 10%',
            ]),
            'type' => $this->faker->randomElement($taxTypes),
            'rate' => $this->faker->randomFloat(3, 0, 30), // 0% to 30% with 3 decimal places
            'category' => $this->faker->randomElement($categories),
            'country_code' => $this->faker->randomElement($countryCodes),
            'description' => $this->faker->optional(0.7)->sentence(),
            'is_active' => $this->faker->boolean(85), // 85% chance of being active
            'metadata' => $this->faker->optional(0.3)->randomElement([
                ['application' => 'goods'],
                ['application' => 'services'],
                ['threshold' => 250000],
                ['exemption_limit' => 40000],
            ]),
        ];
    }

    /**
     * Create an active tax template.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create an inactive tax template.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a GST tax template.
     */
    public function gst(float $rate = 18.000): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "GST {$rate}%",
            'type' => 'GST',
            'rate' => $rate,
            'country_code' => 'IN',
            'category' => 'standard',
        ]);
    }

    /**
     * Create a VAT tax template.
     */
    public function vat(float $rate = 5.000): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "VAT {$rate}%",
            'type' => 'VAT',
            'rate' => $rate,
            'country_code' => 'AE',
            'category' => 'standard',
        ]);
    }

    /**
     * Create a CGST tax template.
     */
    public function cgst(float $rate = 9.000): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "CGST {$rate}%",
            'type' => 'CGST',
            'rate' => $rate,
            'country_code' => 'IN',
            'category' => 'standard',
        ]);
    }

    /**
     * Create a SGST tax template.
     */
    public function sgst(float $rate = 9.000): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "SGST {$rate}%",
            'type' => 'SGST',
            'rate' => $rate,
            'country_code' => 'IN',
            'category' => 'standard',
        ]);
    }

    /**
     * Create an IGST tax template.
     */
    public function igst(float $rate = 18.000): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "IGST {$rate}%",
            'type' => 'IGST',
            'rate' => $rate,
            'country_code' => 'IN',
            'category' => 'standard',
        ]);
    }

    /**
     * Create a tax template for a specific country.
     */
    public function forCountry(string $countryCode): static
    {
        return $this->state(fn (array $attributes) => [
            'country_code' => $countryCode,
        ]);
    }

    /**
     * Create a tax template with specific metadata.
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => $metadata,
        ]);
    }
}
