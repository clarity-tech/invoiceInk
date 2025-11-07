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
        $this->info('Seeding users and organizations using factory states...');

        // Create system administrator
        $admin = User::factory()->systemAdmin()->create();

        // Create business personas using factory states
        $johnUser = User::factory()->johnSmithManufacturing()->create();
        $sarahUser = User::factory()->sarahTechStartup()->create();
        $mariaUser = User::factory()->mariaEuroConsult()->create();
        $demoUser = User::factory()->demoUser()->create();
        $uaeUser = User::factory()->ahmedDubaiTrader()->create();

        // Create multi-organization owner
        $globalCorpOwner = User::factory()->robertGlobalCorp()->create();

        // Add team members and invitations using existing relationships
        $this->createTeamMembersAndInvitations($johnUser, $sarahUser, $mariaUser, $uaeUser, $globalCorpOwner);

        $this->info('Created users and organizations successfully!');
        $this->info('✓ System Admin: admin@invoicing.claritytech.test (password: password)');
        $this->info('✓ Demo User: demo@invoicing.claritytech.test (password: password)');
        $this->info('✓ Business Users: john@acmecorp.com, sarah@techstartup.com, maria@euroconsult.de');
        $this->info('✓ UAE User: ahmed@dubaitrading.ae');
        $this->info('✓ Multi-org Owner: robert@globalcorp.com');
        $this->info('All passwords: password');
    }


    private function createTeamMembersAndInvitations(
        User $johnUser,
        User $sarahUser,
        User $mariaUser,
        User $uaeUser,
        User $globalCorpOwner
    ): void {
        // Get business organizations for each user
        $johnOrg = $johnUser->ownedTeams()->where('personal_team', false)->first();
        $mariaOrg = $mariaUser->ownedTeams()->where('personal_team', false)->first();
        $globalCorpOrg = $globalCorpOwner->ownedTeams()
            ->where('name', 'LIKE', 'GlobalCorp Holdings%')
            ->first();

        // Cross-team memberships for collaboration
        if ($johnOrg) {
            $johnOrg->users()->attach($sarahUser, ['role' => 'editor']);
            $this->info('✓ Added Sarah as editor to John\'s manufacturing team');
        }

        if ($mariaOrg) {
            $mariaOrg->users()->attach($johnUser, ['role' => 'admin']);
            $this->info('✓ Added John as admin to Maria\'s consulting team');
        }

        // Create pending team invitations for realistic scenarios
        if ($globalCorpOrg) {
            TeamInvitation::create([
                'team_id' => $globalCorpOrg->id,
                'email' => 'sarah@techstartup.com',
                'role' => 'admin',
            ]);
            $this->info('✓ Created invitation for Sarah to join GlobalCorp');
        }

        if ($johnOrg) {
            TeamInvitation::create([
                'team_id' => $johnOrg->id,
                'email' => 'accountant@acmecorp.com',
                'role' => 'editor',
            ]);
            $this->info('✓ Created invitation for accountant to join ACME Manufacturing');
        }

        if ($mariaOrg) {
            TeamInvitation::create([
                'team_id' => $mariaOrg->id,
                'email' => 'finance@euroconsult.de',
                'role' => 'admin',
            ]);
            $this->info('✓ Created invitation for finance manager to join EuroConsult');
        }

        $this->info('Team relationships and invitations created successfully!');
    }
}
