<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
            'current_team_id' => null,
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
        );
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
                ->withHeadOffice()
                ->state(fn (array $attributes, User $user) => [
                    'name' => $user->name.'\'s Organization',
                    'user_id' => $user->id,
                ])
                ->when(is_callable($callback), $callback),
            'ownedTeams'
        );
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
                    'currency' => 'INR',
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
                ->withHeadOffice()
                ->state([
                    'name' => 'Demo Company Ltd',
                    'company_name' => 'Demo Company Private Limited',
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
            'email' => 'john@acmecorp.com',
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
                    'emails' => ['billing@acmecorp.com', 'john@acmecorp.com'],
                    'phone' => '+1-555-0123',
                    'website' => 'https://acmecorp.com',
                    'custom_domain' => 'invoicing.acmecorp.com',
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
            'email' => 'sarah@techstartup.com',
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
                    'emails' => ['hello@techstartup.com', 'sarah@techstartup.com'],
                    'phone' => '+1-555-0456',
                    'website' => 'https://techstartup.com',
                    'custom_domain' => 'billing.techstartup.com',
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
            'email' => 'maria@euroconsult.de',
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
                    'emails' => ['info@euroconsult.de', 'maria@euroconsult.de'],
                    'phone' => '+49-30-12345678',
                    'website' => 'https://euroconsult.de',
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
            'email' => 'ahmed@dubaitrading.ae',
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
                    'emails' => ['info@dubaitrading.ae', 'ahmed@dubaitrading.ae'],
                    'phone' => '+971-4-1234567',
                    'website' => 'https://dubaitrading.ae',
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
            'email' => 'robert@globalcorp.com',
        ])->has(
            Organization::factory()
                ->personalTeam()
                ->state([
                    'name' => 'Robert Global\'s Team',
                    'currency' => 'INR',
                ])
                ->count(1),
            'ownedTeams'
        )->has(
            Organization::factory()
                ->businessOrganization()
                ->indianCompany()
                ->completedSetup()
                ->withHeadOffice()
                ->state([
                    'name' => 'GlobalCorp Holdings',
                    'company_name' => 'GlobalCorp Holdings Inc',
                    'currency' => 'INR',
                    'custom_domain' => 'invoicing.globalcorp.com',
                ])
                ->count(1),
            'ownedTeams'
        )->has(
            Organization::factory()
                ->businessOrganization()
                ->indianCompany()
                ->completedSetup()
                ->withHeadOffice()
                ->state([
                    'name' => 'GlobalCorp Tech Solutions',
                    'company_name' => 'GlobalCorp Tech Solutions Ltd',
                    'currency' => 'INR',
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
