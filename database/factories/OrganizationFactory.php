<?php

namespace Database\Factories;

use App\Enums\Country;
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
            'currency' => 'INR',
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Create an organization with financial year setup.
     */
    public function withFinancialYear(?Country $country = null): static
    {
        $country = $country ?? $this->faker->randomElement(Country::cases());
        $currency = $country->getDefaultCurrency();
        $financialYearType = $country->getDefaultFinancialYearType();

        return $this->state([
            'country_code' => $country,
            'financial_year_type' => $financialYearType,
            'financial_year_start_month' => $financialYearType->getStartMonth(),
            'financial_year_start_day' => $financialYearType->getStartDay(),
            'currency' => $currency,
        ]);
    }

    /**
     * Create an organization with a primary location.
     */
    public function withLocation(array $locationAttributes = []): static
    {
        return $this->afterCreating(function ($organization) use ($locationAttributes) {
            $defaultLocationAttributes = [
                'name' => 'Head Office',
                'address_line_1' => $this->faker->streetAddress,
                'city' => $this->faker->city,
                'state' => $this->faker->state,
                'country' => 'IN',
                'postal_code' => $this->faker->postcode,
                'locatable_type' => get_class($organization),
                'locatable_id' => $organization->id,
            ];

            $location = \App\Models\Location::create(array_merge($defaultLocationAttributes, $locationAttributes));

            $organization->update(['primary_location_id' => $location->id]);

            return $organization->fresh(['primaryLocation']);
        });
    }
}
