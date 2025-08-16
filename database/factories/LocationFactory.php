<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Office',
            'gstin' => fake()->optional(0.7)->bothify('##???#####?#?#'),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->optional(0.3)->secondaryAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => fake()->country(),
            'postal_code' => fake()->postcode(),
            'locatable_type' => \App\Models\Company::class, // Default to Company
            'locatable_id' => 1, // Default value, will be overridden by relationships
        ];
    }

    /**
     * Create a location for a company
     */
    public function forCompany($companyId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'locatable_type' => \App\Models\Company::class,
            'locatable_id' => $companyId,
            'name' => fake()->company().' Headquarters',
        ]);
    }

    /**
     * Create a location for a customer
     */
    public function forCustomer($customerId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'locatable_type' => \App\Models\Customer::class,
            'locatable_id' => $customerId,
            'name' => fake()->company().' Office',
        ]);
    }

    /**
     * Create a location with GST number (for Indian businesses)
     */
    public function withGstin(): static
    {
        return $this->state(fn (array $attributes) => [
            'gstin' => fake()->bothify('##???#####?#?#'),
            'country' => 'India',
            'state' => fake()->randomElement([
                'Maharashtra', 'Delhi', 'Karnataka', 'Tamil Nadu',
                'Gujarat', 'Rajasthan', 'West Bengal', 'Uttar Pradesh',
            ]),
        ]);
    }

    /**
     * Create a location without GST number
     */
    public function withoutGstin(): static
    {
        return $this->state(fn (array $attributes) => [
            'gstin' => null,
        ]);
    }
}
