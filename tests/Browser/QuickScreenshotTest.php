<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class QuickScreenshotTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_capture_key_pages(): void
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

            // Create user manually
            $user = User::factory()->withPersonalTeam()->create([
                'name' => 'John Smith Demo',
                'email' => 'john.smith@demo.test',
            ]);

            // Set incomplete setup
            $user->currentTeam->update([
                'name' => 'John\'s Organization',
                'setup_completed_at' => null,
                'company_name' => null,
            ]);

            // 3. Dashboard with incomplete setup (should redirect)
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->pause(5000) // Wait for any redirects
                ->screenshot('user-onboarding/03-dashboard-redirect-to-setup');

            // 4. Setup wizard direct visit
            $browser->visit('/organization/setup')
                ->pause(4000)
                ->screenshot('user-onboarding/04-setup-wizard-empty');

            // 5. Complete setup example - create another user
            $completedUser = User::factory()->withPersonalTeam()->create([
                'name' => 'Completed User',
                'email' => 'completed@demo.test',
            ]);

            $completedUser->currentTeam->update([
                'company_name' => 'Demo Corporation',
                'setup_completed_at' => now(),
            ]);

            $browser->loginAs($completedUser)
                ->visit('/dashboard')
                ->pause(4000)
                ->screenshot('user-onboarding/05-completed-setup-dashboard');

            // 6. Main application pages
            $browser->visit('/invoices')
                ->pause(4000)
                ->screenshot('invoice-journey/01-invoices-page-empty');

            $browser->visit('/organizations')
                ->pause(4000)
                ->screenshot('user-onboarding/06-organizations-page');

            $browser->visit('/customers')
                ->pause(4000)
                ->screenshot('user-onboarding/07-customers-page');

            // 7. Create sample data to show populated views
            $invoice = \App\Models\Invoice::factory()->create([
                'organization_id' => $completedUser->currentTeam->id,
                'customer_id' => null, // Skip customer to avoid schema issues
                'document_type' => 'invoice',
                'invoice_number' => 'INV-2024-001',
                'subtotal' => 100000,
                'tax_amount' => 18000,
                'total' => 118000,
            ]);

            // 8. Public invoice view
            $browser->visit("/invoices/view/{$invoice->ulid}")
                ->pause(4000)
                ->screenshot('invoice-journey/02-public-invoice-view');

            // 9. Create estimate
            $estimate = \App\Models\Invoice::factory()->create([
                'organization_id' => $completedUser->currentTeam->id,
                'customer_id' => null,
                'document_type' => 'estimate',
                'invoice_number' => 'EST-2024-001',
                'subtotal' => 250000,
                'tax_amount' => 45000,
                'total' => 295000,
            ]);

            // 10. Public estimate view
            $browser->visit("/estimates/view/{$estimate->ulid}")
                ->pause(4000)
                ->screenshot('estimate-journey/01-public-estimate-view');

            // 11. Back to invoices to show populated list
            $browser->visit('/invoices')
                ->pause(4000)
                ->screenshot('invoice-journey/03-invoices-page-with-data');
        });
    }

    public function test_capture_setup_workflow(): void
    {
        $this->browse(function (Browser $browser) {
            // Create user for demonstration
            $user = User::factory()->withPersonalTeam()->create([
                'name' => 'Setup Demo',
                'email' => 'setup@demo.test',
            ]);

            $user->currentTeam->update(['setup_completed_at' => null]);

            $browser->loginAs($user)
                ->visit('/organization/setup')
                ->pause(4000);

            // Take screenshots of each state
            $browser->screenshot('user-onboarding/setup-step1-empty');

            // Capture the HTML structure for debugging
            $html = $browser->driver->getPageSource();
            file_put_contents('/tmp/setup-page.html', $html);

            // Show different setup steps by manually updating the component state if possible
            // For now, just capture what we can see
            $browser->pause(2000)
                ->screenshot('user-onboarding/setup-form-details');
        });
    }

    public function test_capture_navigation_states(): void
    {
        $this->browse(function (Browser $browser) {
            // User with incomplete setup
            $incompleteUser = User::factory()->withPersonalTeam()->create([
                'name' => 'Incomplete Setup',
                'email' => 'incomplete@demo.test',
            ]);
            $incompleteUser->currentTeam->update(['setup_completed_at' => null]);

            $browser->loginAs($incompleteUser)
                ->visit('/organization/setup')
                ->pause(3000)
                ->screenshot('user-onboarding/navigation-incomplete-setup');

            // User with complete setup
            $completeUser = User::factory()->withPersonalTeam()->create([
                'name' => 'Complete Setup',
                'email' => 'complete@demo.test',
            ]);
            $completeUser->currentTeam->update([
                'company_name' => 'Complete Corp',
                'setup_completed_at' => now(),
            ]);

            $browser->loginAs($completeUser)
                ->visit('/dashboard')
                ->pause(3000)
                ->screenshot('user-onboarding/navigation-complete-setup');
        });
    }
}
