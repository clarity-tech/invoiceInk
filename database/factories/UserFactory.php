<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use App\Support\JetstreamFeatures as Features;
use App\ValueObjects\BankDetails;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->userName().'@'.fake()->domainName(1).'.test',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'remember_token' => Str::random(10),
            'profile_photo_path' => null,
            // Don't set current_team_id here - let it be set during team creation
            // Setting to null here would override proper team assignment
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user should have a personal team.
     */
    public function withPersonalTeam(?callable $callback = null): static
    {
        if (! Features::hasTeamFeatures()) {
            return $this->state([]);
        }

        return $this->has(
            Organization::factory()
                ->personalTeam()
                ->state(fn (array $attributes, User $user) => [
                    'name' => $user->name.'\'s Organization',
                    'user_id' => $user->id,
                ])
                ->when(is_callable($callback), $callback),
            'ownedTeams'
        )
            ->afterCreating(function (User $user) {
                $firstTeam = $user->ownedTeams->first();
                if ($firstTeam) {
                    $user->current_team_id = $firstTeam->id;
                    $user->save();
                }
            });
    }

    /**
     * Create user with a business organization (completed setup)
     */
    public function withBusinessOrganization(?callable $callback = null): static
    {
        if (! Features::hasTeamFeatures()) {
            return $this->state([]);
        }

        return $this->has(
            Organization::factory()
                ->businessOrganization()
                ->completedSetup()
                ->withBankDetails()
                ->withHeadOffice()
                ->state(fn (array $attributes, User $user) => [
                    'name' => $user->name.'\'s Organization',
                    'user_id' => $user->id,
                ])
                ->when(is_callable($callback), $callback),
            'ownedTeams'
        )
            ->afterCreating(function (User $user) {
                $firstTeam = $user->ownedTeams->first();
                if ($firstTeam) {
                    $user->current_team_id = $firstTeam->id;
                    $user->save();
                }
            });
    }

    // =====================================================================
    // USER TYPE STATES
    // =====================================================================

    /**
     * System administrator user
     */
    public function systemAdmin(): static
    {
        return $this->state([
            'name' => 'System Administrator',
            'email' => 'admin@invoicing.claritytech.test',
        ])->has(
            Organization::factory()
                ->personalTeam()
                ->state([
                    'name' => 'Clarity Tech Admin',
                    'company_name' => null,
                    'currency' => 'INR',
                    'phone' => null,
                    'phone_country' => null,
                ])
                ->count(1),
            'ownedTeams'
        );
    }

    /**
     * Business owner with completed setup
     */
    public function businessOwner(): static
    {
        return $this->withBusinessOrganization();
    }

    /**
     * New user needing onboarding
     */
    public function newUser(): static
    {
        return $this->has(
            Organization::factory()
                ->personalTeam()
                ->incompleteSetup()
                ->state(fn (array $attributes, User $user) => [
                    'name' => $user->name.'\'s Organization',
                    'user_id' => $user->id,
                ])
                ->count(1),
            'ownedTeams'
        );
    }

    /**
     * Demo user with rich demo data
     */
    public function demoUser(): static
    {
        return $this->state([
            'name' => 'Demo User',
            'email' => 'demo@invoicing.claritytech.test',
        ])->has(
            Organization::factory()
                ->businessOrganization()
                ->indianCompany()
                ->completedSetup()
                ->withHeadOffice([
                    'address_line_1' => '12 Church Street, Ashok Nagar',
                    'city' => 'Bengaluru',
                    'state' => 'Karnataka',
                    'postal_code' => '560001',
                    'country' => 'IN',
                    'tax_id' => '29AAGCD4821M1Z6',
                ])
                ->state([
                    'name' => 'Demo Company Ltd',
                    'company_name' => 'Demo Company Private Limited',
                    'phone' => '+918041234567',
                    'phone_country' => 'IN',
                    'bank_details' => new BankDetails(
                        accountName: 'Demo Company Private Limited',
                        accountNumber: '50200041237789',
                        bankName: 'HDFC Bank',
                        ifsc: 'HDFC0000523',
                        branch: 'MG Road, Bengaluru',
                        swift: 'HDFCINBB',
                        pan: 'AAGCD4821M',
                    ),
                ])
                ->count(1),
            'ownedTeams'
        );
    }

    // =====================================================================
    // PERSONA STATES (Specific Business Characters)
    // =====================================================================

    /**
     * John Smith - Manufacturing business owner
     */
    public function johnSmithManufacturing(): static
    {
        return $this->state([
            'name' => 'John Smith',
            'email' => 'john@acmecorp.test',
        ])->has(
            Organization::factory()
                ->businessOrganization()
                ->manufacturingCompany()
                ->usCompany()
                ->completedSetup()
                ->withHeadOffice([
                    'address_line_1' => '123 Industrial Ave',
                    'city' => 'Detroit',
                    'state' => 'Michigan',
                    'postal_code' => '48201',
                    'country' => 'US',
                ])
                ->state([
                    'name' => 'ACME Manufacturing Corp',
                    'company_name' => 'ACME Manufacturing Corporation',
                    'tax_number' => 'US-123456789',
                    'registration_number' => 'REG-ACME-2020',
                    'emails' => [['name' => 'Billing', 'email' => 'billing@acmecorp.test'], ['name' => 'John Smith', 'email' => 'john@acmecorp.test']],
                    'phone' => '+14155550123',
                    'phone_country' => 'US',
                    'website' => 'https://acmecorp.test',
                    'custom_domain' => 'invoicing.acmecorp.test',
                    'bank_details' => new BankDetails(
                        accountName: 'ACME Manufacturing Corporation',
                        accountNumber: '1852330741',
                        bankName: 'Comerica Bank',
                        branch: 'Detroit, Michigan',
                        swift: 'MNBDUS33',
                    ),
                ])
                ->count(1),
            'ownedTeams'
        );
    }

    /**
     * Sarah Johnson - Tech startup founder
     */
    public function sarahTechStartup(): static
    {
        return $this->state([
            'name' => 'Sarah Johnson',
            'email' => 'sarah@techstartup.test',
        ])->has(
            Organization::factory()
                ->businessOrganization()
                ->techStartup()
                ->usCompany()
                ->completedSetup()
                ->withHeadOffice([
                    'address_line_1' => '456 Innovation Blvd',
                    'city' => 'San Francisco',
                    'state' => 'California',
                    'postal_code' => '94105',
                    'country' => 'US',
                ])
                ->state([
                    'name' => 'TechStart Innovation Hub',
                    'company_name' => 'TechStart Inc.',
                    'tax_number' => 'US-987654321',
                    'registration_number' => 'REG-TECH-2021',
                    'emails' => [['name' => 'Hello', 'email' => 'hello@techstartup.test'], ['name' => 'Sarah Johnson', 'email' => 'sarah@techstartup.test']],
                    'phone' => '+14155550456',
                    'phone_country' => 'US',
                    'website' => 'https://techstartup.test',
                    'custom_domain' => 'billing.techstartup.test',
                    'bank_details' => new BankDetails(
                        accountName: 'TechStart Inc.',
                        accountNumber: '7230415896',
                        bankName: 'Wells Fargo Bank',
                        branch: 'San Francisco, California',
                        swift: 'WFBIUS6S',
                    ),
                ])
                ->count(1),
            'ownedTeams'
        );
    }

    /**
     * Maria Schmidt - European consultant
     */
    public function mariaEuroConsult(): static
    {
        return $this->state([
            'name' => 'Maria Schmidt',
            'email' => 'maria@euroconsult.test',
        ])->has(
            Organization::factory()
                ->businessOrganization()
                ->consultingFirm()
                ->germanCompany()
                ->completedSetup()
                ->withHeadOffice([
                    'address_line_1' => 'Unter den Linden 1',
                    'city' => 'Berlin',
                    'state' => 'Berlin',
                    'postal_code' => '10117',
                    'country' => 'DE',
                ])
                ->state([
                    'name' => 'EuroConsult GmbH',
                    'company_name' => 'EuroConsult GmbH',
                    'tax_number' => 'DE-123456789',
                    'registration_number' => 'HRB-12345',
                    'emails' => [['name' => 'Info', 'email' => 'info@euroconsult.test'], ['name' => 'Maria Schmidt', 'email' => 'maria@euroconsult.test']],
                    'phone' => '+493012345678',
                    'phone_country' => 'DE',
                    'website' => 'https://euroconsult.test',
                    'bank_details' => new BankDetails(
                        accountName: 'EuroConsult GmbH',
                        accountNumber: 'DE89 3704 0044 0532 0130 00',
                        bankName: 'Commerzbank',
                        branch: 'Berlin',
                        swift: 'COBADEFFXXX',
                    ),
                ])
                ->count(1),
            'ownedTeams'
        );
    }

    /**
     * Ahmed Al-Mahmoud - Dubai trader
     */
    public function ahmedDubaiTrader(): static
    {
        return $this->state([
            'name' => 'Ahmed Al-Mahmoud',
            'email' => 'ahmed@dubaitrading.test',
        ])->has(
            Organization::factory()
                ->businessOrganization()
                ->tradingCompany()
                ->uaeCompany()
                ->completedSetup()
                ->withHeadOffice([
                    'address_line_1' => 'Office 1205, Business Bay Tower',
                    'city' => 'Dubai',
                    'state' => 'Dubai',
                    'postal_code' => '00000',
                    'country' => 'AE',
                ])
                ->state([
                    'name' => 'Dubai Trading LLC',
                    'company_name' => 'Dubai Trading Limited Liability Company',
                    'tax_number' => 'AE-100234567890003',
                    'registration_number' => 'CN-1234567',
                    'emails' => [['name' => 'Info', 'email' => 'info@dubaitrading.test'], ['name' => 'Ahmed Al Rashid', 'email' => 'ahmed@dubaitrading.test']],
                    'phone' => '+97141234567',
                    'phone_country' => 'AE',
                    'website' => 'https://dubaitrading.test',
                    'bank_details' => new BankDetails(
                        accountName: 'Dubai Trading LLC',
                        accountNumber: 'AE07 0331 2345 6789 0123 456',
                        bankName: 'Emirates NBD',
                        branch: 'Business Bay, Dubai',
                        swift: 'EBILAEAD',
                    ),
                ])
                ->count(1),
            'ownedTeams'
        );
    }

    /**
     * Robert Global - Multi-business owner
     */
    public function robertGlobalCorp(): static
    {
        return $this->state([
            'name' => 'Robert Global',
            'email' => 'robert@globalcorp.test',
        ])->has(
            Organization::factory()
                ->personalTeam()
                ->state([
                    'name' => 'Robert Global\'s Team',
                    'company_name' => null,
                    'currency' => 'INR',
                    'phone' => null,
                    'phone_country' => null,
                ])
                ->count(1),
            'ownedTeams'
        )->has(
            Organization::factory()
                ->businessOrganization()
                ->indianCompany()
                ->completedSetup()
                ->withHeadOffice([
                    'address_line_1' => '7th Floor, Maker Chambers IV, Nariman Point',
                    'city' => 'Mumbai',
                    'state' => 'Maharashtra',
                    'postal_code' => '400021',
                    'country' => 'IN',
                    'tax_id' => '27AABCG7310K1ZQ',
                ])
                ->state([
                    'name' => 'GlobalCorp Holdings',
                    'company_name' => 'GlobalCorp Holdings Inc',
                    'currency' => 'INR',
                    'custom_domain' => 'invoicing.globalcorp.test',
                    'phone' => '+912266554433',
                    'phone_country' => 'IN',
                    'bank_details' => new BankDetails(
                        accountName: 'GlobalCorp Holdings Inc',
                        accountNumber: '000405112233',
                        bankName: 'ICICI Bank',
                        ifsc: 'ICIC0000004',
                        branch: 'Nariman Point, Mumbai',
                        swift: 'ICICINBB',
                        pan: 'AABCG7310K',
                    ),
                ])
                ->count(1),
            'ownedTeams'
        )->has(
            Organization::factory()
                ->businessOrganization()
                ->indianCompany()
                ->completedSetup()
                ->withHeadOffice([
                    'address_line_1' => 'Plot 17, HITEC City, Madhapur',
                    'city' => 'Hyderabad',
                    'state' => 'Telangana',
                    'postal_code' => '500081',
                    'country' => 'IN',
                    'tax_id' => '36AADCG5592P1Z3',
                ])
                ->state([
                    'name' => 'GlobalCorp Tech Solutions',
                    'company_name' => 'GlobalCorp Tech Solutions Ltd',
                    'currency' => 'INR',
                    'phone' => '+914040123456',
                    'phone_country' => 'IN',
                    'bank_details' => new BankDetails(
                        accountName: 'GlobalCorp Tech Solutions Ltd',
                        accountNumber: '918020045566771',
                        bankName: 'Axis Bank',
                        ifsc: 'UTIB0000553',
                        branch: 'HITEC City, Hyderabad',
                        swift: 'AXISINBB',
                        pan: 'AADCG5592P',
                    ),
                ])
                ->count(1),
            'ownedTeams'
        );
    }

    // =====================================================================
    // TEAM RELATIONSHIP STATES
    // =====================================================================

    /**
     * User with multiple organizations
     */
    public function withMultipleOrganizations(int $count = 3): static
    {
        return $this->has(
            Organization::factory()
                ->businessOrganization()
                ->completedSetup()
                ->count($count),
            'ownedTeams'
        );
    }

    /**
     * User who is a member of other teams (but doesn't own them)
     */
    public function withTeamMemberships(): static
    {
        // This would need to be handled after user creation
        // by attaching to existing teams, not in factory definition
        return $this;
    }
}
