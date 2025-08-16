<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Location;
use App\ValueObjects\EmailCollection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                fake()->company(),
                fake()->name().' Enterprises',
                fake()->lastName().' & Co.',
                fake()->firstName().' Solutions',
            ]),
            'phone' => fake()->optional(0.8)->phoneNumber(),
            'emails' => new EmailCollection(array_filter([
                fake()->unique()->safeEmail(),
                fake()->randomFloat() < 0.4 ? fake()->unique()->safeEmail() : null,
            ])),
            'primary_location_id' => null, // Will be set after location creation
        ];
    }

    /**
     * Create a customer with a primary location
     */
    public function withLocation(): static
    {
        return $this->afterCreating(function (Customer $customer) {
            $location = Location::factory()
                ->forCustomer($customer->id)
                ->create();

            $customer->update(['primary_location_id' => $location->id]);
        });
    }

    /**
     * Create a customer with multiple locations
     */
    public function withMultipleLocations(int $count = 2): static
    {
        return $this->afterCreating(function (Customer $customer) use ($count) {
            $locations = Location::factory()
                ->count($count)
                ->forCustomer($customer->id)
                ->create();

            // Set first location as primary
            $customer->update(['primary_location_id' => $locations->first()->id]);
        });
    }

    /**
     * Create a customer with GST-enabled location
     */
    public function withGstLocation(): static
    {
        return $this->afterCreating(function (Customer $customer) {
            $location = Location::factory()
                ->forCustomer($customer->id)
                ->withGstin()
                ->create();

            $customer->update(['primary_location_id' => $location->id]);
        });
    }

    /**
     * Create a customer with multiple emails
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
     * Create an individual customer (person rather than company)
     */
    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->name(),
            'emails' => new EmailCollection([fake()->unique()->safeEmail()]),
        ]);
    }

    /**
     * Create a customer without phone number
     */
    public function withoutPhone(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => null,
        ]);
    }

    /**
     * Create a minimal customer (just name and email)
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => null,
            'emails' => new EmailCollection([fake()->unique()->safeEmail()]),
        ]);
    }
}
