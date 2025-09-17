<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'user_id' => User::factory(),
            'personal_team' => true,
            'company_name' => $this->faker->company(),
            'tax_number' => $this->faker->regexify('[A-Z]{2}-[0-9]{9}'),
            'registration_number' => $this->faker->regexify('REG-[A-Z]{4}-[0-9]{4}'),
            'emails' => [$this->faker->companyEmail()],
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->url(),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP', 'INR']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
