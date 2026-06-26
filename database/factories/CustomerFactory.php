<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Location;
use App\Models\Organization;
use App\ValueObjects\ContactCollection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
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
            ...(fake()->boolean(80) ? $this->e164Phone() : ['phone' => null, 'phone_country' => null]),
            'emails' => new ContactCollection(array_filter([
                ['name' => fake()->name(), 'email' => fake()->unique()->userName().'@'.fake()->domainName(1).'.test'],
                fake()->randomFloat() < 0.4 ? ['name' => fake()->name(), 'email' => fake()->unique()->userName().'@'.fake()->domainName(1).'.test'] : null,
            ])),
            'organization_id' => Organization::factory(),
            'primary_location_id' => null, // Will be set after location creation
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
        return fake()->randomElement([
            ['phone' => '+14155552671', 'phone_country' => 'US'],
            ['phone' => '+442071838750', 'phone_country' => 'GB'],
            ['phone' => '+919812345678', 'phone_country' => 'IN'],
            ['phone' => '+493012345678', 'phone_country' => 'DE'],
            ['phone' => '+97142345678', 'phone_country' => 'AE'],
            ['phone' => '+61412345678', 'phone_country' => 'AU'],
        ]);
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
            'emails' => new ContactCollection([
                ['name' => fake()->name(), 'email' => fake()->unique()->userName().'@'.fake()->domainName(1).'.test'],
                ['name' => fake()->name(), 'email' => fake()->unique()->userName().'@'.fake()->domainName(1).'.test'],
                ['name' => fake()->name(), 'email' => fake()->unique()->userName().'@'.fake()->domainName(1).'.test'],
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
            'emails' => new ContactCollection([['name' => fake()->name(), 'email' => fake()->unique()->userName().'@'.fake()->domainName(1).'.test']]),
        ]);
    }

    /**
     * Create a customer without phone number
     */
    public function withoutPhone(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => null,
            'phone_country' => null,
        ]);
    }

    /**
     * Create a minimal customer (just name and email)
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => null,
            'phone_country' => null,
            'emails' => new ContactCollection([['name' => fake()->name(), 'email' => fake()->unique()->userName().'@'.fake()->domainName(1).'.test']]),
        ]);
    }

    // =====================================================================
    // INDUSTRY-SPECIFIC CUSTOMER TYPES
    // =====================================================================

    /**
     * Manufacturing industry customer (B2B manufacturing)
     */
    public function manufacturingCustomer(): static
    {
        return $this->state([
            'name' => fake()->randomElement([
                'Detroit Auto Parts Inc',
                'Midwest Industrial Supply',
                'Great Lakes Manufacturing',
                'American Steel Works',
                'Precision Tools Corp',
                'Industrial Components Ltd',
                'Manufacturing Solutions Inc',
                'Heavy Industry Partners',
            ]),
            'emails' => new ContactCollection([
                ['name' => 'Purchasing Department', 'email' => 'purchasing@'.fake()->lexify('????????').'.test'],
                ['name' => 'Accounting Department', 'email' => 'accounting@'.fake()->lexify('????????').'.test'],
            ]),
            'phone' => '+1415'.fake()->numerify('#######'),
            'phone_country' => 'US',
        ]);
    }

    /**
     * Technology customer (Tech startups, software companies)
     */
    public function techCustomer(): static
    {
        return $this->state([
            'name' => fake()->randomElement([
                'CloudFirst Enterprises',
                'Innovate Digital Agency',
                'NextGen Startups Inc',
                'Mobile App Solutions',
                'E-commerce Pioneers',
                'Digital Innovation Labs',
                'Tech Solutions Hub',
                'Software Development Inc',
            ]),
            'emails' => new ContactCollection([
                ['name' => 'Billing Department', 'email' => 'billing@'.fake()->lexify('????????').'.test'],
                ['name' => 'Finance Department', 'email' => 'finance@'.fake()->lexify('????????').'.test'],
            ]),
            'phone' => '+1415'.fake()->numerify('#######'),
            'phone_country' => 'US',
        ]);
    }

    /**
     * Consulting customer (Enterprise consulting clients)
     */
    public function consultingCustomer(): static
    {
        return $this->state([
            'name' => fake()->randomElement([
                'Deutsche Bank AG',
                'BMW Group',
                'Siemens AG',
                'SAP SE',
                'Volkswagen AG',
                'BASF SE',
                'Fortune 500 Corp',
                'Enterprise Solutions Ltd',
                'Investment Bank Partners',
            ]),
            'emails' => new ContactCollection([
                ['name' => 'Procurement', 'email' => 'procurement@'.fake()->lexify('????????').'.test'],
                ['name' => 'Supplier Management', 'email' => 'supplier.management@'.fake()->lexify('????????').'.test'],
            ]),
            'phone' => '+4930'.fake()->numerify('########'),
            'phone_country' => 'DE',
        ]);
    }

    /**
     * Retail customer (Retail chains, commerce)
     */
    public function retailCustomer(): static
    {
        return $this->state([
            'name' => fake()->randomElement([
                'Retail Chain India Pvt Ltd',
                'Mumbai Textiles Exports',
                'Chennai Auto Components',
                'Bangalore Electronics Ltd',
                'Delhi Fashion House',
                'Commercial Trading Corp',
                'Wholesale Distributors',
                'Retail Solutions Ltd',
            ]),
            'emails' => new ContactCollection([
                ['name' => 'Procurement', 'email' => 'procurement@'.fake()->lexify('????????').'.test'],
                ['name' => 'Orders Department', 'email' => 'orders@'.fake()->lexify('????????').'.test'],
            ]),
            'phone' => '+9198'.fake()->numerify('########'),
            'phone_country' => 'IN',
        ]);
    }

    // =====================================================================
    // GEOGRAPHIC CUSTOMER STATES
    // =====================================================================

    /**
     * US-based customer
     */
    public function usCustomer(): static
    {
        return $this->afterCreating(function (Customer $customer) {
            $location = Location::factory()
                ->forCustomer($customer->id)
                ->create([
                    'country' => 'US',
                    'state' => fake()->randomElement(['California', 'New York', 'Texas', 'Florida', 'Illinois']),
                    'city' => fake()->city(),
                    'postal_code' => fake()->postcode(),
                ]);

            $customer->update(['primary_location_id' => $location->id]);
        })->state([
            'phone' => '+1415'.fake()->numerify('#######'),
            'phone_country' => 'US',
        ]);
    }

    /**
     * Indian customer
     */
    public function indianCustomer(): static
    {
        return $this->afterCreating(function (Customer $customer) {
            $location = Location::factory()
                ->forCustomer($customer->id)
                ->create([
                    'country' => 'IN',
                    'state' => fake()->randomElement(['Maharashtra', 'Karnataka', 'Tamil Nadu', 'Delhi', 'Gujarat']),
                    'city' => fake()->randomElement(['Mumbai', 'Bangalore', 'Chennai', 'Delhi', 'Pune']),
                    'postal_code' => fake()->numerify('######'),
                ]);

            $customer->update(['primary_location_id' => $location->id]);
        })->state([
            'phone' => '+9198'.fake()->numerify('########'),
            'phone_country' => 'IN',
        ]);
    }

    /**
     * German customer
     */
    public function germanCustomer(): static
    {
        return $this->afterCreating(function (Customer $customer) {
            $location = Location::factory()
                ->forCustomer($customer->id)
                ->create([
                    'country' => 'DE',
                    'state' => fake()->randomElement(['Bavaria', 'Berlin', 'Hessen', 'Baden-Württemberg']),
                    'city' => fake()->randomElement(['Berlin', 'Munich', 'Frankfurt', 'Hamburg', 'Stuttgart']),
                    'postal_code' => fake()->numerify('#####'),
                ]);

            $customer->update(['primary_location_id' => $location->id]);
        })->state([
            'phone' => '+4930'.fake()->numerify('########'),
            'phone_country' => 'DE',
        ]);
    }

    /**
     * UAE customer (Sandline, Corniche Labs style)
     */
    public function uaeCustomer(): static
    {
        return $this->state([
            'name' => fake()->randomElement([
                'Sandline LLC',
                'Corniche Labs',
                'Emirates Trading Co',
                'Dubai Business Solutions',
                'Abu Dhabi Enterprises',
                'Gulf Commercial Ltd',
            ]),
            'emails' => new ContactCollection(fake()->randomElement([
                [['name' => 'Billing', 'email' => 'billing@sandline.test'], ['name' => 'Finance', 'email' => 'finance@sandline.test']],
                [['name' => 'Priya', 'email' => 'priya@cornichelabs.test'], ['name' => 'Consultant', 'email' => 'consult@cornichelabs.test']],
                [['name' => 'Info', 'email' => 'info@'.fake()->lexify('????????').'.test']],
            ])),
            'phone' => '+9714'.fake()->numerify('#######'),
            'phone_country' => 'AE',
        ])->afterCreating(function (Customer $customer) {
            $location = Location::factory()
                ->forCustomer($customer->id)
                ->create([
                    'name' => $customer->name === 'Sandline LLC' ? 'Sandline Healthcare HQ' : $customer->name,
                    'address_line_1' => fake()->randomElement([
                        'Dubai Healthcare City',
                        'Al Warsan Towers, 305',
                        'Business Bay Tower',
                        'DIFC Gate Village',
                    ]),
                    'address_line_2' => fake()->randomElement([
                        'Building 64, Office 4001',
                        'Barsha Heights',
                        'Suite 1205',
                        null,
                    ]),
                    'city' => 'Dubai',
                    'state' => 'Dubai',
                    'country' => 'AE',
                    'postal_code' => '00000',
                ]);

            $customer->update(['primary_location_id' => $location->id]);
        });
    }

    // =====================================================================
    // BUSINESS RELATIONSHIP STATES
    // =====================================================================

    /**
     * New customer (recently added, minimal history)
     */
    public function newCustomer(): static
    {
        return $this->state([
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Established customer (long relationship, many transactions)
     */
    public function establishedCustomer(): static
    {
        return $this->state([
            'created_at' => fake()->dateTimeBetween('-3 years', '-6 months'),
        ]);
    }

    /**
     * High-value customer (large transaction volumes)
     */
    public function highValueCustomer(): static
    {
        return $this->state([
            'name' => fake()->randomElement([
                'Enterprise Solutions Corp',
                'Global Manufacturing Ltd',
                'Fortune 500 Company',
                'International Conglomerate',
                'Multi-National Corporation',
                'Strategic Partners Inc',
            ]),
            'emails' => new ContactCollection([
                ['name' => 'Procurement', 'email' => 'procurement@'.fake()->lexify('????????').'.test'],
                ['name' => 'Finance', 'email' => 'finance@'.fake()->lexify('????????').'.test'],
                ['name' => 'Legal', 'email' => 'legal@'.fake()->lexify('????????').'.test'],
            ]),
            'created_at' => fake()->dateTimeBetween('-5 years', '-1 year'),
        ]);
    }

    /**
     * International customer (multi-currency transactions)
     */
    public function internationalCustomer(): static
    {
        return $this->state([
            'name' => fake()->randomElement([
                'International Business Corp',
                'Global Trading Partners',
                'Worldwide Commerce Ltd',
                'Cross-Border Solutions',
                'Multi-Currency Enterprise',
                'International Holdings Inc',
            ]),
            'emails' => new ContactCollection([
                ['name' => 'International', 'email' => 'international@'.fake()->lexify('????????').'.test'],
                ['name' => 'Global Billing', 'email' => 'global.billing@'.fake()->lexify('??????').'.test'],
            ]),
        ]);
    }

    // =====================================================================
    // COMBINED CONVENIENCE STATES
    // =====================================================================

    /**
     * US Manufacturing customer with established relationship
     */
    public function usManufacturingEstablished(): static
    {
        return $this->manufacturingCustomer()
            ->usCustomer()
            ->establishedCustomer();
    }

    /**
     * German consulting customer (high value)
     */
    public function germanConsultingHighValue(): static
    {
        return $this->consultingCustomer()
            ->germanCustomer()
            ->highValueCustomer();
    }

    /**
     * Indian retail customer (new relationship)
     */
    public function indianRetailNew(): static
    {
        return $this->retailCustomer()
            ->indianCustomer()
            ->newCustomer();
    }

    /**
     * UAE tech customer (established)
     */
    public function uaeTechEstablished(): static
    {
        return $this->uaeCustomer()
            ->establishedCustomer();
    }
}
