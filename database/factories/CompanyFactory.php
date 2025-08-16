<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Location;
use App\ValueObjects\EmailCollection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'phone' => fake()->optional(0.8)->phoneNumber(),
            'emails' => new EmailCollection(array_filter([
                fake()->unique()->safeEmail(),
                fake()->randomFloat() < 0.3 ? fake()->unique()->safeEmail() : null,
            ])),
            'primary_location_id' => null, // Will be set after location creation
        ];
    }

    /**
     * Create a company with a primary location
     */
    public function withLocation(): static
    {
        return $this->afterCreating(function (Company $company) {
            $location = Location::factory()
                ->forCompany($company->id)
                ->withGstin()
                ->create();

            $company->update(['primary_location_id' => $location->id]);
        });
    }

    /**
     * Create a company with multiple locations
     */
    public function withMultipleLocations(int $count = 3): static
    {
        return $this->afterCreating(function (Company $company) use ($count) {
            $locations = Location::factory()
                ->count($count)
                ->forCompany($company->id)
                ->create();

            // Set first location as primary
            $company->update(['primary_location_id' => $locations->first()->id]);
        });
    }

    /**
     * Create a company with multiple emails
     */
    public function withMultipleEmails(): static
    {
        return $this->state(fn (array $attributes) => [
            'emails' => new EmailCollection([
                fake()->unique()->safeEmail(),
                fake()->unique()->safeEmail(),
                fake()->unique()->safeEmail(),
            ]),
        ]);
    }

    /**
     * Create a company without phone number
     */
    public function withoutPhone(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => null,
        ]);
    }

    /**
     * Create a minimal company (just name and email)
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => null,
            'emails' => new EmailCollection([fake()->unique()->safeEmail()]),
        ]);
    }
}
