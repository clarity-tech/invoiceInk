<?php

namespace Database\Factories;

use App\Enums\ResetFrequency;
use App\Models\InvoiceNumberingSeries;
use App\Models\Location;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceNumberingSeries>
 */
class InvoiceNumberingSeriesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'location_id' => null, // Default to organization-wide
            'name' => fake()->words(2, true) . ' Series',
            'prefix' => fake()->randomElement(['INV', 'BILL', 'DOC']),
            'format_pattern' => '{PREFIX}-{YEAR}-{MONTH}-{SEQUENCE:4}',
            'current_number' => fake()->numberBetween(0, 100),
            'reset_frequency' => fake()->randomElement(ResetFrequency::cases()),
            'is_active' => true,
            'is_default' => false,
            'last_reset_at' => fake()->optional(0.7)->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Create a default series for an organization.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Default Invoice Series',
            'prefix' => 'INV',
            'is_default' => true,
        ]);
    }

    /**
     * Create a location-specific series.
     */
    public function forLocation(Location $location): static
    {
        return $this->state(fn (array $attributes) => [
            'location_id' => $location->id,
            'name' => $location->name . ' Series',
            'prefix' => 'INV-' . strtoupper(substr($location->name, 0, 3)),
        ]);
    }

    /**
     * Create an inactive series.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a series with specific reset frequency.
     */
    public function resetFrequency(ResetFrequency $frequency): static
    {
        return $this->state(fn (array $attributes) => [
            'reset_frequency' => $frequency,
        ]);
    }

    /**
     * Create a series with specific format pattern.
     */
    public function withFormat(string $pattern): static
    {
        return $this->state(fn (array $attributes) => [
            'format_pattern' => $pattern,
        ]);
    }

    /**
     * Create a series with specific current number.
     */
    public function withCurrentNumber(int $number): static
    {
        return $this->state(fn (array $attributes) => [
            'current_number' => $number,
        ]);
    }
}