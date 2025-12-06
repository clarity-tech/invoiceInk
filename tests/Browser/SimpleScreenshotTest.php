<?php

namespace Tests\Browser;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SimpleScreenshotTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_capture_all_key_pages(): void
    {
        $this->browse(function (Browser $browser) {
            // 1. Registration Page
            $browser->visit('/register')
                ->pause(3000)
                ->screenshot('user-onboarding/01-registration-page');

            // Create user manually
            $user = User::factory()->withPersonalTeam()->create([
                'name' => 'John Smith Demo',
                'email' => 'john.smith@demo.test',
            ]);

            // Set incomplete setup
            $user->currentTeam->update([
                'name' => 'John\'s Organization',
                'setup_completed_at' => null,
            ]);

            // 2. Login and see what happens
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->pause(5000) // Wait for any redirects
                ->screenshot('user-onboarding/02-after-login-redirect');

            // 3. Visit setup page directly
            $browser->visit('/organization/setup')
                ->pause(4000)
                ->screenshot('user-onboarding/03-setup-wizard-step1');

            // 4. Try to interact with the form (just capture the page)
            $browser->pause(2000)
                ->screenshot('user-onboarding/04-setup-wizard-details');

            // Let's also capture the page source to debug
            $pageSource = $browser->driver->getPageSource();
            file_put_contents(__DIR__.'/../../storage/logs/setup-page-source.html', $pageSource);

            // 5. Create a user with completed setup to show the difference
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

            // 6. Visit invoices page
            $browser->visit('/invoices')
                ->pause(4000)
                ->screenshot('invoice-journey/01-invoices-page');

            // 7. Create some demo data for better screenshots
            $customer = Customer::factory()->create([
                'organization_id' => $completedUser->currentTeam->id,
                'name' => 'Demo Customer Corp',
                'email' => 'billing@democustomer.test',
            ]);

            // 8. Refresh invoices page with customer data
            $browser->refresh()
                ->pause(4000)
                ->screenshot('invoice-journey/02-invoices-page-with-data');

            // 9. Try to capture navigation menu
            try {
                $browser->click('button:contains("Demo"), .dropdown-toggle, [data-dropdown-toggle]')
                    ->pause(2000)
                    ->screenshot('user-onboarding/06-navigation-menu');
            } catch (\Exception $e) {
                // If click fails, just take another screenshot
                $browser->screenshot('user-onboarding/06-navigation-state');
            }

            // 10. Check if there are any existing invoices we can show
            $invoice = \App\Models\Invoice::factory()->create([
                'organization_id' => $completedUser->currentTeam->id,
                'customer_id' => $customer->id,
                'document_type' => 'invoice',
                'invoice_number' => 'INV-2024-001',
                'subtotal' => 100000, // ₹1000.00
                'tax_amount' => 18000,  // 18%
                'total' => 118000,
            ]);

            // 11. Public invoice view
            $browser->visit("/invoices/view/{$invoice->ulid}")
                ->pause(4000)
                ->screenshot('invoice-journey/03-public-invoice-view');

            // 12. Create an estimate
            $estimate = \App\Models\Invoice::factory()->create([
                'organization_id' => $completedUser->currentTeam->id,
                'customer_id' => $customer->id,
                'document_type' => 'estimate',
                'invoice_number' => 'EST-2024-001',
                'subtotal' => 250000, // ₹2500.00
                'tax_amount' => 45000,  // 18%
                'total' => 295000,
            ]);

            // 13. Public estimate view
            $browser->visit("/estimates/view/{$estimate->ulid}")
                ->pause(4000)
                ->screenshot('estimate-journey/01-public-estimate-view');

            // 14. Back to invoices page to show list
            $browser->visit('/invoices')
                ->pause(4000)
                ->screenshot('invoice-journey/04-invoices-list-with-data');

            // 15. Organizations page
            $browser->visit('/organizations')
                ->pause(4000)
                ->screenshot('user-onboarding/07-organizations-management');

            // 16. Customers page
            $browser->visit('/customers')
                ->pause(4000)
                ->screenshot('user-onboarding/08-customers-management');
        });
    }

    public function test_capture_setup_process(): void
    {
        $this->browse(function (Browser $browser) {
            // Create user for setup demo
            $user = User::factory()->withPersonalTeam()->create([
                'name' => 'Setup Demo User',
                'email' => 'setup@demo.test',
            ]);

            $user->currentTeam->update([
                'setup_completed_at' => null,
            ]);

            $browser->loginAs($user)
                ->visit('/organization/setup')
                ->pause(4000)
                ->screenshot('user-onboarding/setup-01-initial-state');

            // Check what elements are actually available
            $elements = $browser->driver->findElements(\Facebook\WebDriver\WebDriverBy::tagName('input'));
            $inputCount = count($elements);

            file_put_contents(__DIR__.'/../../storage/logs/form-debug.txt', "Found {$inputCount} input elements\n");

            // Just try to capture whatever we can see
            $browser->pause(2000)
                ->screenshot('user-onboarding/setup-02-form-state');

            // Try different approaches to find form elements
            try {
                // Try wire:model selectors
                $browser->type('[wire\\:model="company_name"]', 'Demo Company')
                    ->pause(1000)
                    ->screenshot('user-onboarding/setup-03-filled-company-name');
            } catch (\Exception $e) {
                try {
                    // Try ID selectors
                    $browser->type('#company_name', 'Demo Company')
                        ->pause(1000)
                        ->screenshot('user-onboarding/setup-03-filled-via-id');
                } catch (\Exception $e2) {
                    // Try name selectors
                    try {
                        $browser->type('input[name="company_name"]', 'Demo Company')
                            ->pause(1000)
                            ->screenshot('user-onboarding/setup-03-filled-via-name');
                    } catch (\Exception $e3) {
                        // Just capture the error state
                        $browser->screenshot('user-onboarding/setup-03-form-interaction-failed');
                        file_put_contents(__DIR__.'/../../storage/logs/form-debug.txt', "All selectors failed: wire:model, #id, name\n", FILE_APPEND);
                    }
                }
            }
        });
    }
}
