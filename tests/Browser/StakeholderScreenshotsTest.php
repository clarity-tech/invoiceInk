<?php

namespace Tests\Browser;

use App\Models\Customer;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class StakeholderScreenshotsTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_onboarding_journey(): void
    {
        $this->browse(function (Browser $browser) {
            // 1. Registration Page
            $browser->visit('/register')
                ->pause(2000)
                ->screenshot('user-onboarding/01-registration-page');

            // Manually create user to bypass registration complexity
            $user = User::factory()->withPersonalTeam()->create([
                'name' => 'John Smith',
                'email' => 'john.smith@example.test',
            ]);

            // Ensure setup is incomplete
            $user->currentTeam->update(['setup_completed_at' => null]);

            // 2. Setup Wizard Step 1 - Empty
            $browser->loginAs($user)
                ->visit('/organization/setup')
                ->pause(3000)
                ->screenshot('user-onboarding/02-setup-wizard-step1-empty');

            // 3. Fill Step 1 - Company Information
            $browser->type('#company_name', 'Acme Corporation')
                ->type('#tax_number', '12-3456789')
                ->type('#registration_number', 'REG-2024-001')
                ->type('#website', 'https://acme-corp.com')
                ->type('#notes', 'Leading provider of innovative business solutions')
                ->pause(1000)
                ->screenshot('user-onboarding/03-setup-wizard-step1-filled');

            // 4. Go to Step 2
            try {
                $browser->click('button[wire\\:click="nextStep"]');
            } catch (\Exception $e) {
                // Try alternative selector
                $browser->click('.next-step, [wire\\:click="nextStep"], .btn-next');
            }

            $browser->pause(3000)
                ->screenshot('user-onboarding/04-setup-wizard-step2-empty');

            // 5. Fill Step 2 - Primary Location
            $browser->type('#location_name', 'Corporate Headquarters')
                ->type('#gstin', '29ABCDE1234F1Z5')
                ->type('#address_line_1', '123 Business Park, Tech Hub')
                ->type('#address_line_2', 'Suite 456, Tower A')
                ->type('#city', 'Bangalore')
                ->type('#state', 'Karnataka')
                ->type('#postal_code', '560001')
                ->pause(1000)
                ->screenshot('user-onboarding/05-setup-wizard-step2-filled');

            // 6. Go to Step 3
            try {
                $browser->click('button[wire\\:click="nextStep"]');
            } catch (\Exception $e) {
                $browser->click('.next-step, [wire\\:click="nextStep"], .btn-next');
            }

            $browser->pause(3000)
                ->screenshot('user-onboarding/06-setup-wizard-step3-empty');

            // 7. Fill Step 3 - Currency & Financial Year
            $browser->select('select[wire\\:model="country_code"], #country_code', 'IN')
                ->pause(3000) // Wait for auto-population
                ->screenshot('user-onboarding/07-setup-wizard-step3-filled');

            // 8. Go to Step 4
            try {
                $browser->click('button[wire\\:click="nextStep"]');
            } catch (\Exception $e) {
                $browser->click('.next-step, [wire\\:click="nextStep"], .btn-next');
            }

            $browser->pause(3000)
                ->screenshot('user-onboarding/08-setup-wizard-step4-empty');

            // 9. Fill Step 4 - Contact Information
            $browser->type('input[wire\\:model="emails.0"], #emails_0', 'contact@acme-corp.com')
                ->pause(1000);

            // Try to add another email
            try {
                $browser->click('button[wire\\:click="addEmailField"]');
                $browser->pause(1000)
                    ->type('input[wire\\:model="emails.1"], #emails_1', 'billing@acme-corp.com');
            } catch (\Exception $e) {
                // Email field addition failed, continue
            }

            $browser->type('input[wire\\:model="phone"], #phone', '+91-80-12345678')
                ->pause(1000)
                ->screenshot('user-onboarding/09-setup-wizard-step4-filled');

            // 10. Complete Setup (screenshot first, then try to complete)
            $browser->screenshot('user-onboarding/10-setup-wizard-ready-to-complete');

            // Try to complete setup
            try {
                $browser->click('button[wire\\:click="completeSetup"]')
                    ->pause(5000);

                // If successful, take completion screenshot
                $browser->screenshot('user-onboarding/11-setup-completed-dashboard');
            } catch (\Exception $e) {
                // If completion fails, just take a screenshot of the attempt
                $browser->screenshot('user-onboarding/11-setup-completion-attempt');
            }

            // 12. Try to show navigation
            try {
                $browser->click('button, .dropdown-toggle, [data-dropdown-toggle]')
                    ->pause(2000)
                    ->screenshot('user-onboarding/12-navigation-menu');
            } catch (\Exception $e) {
                // Navigation click failed, just take a screenshot of the page
                $browser->screenshot('user-onboarding/12-final-page-state');
            }
        });
    }

    public function test_completed_setup_dashboard(): void
    {
        // Create user with completed setup for comparison
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
                ->screenshot('user-onboarding/13-completed-organization-dashboard');
        });
    }

    public function test_invoice_creation_flow(): void
    {
        // Create user with completed setup
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Invoice Manager',
            'email' => 'manager@company.test',
        ]);

        $organization = $user->currentTeam;
        $organization->update([
            'company_name' => 'Professional Services LLC',
            'setup_completed_at' => now(),
        ]);

        // Create customer
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Tech Startup Inc',
            'email' => 'billing@techstartup.test',
        ]);

        $this->browse(function (Browser $browser) use ($user, $organization, $customer) {
            // Login and dashboard
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->pause(3000)
                ->screenshot('invoice-journey/01-dashboard-logged-in');

            // Navigate to invoices
            $browser->visit('/invoices')
                ->pause(3000)
                ->screenshot('invoice-journey/02-invoices-page-empty');

            // Start creating invoice
            try {
                $browser->click('button[wire\\:click="create"], .create-invoice, .btn-create')
                    ->pause(3000)
                    ->screenshot('invoice-journey/03-invoice-wizard-step1-empty');
            } catch (\Exception $e) {
                $browser->screenshot('invoice-journey/03-invoices-page-no-create-button');

                return;
            }

            // Fill basic information
            try {
                $browser->select('select[wire\\:model="organization_id"], #organization_id', $organization->id)
                    ->pause(1000)
                    ->select('select[wire\\:model="customer_id"], #customer_id', $customer->id)
                    ->pause(2000)
                    ->screenshot('invoice-journey/04-invoice-wizard-step1-filled');

                // Continue to next steps...
                $browser->click('button[wire\\:click="nextStep"], .next-step')
                    ->pause(3000)
                    ->screenshot('invoice-journey/05-invoice-wizard-step2');

                $browser->click('button[wire\\:click="nextStep"], .next-step')
                    ->pause(3000)
                    ->screenshot('invoice-journey/06-invoice-wizard-step3-empty');

                // Add items
                $browser->type('input[wire\\:model="items.0.description"], #item_0_description', 'Web Development Services')
                    ->type('input[wire\\:model="items.0.quantity"], #item_0_quantity', '40')
                    ->type('input[wire\\:model="items.0.unit_price"], #item_0_unit_price', '2500.00')
                    ->type('input[wire\\:model="items.0.tax_rate"], #item_0_tax_rate', '18')
                    ->pause(3000)
                    ->screenshot('invoice-journey/07-invoice-wizard-with-items');

            } catch (\Exception $e) {
                $browser->screenshot('invoice-journey/04-invoice-wizard-error');
            }
        });
    }

    public function test_estimates_flow(): void
    {
        // Create user with completed setup
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Sales Manager',
            'email' => 'sales@company.test',
        ]);

        $organization = $user->currentTeam;
        $organization->update([
            'company_name' => 'Consulting Solutions Ltd',
            'setup_completed_at' => now(),
        ]);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Future Client Corp',
            'email' => 'procurement@futureclient.test',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/invoices')
                ->pause(3000)
                ->screenshot('estimate-journey/01-invoices-page');

            // Try to switch to estimates tab
            try {
                $browser->click('button[wire\\:click="$set(\'activeTab\', \'estimates\')"], .estimates-tab, [data-tab="estimates"]')
                    ->pause(3000)
                    ->screenshot('estimate-journey/02-estimates-tab');
            } catch (\Exception $e) {
                $browser->screenshot('estimate-journey/02-estimates-tab-not-found');
            }
        });
    }

    public function test_public_views(): void
    {
        // Create a user and invoice for public viewing
        $user = User::factory()->withPersonalTeam()->create();
        $organization = $user->currentTeam;
        $organization->update(['setup_completed_at' => now()]);

        $customer = Customer::factory()->create(['organization_id' => $organization->id]);

        $invoice = \App\Models\Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'document_type' => 'invoice',
        ]);

        $this->browse(function (Browser $browser) use ($invoice) {
            // Public invoice view
            $browser->visit("/invoices/view/{$invoice->ulid}")
                ->pause(3000)
                ->screenshot('invoice-journey/08-public-invoice-view');
        });

        // Create estimate for public view
        $estimate = \App\Models\Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'document_type' => 'estimate',
        ]);

        $this->browse(function (Browser $browser) use ($estimate) {
            // Public estimate view
            $browser->visit("/estimates/view/{$estimate->ulid}")
                ->pause(3000)
                ->screenshot('estimate-journey/03-public-estimate-view');
        });
    }
}
