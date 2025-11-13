<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ScreenshotCaptureTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_capture_registration_page(): void
    {
        $this->browse(function (Browser $browser) {
            // Screenshot 1: Registration Page
            $browser->visit('/register')
                ->pause(2000)
                ->screenshot('user-onboarding/01-registration-page');
        });
    }

    public function test_capture_setup_wizard_empty(): void
    {
        // Create a user manually to test setup wizard
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'John Smith',
            'email' => 'john.smith@example.test',
        ]);

        // Ensure setup is incomplete
        $user->currentTeam->update(['setup_completed_at' => null]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/organization/setup')
                ->pause(3000)
                ->screenshot('user-onboarding/02-setup-wizard-step1-empty');
        });
    }

    public function test_capture_dashboard_after_setup(): void
    {
        // Create a user with completed setup
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.test',
        ]);

        $organization = $user->currentTeam;
        $organization->update([
            'company_name' => 'Completed Corp',
            'setup_completed_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->pause(3000)
                ->screenshot('user-onboarding/03-dashboard-completed-setup');
        });
    }

    public function test_capture_invoices_page(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'email' => 'invoice@test.test',
        ]);
        $user->currentTeam->update(['setup_completed_at' => now()]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/invoices')
                ->pause(3000)
                ->screenshot('invoice-journey/01-invoices-page-empty');
        });
    }
}