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

class InvoiceJourneyScreenshotsTest extends DuskTestCase
{
    use DatabaseMigrations, TestHelpers;

    public function test_complete_invoice_creation_journey(): void
    {
        // Create user with completed organization setup
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Invoice Manager',
            'email' => 'manager@company.test',
        ]);

        $organization = $user->currentTeam;
        $organization->update([
            'company_name' => 'Professional Services LLC',
            'setup_completed_at' => now(),
        ]);

        // Create location for organization
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
            // Login and navigate to invoices
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->waitFor('.dashboard-content')
                ->screenshot('invoice-journey/01-dashboard-logged-in');

            // Navigate to invoices page
            $browser->click('a[href="/invoices"]')
                ->waitForLocation('/invoices')
                ->waitFor('.invoice-wizard')
                ->screenshot('invoice-journey/02-invoices-page-empty');

            // Start creating new invoice
            $browser->click('[wire:click="create"]')
                ->waitFor('.create-form')
                ->screenshot('invoice-journey/03-invoice-wizard-step1-empty');

            // Fill Step 1: Basic Information
            $browser->select('organization_id', $organization->id)
                ->pause(500)
                ->select('customer_id', $customer->id)
                ->pause(500)
                ->screenshot('invoice-journey/04-invoice-wizard-step1-filled');

            $browser->click('[wire:click="nextStep"]')
                ->waitFor('.step-2')
                ->screenshot('invoice-journey/05-invoice-wizard-step2-locations');

            // Step 2: Locations (auto-populated)
            $browser->click('[wire:click="nextStep"]')
                ->waitFor('.step-3')
                ->screenshot('invoice-journey/06-invoice-wizard-step3-empty');

            // Step 3: Items and Details
            $browser->type('items.0.description', 'Web Development Services')
                ->type('items.0.quantity', '40')
                ->type('items.0.unit_price', '2500.00')
                ->type('items.0.tax_rate', '18')
                ->pause(500) // Allow calculation
                ->screenshot('invoice-journey/07-invoice-wizard-step3-first-item');

            // Add second item
            $browser->click('[wire:click="addItem"]')
                ->type('items.1.description', 'Domain and Hosting Setup')
                ->type('items.1.quantity', '1')
                ->type('items.1.unit_price', '15000.00')
                ->type('items.1.tax_rate', '18')
                ->pause(500) // Allow calculation
                ->screenshot('invoice-journey/08-invoice-wizard-step3-multiple-items');

            // Set invoice details
            $browser->type('invoice_date', '2024-01-15')
                ->type('due_date', '2024-02-15')
                ->type('notes', 'Payment terms: 30 days. Late payment charges may apply.')
                ->screenshot('invoice-journey/09-invoice-wizard-step3-complete');

            // Save invoice
            $browser->click('[wire:click="save"]')
                ->waitFor('.success-message')
                ->screenshot('invoice-journey/10-invoice-created-success');

            // View invoice list
            $browser->waitFor('.invoice-list')
                ->screenshot('invoice-journey/11-invoice-list-with-new-invoice');

            // Edit the invoice
            $browser->click('.edit-invoice-btn:first-of-type')
                ->waitFor('.edit-form')
                ->screenshot('invoice-journey/12-invoice-edit-form');

            // View invoice details/preview
            $browser->click('[wire:click="cancel"]')
                ->waitFor('.invoice-list')
                ->click('.view-invoice-btn:first-of-type')
                ->pause(2000) // Wait for any modal or details view
                ->screenshot('invoice-journey/13-invoice-details-view');

            // Test public invoice view (if implemented)
            $invoice = \App\Models\Invoice::first();
            if ($invoice) {
                $browser->visit("/invoices/{$invoice->ulid}")
                    ->waitFor('.public-invoice')
                    ->screenshot('invoice-journey/14-public-invoice-view');
            }
        });
    }

    public function test_invoice_wizard_validation_and_errors(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'email' => 'test@validation.test',
        ]);

        $organization = $user->currentTeam;
        $organization->update(['setup_completed_at' => now()]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/invoices')
                ->waitFor('.invoice-wizard')
                ->click('[wire:click="create"]')
                ->waitFor('.create-form')
                ->screenshot('invoice-journey/15-validation-empty-form');

            // Try to proceed without filling required fields
            $browser->click('[wire:click="nextStep"]')
                ->waitFor('.error-message')
                ->screenshot('invoice-journey/16-validation-errors-step1');

            // Try to save without items
            $organization = $user->currentTeam;
            $customer = Customer::factory()->create([
                'organization_id' => $organization->id,
                'name' => 'Test Customer',
            ]);

            $browser->select('organization_id', $organization->id)
                ->select('customer_id', $customer->id)
                ->click('[wire:click="nextStep"]')
                ->click('[wire:click="nextStep"]')
                ->click('[wire:click="save"]')
                ->waitFor('.error-message')
                ->screenshot('invoice-journey/17-validation-errors-no-items');
        });
    }
}