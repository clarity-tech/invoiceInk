<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\TeamInvitation;
use App\Models\User;
use Throwable;

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

        // Fix current team assignments for users created with teams
        $this->fixCurrentTeamAssignments($admin, $johnUser, $sarahUser, $mariaUser, $demoUser, $uaeUser);

        // Create multi-organization owner
        $globalCorpOwner = User::factory()->robertGlobalCorp()->create();

        // A brand new signup who hasn't finished onboarding, so the setup
        // wizard has something to demo.
        $newUser = User::factory()->create([
            'name' => 'Priya Nair',
            'email' => 'priya@freshstart.test',
        ]);

        $newUser->ownedTeams()->save(
            Organization::factory()->incompleteSetup()->make([
                'name' => 'Fresh Start Studio',
                'currency' => 'INR',
            ])
        );

        // Add team members and invitations using existing relationships
        $this->createTeamMembersAndInvitations($johnUser, $sarahUser, $mariaUser, $uaeUser, $globalCorpOwner);

        $this->attachOrganizationLogos();

        $this->info('Created users and organizations successfully!');
        $this->info('✓ System Admin: admin@invoicing.claritytech.test (password: password)');
        $this->info('✓ Demo User: demo@invoicing.claritytech.test (password: password)');
        $this->info('✓ Business Users: john@acmecorp.test, sarah@techstartup.test, maria@euroconsult.test');
        $this->info('✓ UAE User: ahmed@dubaitrading.test');
        $this->info('✓ Multi-org Owner: robert@globalcorp.test');
        $this->info('✓ Onboarding (setup incomplete): priya@freshstart.test');
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
        $johnOrg = $johnUser->ownedTeams()->first();
        $mariaOrg = $mariaUser->ownedTeams()->first();
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
                'email' => 'sarah@techstartup.test',
                'role' => 'admin',
            ]);
            $this->info('✓ Created invitation for Sarah to join GlobalCorp');
        }

        if ($johnOrg) {
            TeamInvitation::create([
                'team_id' => $johnOrg->id,
                'email' => 'accountant@acmecorp.test',
                'role' => 'editor',
            ]);
            $this->info('✓ Created invitation for accountant to join ACME Manufacturing');
        }

        if ($mariaOrg) {
            TeamInvitation::create([
                'team_id' => $mariaOrg->id,
                'email' => 'finance@euroconsult.test',
                'role' => 'admin',
            ]);
            $this->info('✓ Created invitation for finance manager to join EuroConsult');
        }

        $this->info('Team relationships and invitations created successfully!');
    }

    /**
     * Fix current team assignments for users who should have a current team.
     */
    /**
     * Give each set-up organization a logo so documents, emails and the org
     * pages have real branding rather than a placeholder.
     */
    private function attachOrganizationLogos(): void
    {
        $organizations = Organization::whereNotNull('setup_completed_at')->get();

        foreach ($organizations as $organization) {
            if ($organization->getFirstMedia('logo')) {
                continue;
            }

            try {
                $organization
                    ->addMediaFromString($this->generateLogo($organization->name))
                    ->usingFileName('logo.png')
                    ->toMediaCollection('logo');
            } catch (Throwable $exception) {
                $this->warn('Skipped logos — object storage unavailable: '.$exception->getMessage());

                return;
            }
        }

        $this->info('✓ Added logos for '.$organizations->count().' organizations');
    }

    /**
     * A square PNG carrying the organization's initials on a brand-coloured
     * background. Generated rather than shipped so no binary lives in the repo.
     */
    private function generateLogo(string $name): string
    {
        $initials = collect(explode(' ', $name))
            ->filter()
            ->take(2)
            ->map(fn (string $word) => strtoupper($word[0]))
            ->implode('');

        $image = imagecreatetruecolor(240, 240);
        imagefilledrectangle($image, 0, 0, 240, 240, imagecolorallocate($image, 29, 78, 216));
        imagestring($image, 5, 105, 112, $initials, imagecolorallocate($image, 255, 255, 255));

        ob_start();
        imagepng($image);
        imagedestroy($image);

        return (string) ob_get_clean();
    }

    private function fixCurrentTeamAssignments(User ...$users): void
    {
        foreach ($users as $user) {
            if (is_null($user->current_team_id) && $user->ownedTeams()->count() > 0) {
                $firstTeam = $user->ownedTeams()->first();
                $user->update(['current_team_id' => $firstTeam->id]);
                $this->info("✓ Set current team for {$user->email} to: {$firstTeam->name}");
            }
        }
    }
}
