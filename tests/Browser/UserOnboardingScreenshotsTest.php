<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class UserOnboardingScreenshotsTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_complete_user_onboarding_journey(): void
    {
        $this->browse(function (Browser $browser) {
            // Screenshot 1: Registration Page
            $browser->visit('/register')
                ->waitFor('form')
                ->screenshot('user-onboarding/01-registration-page');

            // Fill registration form
            $browser->type('name', 'John Smith')
                ->type('email', 'john.smith@example.test')
                ->type('password', 'password123')
                ->type('password_confirmation', 'password123')
                ->check('terms')
                ->screenshot('user-onboarding/02-registration-form-filled')
                ->press('Register');

            // Wait for redirect and take screenshot
            $browser->waitForLocation('/organization/setup')
                ->waitFor('.setup-wizard')
                ->screenshot('user-onboarding/03-setup-wizard-redirect');

            // Step 1: Company Information
            $browser->assertSee('Company Information')
                ->screenshot('user-onboarding/04-step1-company-info-empty');

            $browser->type('company_name', 'Acme Corporation')
                ->type('tax_number', '12-3456789')
                ->type('registration_number', 'REG-2024-001')
                ->type('website', 'https://acme-corp.com')
                ->type('notes', 'Leading provider of innovative business solutions')
                ->screenshot('user-onboarding/05-step1-company-info-filled')
                ->click('[wire:click="nextStep"]');

            // Step 2: Primary Location
            $browser->waitFor('.step-2')
                ->assertSee('Primary Location')
                ->screenshot('user-onboarding/06-step2-location-empty');

            $browser->type('location_name', 'Corporate Headquarters')
                ->type('gstin', '29ABCDE1234F1Z5')
                ->type('address_line_1', '123 Business Park, Tech Hub')
                ->type('address_line_2', 'Suite 456, Tower A')
                ->type('city', 'Bangalore')
                ->type('state', 'Karnataka')
                ->type('postal_code', '560001')
                ->screenshot('user-onboarding/07-step2-location-filled')
                ->click('[wire:click="nextStep"]');

            // Step 3: Currency & Financial Year Configuration
            $browser->waitFor('.step-3')
                ->assertSee('Configuration')
                ->screenshot('user-onboarding/08-step3-config-empty');

            // Select country (this will auto-populate currency and financial year)
            $browser->select('country_code', 'IN')
                ->pause(1000) // Wait for auto-population
                ->screenshot('user-onboarding/09-step3-config-filled')
                ->click('[wire:click="nextStep"]');

            // Step 4: Contact Information
            $browser->waitFor('.step-4')
                ->assertSee('Contact Details')
                ->screenshot('user-onboarding/10-step4-contact-empty');

            $browser->type('emails.0', 'contact@acme-corp.com')
                ->click('[wire:click="addEmailField"]')
                ->type('emails.1', 'billing@acme-corp.com')
                ->type('phone', '+91-80-12345678')
                ->screenshot('user-onboarding/11-step4-contact-filled');

            // Complete setup
            $browser->click('[wire:click="completeSetup"]')
                ->waitForLocation('/dashboard')
                ->waitFor('.welcome-message')
                ->screenshot('user-onboarding/12-setup-complete-dashboard');

            // Navigation showing complete setup
            $browser->click('[data-dropdown-toggle="navbar-dropdown"]')
                ->waitFor('.dropdown-menu')
                ->screenshot('user-onboarding/13-navigation-setup-complete');
        });

        $this->updateTodoStatus('43', 'completed');
        $this->updateTodoStatus('44', 'completed');
        $this->updateTodoStatus('45', 'completed');
        $this->updateTodoStatus('46', 'completed');
        $this->updateTodoStatus('47', 'completed');
        $this->updateTodoStatus('48', 'completed');
        $this->updateTodoStatus('49', 'completed');
        $this->updateTodoStatus('50', 'completed');
    }

    public function test_existing_user_setup_indicators(): void
    {
        // Create user with incomplete setup
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.test',
        ]);

        // Ensure organization setup is incomplete
        $user->currentTeam->update(['setup_completed_at' => null]);

        $this->browse(function (Browser $browser) use ($user) {
            // Login and show setup indicators
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->waitForLocation('/organization/setup')
                ->screenshot('user-onboarding/14-existing-user-setup-required');

            // Show navigation with setup indicators
            $browser->visit('/dashboard')
                ->waitForLocation('/organization/setup')
                ->screenshot('user-onboarding/15-setup-wizard-progress-indicators');
        });
    }

    private function updateTodoStatus(string $id, string $status): void
    {
        // This is a placeholder - the actual todo updating would be handled by the main test runner
    }
}