<?php

namespace Tests\Browser;

use App\Models\Customer;
use App\Models\Location;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Tests\TestHelpers;

class EstimateJourneyScreenshotsTest extends DuskTestCase
{
    use DatabaseMigrations, TestHelpers;

    public function test_complete_estimate_creation_journey(): void
    {
        // Create user with completed organization setup
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Sales Manager',
            'email' => 'sales@company.test',
        ]);

        $organization = $user->currentTeam;
        $organization->update([
            'company_name' => 'Consulting Solutions Ltd',
            'setup_completed_at' => now(),
        ]);

        // Create location for organization
        $orgLocation = Location::factory()->create([
            'locatable_type' => Organization::class,
            'locatable_id' => $organization->id,
            'name' => 'Sales Office',
            'address_line_1' => '321 Business Center',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'country' => 'IN',
            'postal_code' => '110001',
        ]);

        $organization->update(['primary_location_id' => $orgLocation->id]);

        // Create prospective customer
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Future Client Corp',
            'email' => 'procurement@futureclient.test',
        ]);

        $customerLocation = Location::factory()->create([
            'locatable_type' => Customer::class,
            'locatable_id' => $customer->id,
            'name' => 'Client Headquarters',
            'address_line_1' => '555 Corporate Plaza',
            'city' => 'Gurgaon',
            'state' => 'Haryana',
            'country' => 'IN',
            'postal_code' => '122001',
        ]);

        $customer->update(['primary_location_id' => $customerLocation->id]);

        $this->browse(function (Browser $browser) use ($user, $organization, $customer) {
            // Login and navigate to estimates
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->waitFor('.dashboard-content')
                ->screenshot('estimate-journey/01-dashboard-logged-in');

            // Navigate to invoices page (estimates are created in same wizard)
            $browser->click('a[href="/invoices"]')
                ->waitForLocation('/invoices')
                ->waitFor('.invoice-wizard')
                ->screenshot('estimate-journey/02-invoices-page-with-tabs');

            // Switch to estimates view
            $browser->click('[wire:click="$set(\'activeTab\', \'estimates\')"]')
                ->waitFor('.estimates-list')
                ->screenshot('estimate-journey/03-estimates-tab-empty');

            // Start creating new estimate
            $browser->click('[wire:click="create"]')
                ->waitFor('.create-form')
                ->screenshot('estimate-journey/04-estimate-wizard-step1-empty');

            // Fill Step 1: Basic Information
            $browser->select('organization_id', $organization->id)
                ->pause(500)
                ->select('customer_id', $customer->id)
                ->pause(500)
                ->select('document_type', 'estimate') // Select estimate type
                ->screenshot('estimate-journey/05-estimate-wizard-step1-filled');

            $browser->click('[wire:click="nextStep"]')
                ->waitFor('.step-2')
                ->screenshot('estimate-journey/06-estimate-wizard-step2-locations');

            // Step 2: Locations (auto-populated)
            $browser->click('[wire:click="nextStep"]')
                ->waitFor('.step-3')
                ->screenshot('estimate-journey/07-estimate-wizard-step3-empty');

            // Step 3: Items and Details
            $browser->type('items.0.description', 'Digital Marketing Strategy')
                ->type('items.0.quantity', '1')
                ->type('items.0.unit_price', '50000.00')
                ->type('items.0.tax_rate', '18')
                ->pause(500) // Allow calculation
                ->screenshot('estimate-journey/08-estimate-wizard-step3-first-item');

            // Add second item
            $browser->click('[wire:click="addItem"]')
                ->type('items.1.description', 'Social Media Management (6 months)')
                ->type('items.1.quantity', '6')
                ->type('items.1.unit_price', '12000.00')
                ->type('items.1.tax_rate', '18')
                ->pause(500) // Allow calculation
                ->screenshot('estimate-journey/09-estimate-wizard-step3-multiple-items');

            // Add third item for comprehensive estimate
            $browser->click('[wire:click="addItem"]')
                ->type('items.2.description', 'SEO Optimization & Analytics Setup')
                ->type('items.2.quantity', '1')
                ->type('items.2.unit_price', '25000.00')
                ->type('items.2.tax_rate', '18')
                ->pause(500) // Allow calculation
                ->screenshot('estimate-journey/10-estimate-wizard-step3-comprehensive');

            // Set estimate details
            $browser->type('invoice_date', '2024-01-20')
                ->type('due_date', '2024-02-20')
                ->type('notes', 'This estimate is valid for 30 days. Project timeline: 3-4 months.')
                ->screenshot('estimate-journey/11-estimate-wizard-step3-complete');

            // Save estimate
            $browser->click('[wire:click="save"]')
                ->waitFor('.success-message')
                ->screenshot('estimate-journey/12-estimate-created-success');

            // View estimates list
            $browser->waitFor('.estimates-list')
                ->screenshot('estimate-journey/13-estimates-list-with-new-estimate');

            // Edit the estimate
            $browser->click('.edit-estimate-btn:first-of-type')
                ->waitFor('.edit-form')
                ->screenshot('estimate-journey/14-estimate-edit-form');

            // View estimate details/preview
            $browser->click('[wire:click="cancel"]')
                ->waitFor('.estimates-list')
                ->click('.view-estimate-btn:first-of-type')
                ->pause(2000) // Wait for any modal or details view
                ->screenshot('estimate-journey/15-estimate-details-view');

            // Test public estimate view (if implemented)
            $estimate = \App\Models\Invoice::where('document_type', 'estimate')->first();
            if ($estimate) {
                $browser->visit("/estimates/{$estimate->ulid}")
                    ->waitFor('.public-estimate')
                    ->screenshot('estimate-journey/16-public-estimate-view');
            }
        });
    }

    public function test_estimate_to_invoice_conversion(): void
    {
        // Create user and estimate
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Conversion Manager',
            'email' => 'convert@company.test',
        ]);

        $organization = $user->currentTeam;
        $organization->update(['setup_completed_at' => now()]);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Converting Customer',
        ]);

        // Create an estimate first
        $this->browse(function (Browser $browser) use ($user, $organization, $customer) {
            $browser->loginAs($user)
                ->visit('/invoices');

            // Create estimate quickly
            $browser->click('[wire:click="$set(\'activeTab\', \'estimates\')"]')
                ->click('[wire:click="create"]')
                ->select('organization_id', $organization->id)
                ->select('customer_id', $customer->id)
                ->select('document_type', 'estimate')
                ->click('[wire:click="nextStep"]')
                ->click('[wire:click="nextStep"]')
                ->type('items.0.description', 'Consulting Services')
                ->type('items.0.quantity', '10')
                ->type('items.0.unit_price', '5000.00')
                ->type('items.0.tax_rate', '18')
                ->click('[wire:click="save"]')
                ->waitFor('.success-message');

            // Screenshot the estimate ready for conversion
            $browser->screenshot('estimate-journey/17-estimate-ready-for-conversion');

            // Convert to invoice (if conversion feature exists)
            $browser->click('.convert-to-invoice-btn:first-of-type')
                ->waitFor('.conversion-confirmation')
                ->screenshot('estimate-journey/18-estimate-conversion-confirmation');

            // Confirm conversion
            $browser->click('[wire:click="confirmConversion"]')
                ->waitFor('.success-message')
                ->screenshot('estimate-journey/19-estimate-converted-to-invoice');
        });
    }

    public function test_estimate_status_workflow(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'email' => 'status@company.test',
        ]);

        $organization = $user->currentTeam;
        $organization->update(['setup_completed_at' => now()]);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Status Customer',
        ]);

        $this->browse(function (Browser $browser) use ($user, $organization, $customer) {
            $browser->loginAs($user)
                ->visit('/invoices')
                ->click('[wire:click="$set(\'activeTab\', \'estimates\')"]');

            // Create estimate
            $browser->click('[wire:click="create"]')
                ->select('organization_id', $organization->id)
                ->select('customer_id', $customer->id)
                ->select('document_type', 'estimate')
                ->click('[wire:click="nextStep"]')
                ->click('[wire:click="nextStep"]')
                ->type('items.0.description', 'Status Test Service')
                ->type('items.0.quantity', '1')
                ->type('items.0.unit_price', '10000.00')
                ->type('items.0.tax_rate', '18')
                ->click('[wire:click="save"]')
                ->waitFor('.success-message');

            // Show different estimate statuses
            $browser->screenshot('estimate-journey/20-estimate-draft-status');

            // Update status if status management exists
            if ($browser->element('.status-dropdown')) {
                $browser->click('.status-dropdown:first-of-type')
                    ->click('[data-status="sent"]')
                    ->screenshot('estimate-journey/21-estimate-sent-status');

                $browser->click('.status-dropdown:first-of-type')
                    ->click('[data-status="approved"]')
                    ->screenshot('estimate-journey/22-estimate-approved-status');
            }
        });
    }
}