<?php

namespace Database\Seeders;

use App\Models\Team;
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
        
        // Create business users with their own teams
        $johnUser = $this->createBusinessUser(
            name: 'John Smith',
            email: 'john@acmecorp.com',
            teamName: 'ACME Manufacturing Corp',
            teamSlug: 'acme-corp',
            customDomain: 'invoicing.acmecorp.com'
        );

        $sarahUser = $this->createBusinessUser(
            name: 'Sarah Johnson',
            email: 'sarah@techstartup.com',
            teamName: 'TechStart Innovation Hub',
            teamSlug: 'techstart',
            customDomain: 'billing.techstartup.com'
        );

        $mariaUser = $this->createBusinessUser(
            name: 'Maria Schmidt',
            email: 'maria@euroconsult.de',
            teamName: 'EuroConsult GmbH',
            teamSlug: 'euroconsult',
            customDomain: null
        );

        $demoUser = $this->createBusinessUser(
            name: 'Demo User',
            email: 'demo@invoicing.claritytech.io',
            teamName: 'Demo Company Ltd',
            teamSlug: 'demo-company',
            customDomain: null
        );

        // Create GlobalCorp multi-team organization
        $globalCorpOwner = $this->createGlobalCorpOrganization();

        // Add team members and invitations
        $this->createTeamMembersAndInvitations($johnUser, $sarahUser, $mariaUser, $globalCorpOwner);

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

    private function createBusinessUser(
        string $name,
        string $email,
        string $teamName,
        string $teamSlug,
        ?string $customDomain = null
    ): User {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        // Create user's personal team
        $personalTeam = $user->ownedTeams()->create([
            'name' => $name . "'s Team",
            'personal_team' => true,
        ]);

        // Create business team
        $businessTeam = $user->ownedTeams()->create([
            'name' => $teamName,
            'personal_team' => false,
            'slug' => $teamSlug,
            'custom_domain' => $customDomain,
        ]);

        $user->switchTeam($businessTeam);

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
            'slug' => 'globalcorp-holdings',
            'custom_domain' => 'invoicing.globalcorp.com',
        ]);

        // Create subsidiary teams
        $techTeam = $owner->ownedTeams()->create([
            'name' => 'GlobalCorp Tech Solutions',
            'personal_team' => false,
            'slug' => 'globalcorp-tech',
            'custom_domain' => null,
        ]);

        $servicesTeam = $owner->ownedTeams()->create([
            'name' => 'GlobalCorp Business Services',
            'personal_team' => false,
            'slug' => 'globalcorp-services',
            'custom_domain' => null,
        ]);

        $owner->switchTeam($holdingTeam);

        return $owner;
    }

    private function createTeamMembersAndInvitations(
        User $johnUser,
        User $sarahUser,
        User $mariaUser,
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