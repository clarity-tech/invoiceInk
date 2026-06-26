<?php

namespace Database\Factories;

use App\Enums\Country;
use App\Models\Location;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
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
            'company_name' => $this->faker->company(),
            'tax_number' => $this->faker->regexify('[A-Z]{2}-[0-9]{9}'),
            'registration_number' => $this->faker->regexify('REG-[A-Z]{4}-[0-9]{4}'),
            'emails' => [$this->faker->companyEmail()],
            ...$this->e164Phone(),
            'website' => 'https://www.'.$this->faker->domainName(1).'.test',
            'currency' => 'INR',
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * A valid international phone number paired with its ISO 3166-1 alpha-2
     * country, matching how the app stores phones (E.164 + picked country).
     *
     * @return array{phone: string, phone_country: string}
     */
    protected function e164Phone(): array
    {
        return $this->faker->randomElement([
            ['phone' => '+14155552671', 'phone_country' => 'US'],
            ['phone' => '+442071838750', 'phone_country' => 'GB'],
            ['phone' => '+919812345678', 'phone_country' => 'IN'],
            ['phone' => '+493012345678', 'phone_country' => 'DE'],
            ['phone' => '+97142345678', 'phone_country' => 'AE'],
            ['phone' => '+61412345678', 'phone_country' => 'AU'],
        ]);
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

            $location = Location::create(array_merge($defaultLocationAttributes, $locationAttributes));

            $organization->update(['primary_location_id' => $location->id]);

            return $organization->fresh(['primaryLocation']);
        });
    }

    // =====================================================================
    // SETUP STATE METHODS
    // =====================================================================

    /**
     * Create organization with incomplete setup (needs onboarding)
     */
    public function incompleteSetup(): static
    {
        return $this->state([
            'setup_completed_at' => null,
            'company_name' => null,
            'tax_number' => null,
            'registration_number' => null,
            'website' => null,
            'notes' => null,
            'phone' => null,
            'phone_country' => null,
            'emails' => [],
        ]);
    }

    /**
     * Create organization with completed setup
     */
    public function completedSetup(): static
    {
        return $this->state([
            'setup_completed_at' => now(),
            'company_name' => $this->faker->company(),
            'tax_number' => $this->faker->regexify('[A-Z]{2}-[0-9]{9}'),
            'registration_number' => $this->faker->regexify('REG-[A-Z]{4}-[0-9]{4}'),
            'website' => 'https://www.'.$this->faker->domainName(1).'.test',
            ...$this->e164Phone(),
            'emails' => [$this->faker->companyEmail()],
            'notes' => $this->faker->optional()->sentence(),
        ]);
    }

    /**
     * Create organization with setup in progress (partially filled)
     */
    public function setupInProgress(): static
    {
        return $this->state([
            'setup_completed_at' => null,
            'company_name' => $this->faker->company(),
            'tax_number' => $this->faker->optional(0.7)->regexify('[A-Z]{2}-[0-9]{9}'),
            'registration_number' => $this->faker->optional(0.5)->regexify('REG-[A-Z]{4}-[0-9]{4}'),
            'website' => $this->faker->optional(0.6)->url(),
            ...($this->faker->boolean(80) ? $this->e164Phone() : ['phone' => null, 'phone_country' => null]),
            'emails' => $this->faker->optional(0.9)->randomElements([$this->faker->companyEmail()]),
        ]);
    }

    /**
     * Legacy alias kept to limit test churn. Defaults to a setup-complete org
     * so existing tests that called withPersonalTeam() and expected the old
     * "personal team bypasses setup middleware" behavior keep passing.
     */
    public function personalTeam(): static
    {
        return $this->state([
            'setup_completed_at' => now(),
        ]);
    }

    /**
     * Create business organization with completed setup.
     */
    public function businessOrganization(): static
    {
        return $this->state([
            'setup_completed_at' => now(),
            'company_name' => $this->faker->company(),
        ]);
    }

    // =====================================================================
    // BUSINESS TYPE STATES
    // =====================================================================

    /**
     * Manufacturing company setup (ACME Manufacturing Corp style)
     */
    public function manufacturingCompany(): static
    {
        return $this->state([
            'company_name' => $this->faker->randomElement([
                'ACME Manufacturing Corporation',
                'Industrial Solutions Ltd',
                'Precision Manufacturing Inc',
                'Advanced Manufacturing Systems',
            ]),
            'tax_number' => 'US-'.fake()->numerify('#########'),
            'registration_number' => 'REG-MFG-'.fake()->numerify('####'),
            'website' => 'https://'.strtolower(str_replace(' ', '', $this->faker->company())).'.test',
            'notes' => 'Leading manufacturing company specializing in industrial solutions and precision engineering.',
        ]);
    }

    /**
     * Tech startup setup (TechStart Innovation Hub style)
     */
    public function techStartup(): static
    {
        return $this->state([
            'company_name' => $this->faker->randomElement([
                'TechStart Innovation Inc',
                'Digital Solutions Hub',
                'NextGen Technologies',
                'Innovation Labs LLC',
            ]),
            'tax_number' => 'US-'.fake()->numerify('#########'),
            'registration_number' => 'REG-TECH-'.fake()->numerify('####'),
            'website' => 'https://'.strtolower(str_replace(' ', '', $this->faker->company())).'.test',
            'notes' => 'Innovative technology startup focused on digital transformation and cutting-edge solutions.',
        ]);
    }

    /**
     * Consulting firm setup (EuroConsult GmbH style)
     */
    public function consultingFirm(): static
    {
        return $this->state([
            'company_name' => $this->faker->randomElement([
                'Professional Consulting GmbH',
                'Business Advisory Services',
                'Strategic Consulting Partners',
                'Expert Solutions Group',
            ]),
            'tax_number' => 'DE-'.fake()->numerify('#########'),
            'registration_number' => 'HRB-'.fake()->numerify('#####'),
            'website' => 'https://'.strtolower(str_replace(' ', '', $this->faker->company())).'.test',
            'notes' => 'Professional consulting firm providing strategic business advisory and management consulting services.',
        ]);
    }

    /**
     * Trading company setup (Dubai Trading LLC style)
     */
    public function tradingCompany(): static
    {
        return $this->state([
            'company_name' => $this->faker->randomElement([
                'International Trading LLC',
                'Global Commerce Solutions',
                'Trade Partners Limited',
                'Commercial Enterprise LLC',
            ]),
            'tax_number' => 'AE-'.fake()->numerify('###############'),
            'registration_number' => 'CN-'.fake()->numerify('#######'),
            'website' => 'https://'.strtolower(str_replace(' ', '', $this->faker->company())).'.test',
            'notes' => 'International trading company specializing in import/export and global commerce solutions.',
        ]);
    }

    // =====================================================================
    // COUNTRY-SPECIFIC SETUPS
    // =====================================================================

    /**
     * US company configuration
     */
    public function usCompany(): static
    {
        return $this->withFinancialYear(Country::US)
            ->state([
                'currency' => 'USD',
                'tax_number' => 'US-'.fake()->numerify('#########'),
                'registration_number' => 'REG-'.fake()->lexify('????').'-'.fake()->numerify('####'),
            ]);
    }

    /**
     * Indian company configuration with proper GST format and business naming
     */
    public function indianCompany(): static
    {
        return $this->withFinancialYear(Country::IN)
            ->state([
                'currency' => 'INR',
                'company_name' => $this->faker->optional(0.8)->randomElement([
                    'Demo Company Private Limited',
                    'Business Solutions Pvt Ltd',
                    'Indian Enterprises Ltd',
                    'Commercial Services Pvt Ltd',
                    'Tech Solutions India Pvt Ltd',
                    'Manufacturing India Limited',
                ]),
                'tax_number' => 'IN-'.fake()->regexify('[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}'),
                'registration_number' => 'U74999DL'.fake()->numerify('####').'PTC'.fake()->numerify('######'),
                'website' => $this->faker->optional(0.7)->url(),
                'notes' => $this->faker->optional(0.6)->randomElement([
                    'Indian private limited company providing comprehensive business solutions.',
                    'Leading Indian enterprise focused on technology and innovation.',
                    'Established Indian company serving domestic and international markets.',
                ]),
            ]);
    }

    /**
     * German company configuration
     */
    public function germanCompany(): static
    {
        return $this->withFinancialYear(Country::DE)
            ->state([
                'currency' => 'EUR',
                'tax_number' => 'DE-'.fake()->numerify('#########'),
                'registration_number' => 'HRB-'.fake()->numerify('#####'),
            ]);
    }

    /**
     * UAE company configuration
     */
    public function uaeCompany(): static
    {
        return $this->withFinancialYear(Country::AE)
            ->state([
                'currency' => 'AED',
                'tax_number' => 'AE-'.fake()->numerify('###############'),
                'registration_number' => 'CN-'.fake()->numerify('#######'),
            ]);
    }

    // =====================================================================
    // LOCATION HELPER METHODS
    // =====================================================================

    /**
     * Create organization with head office location
     */
    public function withHeadOffice(array $locationOverrides = []): static
    {
        return $this->afterCreating(function ($organization) use ($locationOverrides) {
            $defaultAttributes = [
                'name' => 'Head Office',
                'address_line_1' => $this->faker->streetAddress,
                'city' => $this->faker->city,
                'state' => $this->faker->state,
                'country' => $organization->country_code?->value ?? 'IN',
                'postal_code' => $this->faker->postcode,
            ];

            $location = Location::create([
                ...$defaultAttributes,
                ...$locationOverrides,
                'locatable_type' => get_class($organization),
                'locatable_id' => $organization->id,
            ]);

            $organization->update(['primary_location_id' => $location->id]);
        });
    }

    /**
     * Create organization with multiple office locations
     */
    public function withMultipleLocations(int $count = 3): static
    {
        return $this->afterCreating(function ($organization) use ($count) {
            $locations = collect();

            for ($i = 0; $i < $count; $i++) {
                $location = Location::create([
                    'name' => $i === 0 ? 'Head Office' : "Branch Office {$i}",
                    'address_line_1' => $this->faker->streetAddress,
                    'city' => $this->faker->city,
                    'state' => $this->faker->state,
                    'country' => $organization->country_code?->value ?? 'IN',
                    'postal_code' => $this->faker->postcode,
                    'locatable_type' => get_class($organization),
                    'locatable_id' => $organization->id,
                ]);

                $locations->push($location);
            }

            // Set first location as primary
            $organization->update(['primary_location_id' => $locations->first()->id]);
        });
    }
}
