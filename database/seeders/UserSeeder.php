<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Organization;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends ProductionSafeSeeder
{
    protected function seed(): void
    {
        $this->info('Seeding users and teams...');

        // Create primary admin user
        $admin = $this->createAdminUser();

        // Create business users with their organizations
        $johnUser = $this->createBusinessUser(
            name: 'John Smith',
            email: 'john@acmecorp.com',
            orgData: [
                'name' => 'ACME Manufacturing Corp',
                'company_name' => 'ACME Manufacturing Corporation',
                'tax_number' => 'US-123456789',
                'registration_number' => 'REG-ACME-2020',
                'emails' => ['billing@acmecorp.com', 'john@acmecorp.com'],
                'phone' => '+1-555-0123',
                'website' => 'https://acmecorp.com',
                'currency' => 'USD',
                'custom_domain' => 'invoicing.acmecorp.com',
                'location' => [
                    'street' => '123 Industrial Ave',
                    'city' => 'Detroit',
                    'state' => 'Michigan',
                    'postal_code' => '48201',
                    'country' => 'US',
                ],
            ]
        );

        $sarahUser = $this->createBusinessUser(
            name: 'Sarah Johnson',
            email: 'sarah@techstartup.com',
            orgData: [
                'name' => 'TechStart Innovation Hub',
                'company_name' => 'TechStart Inc.',
                'tax_number' => 'US-987654321',
                'registration_number' => 'REG-TECH-2021',
                'emails' => ['hello@techstartup.com', 'sarah@techstartup.com'],
                'phone' => '+1-555-0456',
                'website' => 'https://techstartup.com',
                'currency' => 'USD',
                'custom_domain' => 'billing.techstartup.com',
                'location' => [
                    'street' => '456 Innovation Blvd',
                    'city' => 'San Francisco',
                    'state' => 'California',
                    'postal_code' => '94105',
                    'country' => 'US',
                ],
            ]
        );

        $mariaUser = $this->createBusinessUser(
            name: 'Maria Schmidt',
            email: 'maria@euroconsult.de',
            orgData: [
                'name' => 'EuroConsult GmbH',
                'company_name' => 'EuroConsult GmbH',
                'tax_number' => 'DE-123456789',
                'registration_number' => 'HRB-12345',
                'emails' => ['info@euroconsult.de', 'maria@euroconsult.de'],
                'phone' => '+49-30-12345678',
                'website' => 'https://euroconsult.de',
                'currency' => 'EUR',
                'custom_domain' => null,
                'location' => [
                    'street' => 'Unter den Linden 1',
                    'city' => 'Berlin',
                    'state' => 'Berlin',
                    'postal_code' => '10117',
                    'country' => 'DE',
                ],
            ]
        );

        $demoUser = $this->createBusinessUser(
            name: 'Demo User',
            email: 'demo@invoicing.claritytech.io',
            orgData: [
                'name' => 'Demo Company Ltd',
                'company_name' => 'Demo Company Private Limited',
                'tax_number' => 'IN-27AABCU9603R1ZX',
                'registration_number' => 'U74999DL2018PTC331234',
                'emails' => ['demo@invoicing.claritytech.io', 'accounts@democompany.in'],
                'phone' => '+91-11-12345678',
                'website' => 'https://democompany.in',
                'currency' => 'INR',
                'custom_domain' => null,
                'location' => [
                    'street' => 'A-123, Connaught Place',
                    'city' => 'New Delhi',
                    'state' => 'Delhi',
                    'postal_code' => '110001',
                    'country' => 'IN',
                ],
            ]
        );

        $uaeUser = $this->createBusinessUser(
            name: 'Ahmed Al-Mahmoud',
            email: 'ahmed@dubaitrading.ae',
            orgData: [
                'name' => 'Dubai Trading LLC',
                'company_name' => 'Dubai Trading Limited Liability Company',
                'tax_number' => 'AE-100234567890003',
                'registration_number' => 'CN-1234567',
                'emails' => ['info@dubaitrading.ae', 'ahmed@dubaitrading.ae'],
                'phone' => '+971-4-1234567',
                'website' => 'https://dubaitrading.ae',
                'currency' => 'AED',
                'custom_domain' => null,
                'location' => [
                    'street' => 'Office 1205, Business Bay Tower',
                    'city' => 'Dubai',
                    'state' => 'Dubai',
                    'postal_code' => '00000',
                    'country' => 'AE',
                ],
            ]
        );

        // Create GlobalCorp multi-team organization
        $globalCorpOwner = $this->createGlobalCorpOrganization();

        // Add team members and invitations
        $this->createTeamMembersAndInvitations($johnUser, $sarahUser, $mariaUser, $uaeUser, $globalCorpOwner);

        $this->info('Created users and teams successfully!');
        $this->info('Admin user: admin@invoicing.claritytech.io (password: password)');
        $this->info('Demo user: demo@invoicing.claritytech.io (password: password)');
    }

    private function createAdminUser(): User
    {
        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@invoicing.claritytech.io',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        // Create admin's personal team
        $adminTeam = $admin->ownedTeams()->create([
            'name' => 'Clarity Tech Admin',
            'personal_team' => true,
        ]);

        $admin->switchTeam($adminTeam);

        return $admin;
    }

    private function createBusinessUser(string $name, string $email, array $orgData): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        // Create user's personal team
        $personalTeam = $user->ownedTeams()->create([
            'name' => $name."'s Team",
            'personal_team' => true,
        ]);

        // Create organization location
        $location = Location::create([
            'locatable_type' => Organization::class,
            'locatable_id' => 0, // Temporary, will update after organization creation
            'name' => 'Head Office',
            'address_line_1' => $orgData['location']['street'],
            'city' => $orgData['location']['city'],
            'state' => $orgData['location']['state'],
            'postal_code' => $orgData['location']['postal_code'],
            'country' => $orgData['location']['country'],
        ]);

        // Create business organization
        $businessOrg = $user->ownedTeams()->create([
            'name' => $orgData['name'],
            'personal_team' => false,
            'company_name' => $orgData['company_name'],
            'tax_number' => $orgData['tax_number'],
            'registration_number' => $orgData['registration_number'],
            'emails' => $orgData['emails'],
            'phone' => $orgData['phone'],
            'website' => $orgData['website'],
            'currency' => $orgData['currency'],
            'custom_domain' => $orgData['custom_domain'],
            'primary_location_id' => $location->id,
        ]);

        // Update location with correct organization ID
        $location->update(['locatable_id' => $businessOrg->id]);

        $user->switchTeam($businessOrg);

        return $user;
    }

    private function createGlobalCorpOrganization(): User
    {
        $owner = User::create([
            'name' => 'Robert Global',
            'email' => 'robert@globalcorp.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        // Create personal team
        $personalTeam = $owner->ownedTeams()->create([
            'name' => "Robert Global's Team",
            'personal_team' => true,
        ]);

        // Create main holding company team
        $holdingTeam = $owner->ownedTeams()->create([
            'name' => 'GlobalCorp Holdings',
            'personal_team' => false,
            'custom_domain' => 'invoicing.globalcorp.com',
        ]);

        // Create subsidiary teams
        $techTeam = $owner->ownedTeams()->create([
            'name' => 'GlobalCorp Tech Solutions',
            'personal_team' => false,
            'custom_domain' => null,
        ]);

        $servicesTeam = $owner->ownedTeams()->create([
            'name' => 'GlobalCorp Business Services',
            'personal_team' => false,
            'custom_domain' => null,
        ]);

        $owner->switchTeam($holdingTeam);

        return $owner;
    }

    private function createTeamMembersAndInvitations(
        User $johnUser,
        User $sarahUser,
        User $mariaUser,
        User $uaeUser,
        User $globalCorpOwner
    ): void {
        // Add Sarah as editor to John's team
        $johnBusinessTeam = $johnUser->ownedTeams()
            ->where('personal_team', false)
            ->first();

        $johnBusinessTeam->users()->attach($sarahUser, ['role' => 'editor']);

        // Add John as admin to Maria's team
        $mariaBusinessTeam = $mariaUser->ownedTeams()
            ->where('personal_team', false)
            ->first();

        $mariaBusinessTeam->users()->attach($johnUser, ['role' => 'admin']);

        // Create pending team invitations
        $globalCorpMainTeam = $globalCorpOwner->ownedTeams()
            ->where('name', 'GlobalCorp Holdings')
            ->first();

        // Invite Sarah to GlobalCorp Holdings
        TeamInvitation::create([
            'team_id' => $globalCorpMainTeam->id,
            'email' => 'sarah@techstartup.com',
            'role' => 'admin',
        ]);

        // Invite a new user to join John's team
        TeamInvitation::create([
            'team_id' => $johnBusinessTeam->id,
            'email' => 'accountant@acmecorp.com',
            'role' => 'editor',
        ]);

        // Invite a finance manager to Maria's team
        TeamInvitation::create([
            'team_id' => $mariaBusinessTeam->id,
            'email' => 'finance@euroconsult.de',
            'role' => 'admin',
        ]);
    }
}
