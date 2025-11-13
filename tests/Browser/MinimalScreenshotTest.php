<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class MinimalScreenshotTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_capture_core_user_journey(): void
    {
        $this->browse(function (Browser $browser) {
            // 1. Registration Page
            $browser->visit('/register')
                ->pause(3000)
                ->screenshot('user-onboarding/01-registration-page');
            
            // 2. Login Page
            $browser->visit('/login')
                ->pause(3000)
                ->screenshot('user-onboarding/02-login-page');

            // Create user with incomplete setup
            $user = User::factory()->withPersonalTeam()->create([
                'name' => 'Demo User',
                'email' => 'demo@test.test',
            ]);
            
            $user->currentTeam->update([
                'name' => 'Demo Organization',
                'setup_completed_at' => null,
                'company_name' => null,
            ]);

            // 3. Login and show dashboard redirect
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->pause(5000)
                ->screenshot('user-onboarding/03-dashboard-incomplete-setup');

            // 4. Setup wizard page
            $browser->visit('/organization/setup')
                ->pause(4000)
                ->screenshot('user-onboarding/04-setup-wizard-step1');

            // 5. Create completed user
            $completedUser = User::factory()->withPersonalTeam()->create([
                'name' => 'Completed Demo',
                'email' => 'completed@test.test',
            ]);
            
            $completedUser->currentTeam->update([
                'name' => 'Completed Organization',
                'company_name' => 'Demo Corp Ltd',
                'setup_completed_at' => now(),
            ]);

            // 6. Completed dashboard
            $browser->loginAs($completedUser)
                ->visit('/dashboard')
                ->pause(4000)
                ->screenshot('user-onboarding/05-dashboard-completed-setup');

            // 7. Main application pages
            $browser->visit('/invoices')
                ->pause(4000)
                ->screenshot('invoice-journey/01-invoices-page');

            $browser->visit('/organizations')
                ->pause(4000)
                ->screenshot('user-onboarding/06-organizations-page');

            $browser->visit('/customers')
                ->pause(4000)
                ->screenshot('user-onboarding/07-customers-page');
        });
    }

    public function test_capture_setup_process(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->withPersonalTeam()->create([
                'name' => 'Setup Process Demo',
                'email' => 'setup.process@test.test',
            ]);
            
            $user->currentTeam->update(['setup_completed_at' => null]);

            $browser->loginAs($user)
                ->visit('/organization/setup')
                ->pause(4000)
                ->screenshot('user-onboarding/setup-01-initial');

            // Save HTML for analysis
            $html = $browser->driver->getPageSource();
            file_put_contents(storage_path('logs/setup-form.html'), $html);

            // Take additional screenshot after a moment
            $browser->pause(2000)
                ->screenshot('user-onboarding/setup-02-loaded');
        });
    }

    public function test_capture_public_views(): void
    {
        $this->browse(function (Browser $browser) {
            // Create a minimal invoice record directly in database
            $user = User::factory()->withPersonalTeam()->create();
            $organization = $user->currentTeam;
            $organization->update(['setup_completed_at' => now()]);

            $invoice = new \App\Models\Invoice([
                'ulid' => \Illuminate\Support\Str::ulid(),
                'organization_id' => $organization->id,
                'invoice_number' => 'INV-2024-001',
                'document_type' => 'invoice',
                'subtotal' => 100000,
                'tax_amount' => 18000,
                'total' => 118000,
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'status' => 'draft',
            ]);
            $invoice->save();

            // Public invoice view
            $browser->visit("/invoices/{$invoice->ulid}")
                ->pause(4000)
                ->screenshot('invoice-journey/02-public-invoice-view');

            // Create estimate
            $estimate = new \App\Models\Invoice([
                'ulid' => \Illuminate\Support\Str::ulid(),
                'organization_id' => $organization->id,
                'invoice_number' => 'EST-2024-001',
                'document_type' => 'estimate',
                'subtotal' => 250000,
                'tax_amount' => 45000,
                'total' => 295000,
                'invoice_date' => now(),
                'due_date' => now()->addDays(15),
                'status' => 'draft',
            ]);
            $estimate->save();

            // Public estimate view
            $browser->visit("/estimates/{$estimate->ulid}")
                ->pause(4000)
                ->screenshot('estimate-journey/01-public-estimate-view');
        });
    }
}