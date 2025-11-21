<?php

use Laravel\Dusk\Browser;
use App\Models\User;
use Tests\DuskTestCase;

class ManualWorkflowProofTest extends DuskTestCase
{
    /**
     * Manual step-by-step proof of complete organization workflow
     */
    public function test_manual_organization_workflow_proof()
    {
        // Create proof directory
        $proofDir = 'tests/Browser/screenshots/manual-workflow-proof';
        if (!is_dir(base_path($proofDir))) {
            mkdir(base_path($proofDir), 0755, true);
        }

        // Create a fresh user
        $timestamp = time();
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Manual Test User',
            'email' => "manual{$timestamp}@example.test"
        ]);

        $this->browse(function (Browser $browser) use ($proofDir, $user) {
            // Login and navigate to manage business
            loginUserInBrowser($browser, $user);
            
            // Step 1: Show initial dashboard
            $browser->visit('/dashboard')
                ->screenshot('manual-workflow-proof/01-initial-dashboard');

            // Step 2: Direct access to manage business (the new UX improvement) 
            $browser->visit('/organization/edit')
                ->waitForText('Manage Your Business', 10)
                ->screenshot('manual-workflow-proof/02-manage-business-form-initial');

            // Step 3: Show current state - personal team without location
            $browser->screenshot('manual-workflow-proof/03-personal-team-no-location-data');

            // Step 4: Let's manually interact with form elements one by one
            // Update organization name
            $browser->with('form', function (Browser $form) {
                $form->clear('input[name="name"]')
                     ->type('input[name="name"]', 'TechCorp Solutions Private Limited');
            })
            ->screenshot('manual-workflow-proof/04-organization-name-updated');

            // Step 5: Add phone number
            $browser->with('form', function (Browser $form) {
                $form->type('input[name="phone"]', '+91-9876543210');
            })
            ->screenshot('manual-workflow-proof/05-phone-number-added');

            // Step 6: Update email
            $browser->with('form', function (Browser $form) {
                $form->clear('input[name="emails.0"]')
                     ->type('input[name="emails.0"]', 'info@techcorp.test');
            })
            ->screenshot('manual-workflow-proof/06-email-updated');

            // Step 7: Select country (this should auto-update currency)
            $browser->with('form', function (Browser $form) {
                $form->select('select[name="country_code"]', 'IN');
            })
            ->pause(1000) // Wait for JavaScript to update currency
            ->screenshot('manual-workflow-proof/07-country-selected-currency-updated');

            // Step 8: Add location name
            $browser->with('form', function (Browser $form) {
                $form->type('input[name="location_name"]', 'Corporate Headquarters');
            })
            ->screenshot('manual-workflow-proof/08-location-name-added');

            // Step 9: Add GSTIN (this is the critical field that was not working before)
            $browser->with('form', function (Browser $form) {
                $form->type('input[name="gstin"]', '29AABCT9603R1ZV');
            })
            ->screenshot('manual-workflow-proof/09-gstin-added');

            // Step 10: Add address line 1
            $browser->with('form', function (Browser $form) {
                $form->type('input[name="address_line_1"]', '123 Technology Park, Electronic City');
            })
            ->screenshot('manual-workflow-proof/10-address-line-1-added');

            // Step 11: Add address line 2  
            $browser->with('form', function (Browser $form) {
                $form->type('input[name="address_line_2"]', 'Phase 1, Building A, Floor 5');
            })
            ->screenshot('manual-workflow-proof/11-address-line-2-added');

            // Step 12: Add city
            $browser->with('form', function (Browser $form) {
                $form->type('input[name="city"]', 'Bangalore');
            })
            ->screenshot('manual-workflow-proof/12-city-added');

            // Step 13: Add state
            $browser->with('form', function (Browser $form) {
                $form->type('input[name="state"]', 'Karnataka');
            })
            ->screenshot('manual-workflow-proof/13-state-added');

            // Step 14: Add postal code
            $browser->with('form', function (Browser $form) {
                $form->type('input[name="postal_code"]', '560100');
            })
            ->screenshot('manual-workflow-proof/14-postal-code-added-form-complete');

            // Step 15: Submit the form
            $browser->press('Update Organization')
                ->waitForText('Organization updated successfully!', 15)
                ->screenshot('manual-workflow-proof/15-form-submitted-success-message');

            // Step 16: Verify the organization appears in the list with all details
            $browser->visit('/organizations')
                ->waitForText('TechCorp Solutions Private Limited', 10)
                ->assertSee('Corporate Headquarters')
                ->assertSee('Bangalore, Karnataka')  
                ->assertSee('+91-9876543210')
                ->screenshot('manual-workflow-proof/16-organization-in-list-with-location');

            // Step 17: Verify data persistence by editing again
            $browser->visit('/organization/edit')
                ->waitForText('Manage Your Business', 10)
                ->screenshot('manual-workflow-proof/17-form-populated-with-saved-data');

            // Step 18: Verify specific field values are correctly saved
            $browser->assertInputValue('name', 'TechCorp Solutions Private Limited')
                ->assertInputValue('phone', '+91-9876543210')
                ->assertInputValue('emails.0', 'info@techcorp.test')
                ->assertInputValue('location_name', 'Corporate Headquarters')
                ->assertInputValue('gstin', '29AABCT9603R1ZV')
                ->assertInputValue('address_line_1', '123 Technology Park, Electronic City')
                ->assertInputValue('city', 'Bangalore')
                ->assertInputValue('state', 'Karnataka')
                ->assertInputValue('postal_code', '560100')
                ->screenshot('manual-workflow-proof/18-all-fields-verified-correctly-saved');

            // Step 19: Test update functionality - change GSTIN and phone
            $browser->with('form', function (Browser $form) {
                $form->clear('input[name="phone"]')
                     ->type('input[name="phone"]', '+91-8765432109')
                     ->clear('input[name="gstin"]')
                     ->type('input[name="gstin"]', '29AABCT9603R1ZK');
            })
            ->screenshot('manual-workflow-proof/19-making-updates-to-phone-and-gstin');

            // Step 20: Save the updates
            $browser->press('Update Organization')
                ->waitForText('Organization updated successfully!', 15)
                ->screenshot('manual-workflow-proof/20-updates-saved-successfully');

            // Step 21: Final verification of updates
            $browser->visit('/organization/edit')
                ->waitForText('Manage Your Business', 10)
                ->assertInputValue('phone', '+91-8765432109')
                ->assertInputValue('gstin', '29AABCT9603R1ZK')
                ->screenshot('manual-workflow-proof/21-updates-verified-final-proof');

            // SUCCESS: All functionality is working correctly
            $this->assertTrue(true, 'Manual workflow proof completed successfully - all organization location updates work correctly including GSTIN');
        });
    }
}