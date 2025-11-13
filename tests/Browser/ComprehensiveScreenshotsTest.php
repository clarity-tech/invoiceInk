<?php

namespace Tests\Browser;

use App\Models\Customer;
use App\Models\Location;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ComprehensiveScreenshotsTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_complete_user_onboarding_flow(): void
    {
        $this->browse(function (Browser $browser) {
            // Step 1: Registration Page
            $browser->visit('/register')
                ->pause(2000)
                ->screenshot('user-onboarding/01-registration-page');

            // Step 2: Fill Registration Form
            $browser->type('name', 'John Smith')
                ->type('email', 'john.smith@example.test')
                ->type('password', 'password123')
                ->type('password_confirmation', 'password123');

            // Check if terms checkbox exists
            try {
                $browser->check('terms');
            } catch (\Exception $e) {
                // Terms not required, continue
            }

            $browser->screenshot('user-onboarding/02-registration-form-filled')
                ->click('button[type="submit"]') // Use generic button selector
                ->pause(5000); // Wait for registration and redirect

            // Step 3: Setup Wizard - Step 1
            $browser->screenshot('user-onboarding/03-setup-wizard-step1-empty');

            // Fill Step 1 - Company Information
            $browser->type('company_name', 'Acme Corporation')
                ->type('tax_number', '12-3456789')
                ->type('registration_number', 'REG-2024-001')
                ->type('website', 'https://acme-corp.com')
                ->type('notes', 'Leading provider of innovative business solutions')
                ->screenshot('user-onboarding/04-setup-wizard-step1-filled');

            // Go to Step 2
            $browser->click('button[wire\\:click="nextStep"]')
                ->pause(2000)
                ->screenshot('user-onboarding/05-setup-wizard-step2-empty');

            // Fill Step 2 - Primary Location
            $browser->type('location_name', 'Corporate Headquarters')
                ->type('gstin', '29ABCDE1234F1Z5')
                ->type('address_line_1', '123 Business Park, Tech Hub')
                ->type('address_line_2', 'Suite 456, Tower A')
                ->type('city', 'Bangalore')
                ->type('state', 'Karnataka')
                ->type('postal_code', '560001')
                ->screenshot('user-onboarding/06-setup-wizard-step2-filled');

            // Go to Step 3
            $browser->click('button[wire\\:click="nextStep"]')
                ->pause(2000)
                ->screenshot('user-onboarding/07-setup-wizard-step3-empty');

            // Fill Step 3 - Currency & Financial Year
            $browser->select('country_code', 'IN')
                ->pause(2000) // Wait for auto-population
                ->screenshot('user-onboarding/08-setup-wizard-step3-filled');

            // Go to Step 4
            $browser->click('button[wire\\:click="nextStep"]')
                ->pause(2000)
                ->screenshot('user-onboarding/09-setup-wizard-step4-empty');

            // Fill Step 4 - Contact Information
            $browser->type('emails.0', 'contact@acme-corp.com')
                ->click('button[wire\\:click="addEmailField"]')
                ->pause(1000)
                ->type('emails.1', 'billing@acme-corp.com')
                ->type('phone', '+91-80-12345678')
                ->screenshot('user-onboarding/10-setup-wizard-step4-filled');

            // Complete Setup
            $browser->click('button[wire\\:click="completeSetup"]')
                ->pause(5000) // Wait for completion
                ->screenshot('user-onboarding/11-setup-completed-dashboard');

            // Navigation with completed setup
            $browser->click('.dropdown-toggle, [data-dropdown-toggle], button[onclick]')
                ->pause(1000)
                ->screenshot('user-onboarding/12-navigation-setup-complete');
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

        // Create organization location
        $orgLocation = Location::factory()->create([
            'locatable_type' => Organization::class,
            'locatable_id' => $organization->id,
            'name' => 'Head Office',
            'address_line_1' => '456 Corporate Blvd',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'country' => 'IN',
            'postal_code' => '400001',
        ]);
        $organization->update(['primary_location_id' => $orgLocation->id]);

        // Create customer
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Tech Startup Inc',
            'email' => 'billing@techstartup.test',
        ]);

        $customerLocation = Location::factory()->create([
            'locatable_type' => Customer::class,
            'locatable_id' => $customer->id,
            'name' => 'Customer Office',
            'address_line_1' => '789 Innovation Drive',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'country' => 'IN',
            'postal_code' => '411001',
        ]);
        $customer->update(['primary_location_id' => $customerLocation->id]);

        $this->browse(function (Browser $browser) use ($user, $organization, $customer) {
            // Login and navigate to dashboard
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->pause(3000)
                ->screenshot('invoice-journey/01-dashboard-logged-in');

            // Navigate to invoices
            $browser->visit('/invoices')
                ->pause(3000)
                ->screenshot('invoice-journey/02-invoices-page-empty');

            // Start creating invoice
            $browser->click('button[wire\\:click="create"]')
                ->pause(2000)
                ->screenshot('invoice-journey/03-invoice-wizard-step1-empty');

            // Fill basic information
            $browser->select('organization_id', $organization->id)
                ->pause(1000)
                ->select('customer_id', $customer->id)
                ->pause(1000)
                ->screenshot('invoice-journey/04-invoice-wizard-step1-filled');

            // Go to step 2
            $browser->click('button[wire\\:click="nextStep"]')
                ->pause(2000)
                ->screenshot('invoice-journey/05-invoice-wizard-step2-locations');

            // Go to step 3
            $browser->click('button[wire\\:click="nextStep"]')
                ->pause(2000)
                ->screenshot('invoice-journey/06-invoice-wizard-step3-empty');

            // Add invoice items
            $browser->type('items.0.description', 'Web Development Services')
                ->type('items.0.quantity', '40')
                ->type('items.0.unit_price', '2500.00')
                ->type('items.0.tax_rate', '18')
                ->pause(2000) // Wait for calculations
                ->screenshot('invoice-journey/07-invoice-wizard-step3-with-items');

            // Add invoice details
            $browser->type('invoice_date', '2024-01-15')
                ->type('due_date', '2024-02-15')
                ->type('notes', 'Payment terms: 30 days. Late payment charges may apply.')
                ->screenshot('invoice-journey/08-invoice-wizard-complete');

            // Save invoice
            $browser->click('button[wire\\:click="save"]')
                ->pause(3000)
                ->screenshot('invoice-journey/09-invoice-created-success');
        });
    }

    public function test_estimate_creation_flow(): void
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

        // Create customer
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Future Client Corp',
            'email' => 'procurement@futureclient.test',
        ]);

        $this->browse(function (Browser $browser) use ($user, $organization, $customer) {
            // Login and navigate to invoices/estimates
            $browser->loginAs($user)
                ->visit('/invoices')
                ->pause(3000)
                ->screenshot('estimate-journey/01-invoices-page');

            // Switch to estimates tab
            $browser->click('button[wire\\:click="$set(\'activeTab\', \'estimates\')"], .estimates-tab, [data-tab="estimates"]')
                ->pause(2000)
                ->screenshot('estimate-journey/02-estimates-tab');

            // Create estimate
            $browser->click('button[wire\\:click="create"]')
                ->pause(2000)
                ->screenshot('estimate-journey/03-estimate-wizard-empty');

            // Fill estimate details
            $browser->select('organization_id', $organization->id)
                ->pause(1000)
                ->select('customer_id', $customer->id)
                ->pause(1000)
                ->select('document_type', 'estimate')
                ->screenshot('estimate-journey/04-estimate-wizard-filled');

            // Complete estimate creation (simplified)
            $browser->click('button[wire\\:click="nextStep"]')
                ->pause(2000)
                ->click('button[wire\\:click="nextStep"]')
                ->pause(2000)
                ->type('items.0.description', 'Digital Marketing Strategy')
                ->type('items.0.quantity', '1')
                ->type('items.0.unit_price', '50000.00')
                ->type('items.0.tax_rate', '18')
                ->pause(2000)
                ->screenshot('estimate-journey/05-estimate-with-items');

            // Save estimate
            $browser->click('button[wire\\:click="save"]')
                ->pause(3000)
                ->screenshot('estimate-journey/06-estimate-created');
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
            $browser->visit("/invoices/{$invoice->ulid}")
                ->pause(3000)
                ->screenshot('invoice-journey/10-public-invoice-view');
        });

        // Create estimate for public view
        $estimate = \App\Models\Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'document_type' => 'estimate',
        ]);

        $this->browse(function (Browser $browser) use ($estimate) {
            // Public estimate view
            $browser->visit("/estimates/{$estimate->ulid}")
                ->pause(3000)
                ->screenshot('estimate-journey/07-public-estimate-view');
        });
    }
}