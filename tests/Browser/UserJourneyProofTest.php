<?php

use Laravel\Dusk\Browser;
use App\Models\User;
use Tests\DuskTestCase;

class UserJourneyProofTest extends DuskTestCase
{
    /**  
     * Demonstrate complete organization creation and update workflow with screenshots
     */
    public function test_complete_organization_workflow_with_proof_screenshots()
    {
        // Create proof screenshots directory
        $screenshotDir = 'tests/Browser/screenshots/user-journey-proof';
        if (!is_dir(base_path($screenshotDir))) {
            mkdir(base_path($screenshotDir), 0755, true);
        }

        // Create a new user to simulate fresh user experience
        $timestamp = time();
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Proof Test User',
            'email' => "prooftest{$timestamp}@example.test"
        ]);

        $this->browse(function (Browser $browser) use ($screenshotDir, $user) {
            // STEP 1: Login and show dashboard
            loginUserInBrowser($browser, $user);
            $browser->visit('/dashboard')
                ->screenshot('user-journey-proof/01-dashboard-after-login');

            // STEP 2: Navigate to organizations
            $browser->visit('/organizations')
                ->waitForText('Organizations', 5)
                ->screenshot('user-journey-proof/02-organizations-page-with-personal-team');

            // STEP 3: Use "Manage Your Business" direct edit functionality
            $browser->visit('/organization/edit')
                ->waitForText('Manage Your Business', 5)
                ->screenshot('user-journey-proof/03-manage-business-auto-edit-form');

            // STEP 4: Fill in the business details step by step
            $browser->clear('name')
                ->type('name', 'Amazing Tech Solutions Pvt Ltd')
                ->screenshot('user-journey-proof/04-business-name-updated');

            // STEP 5: Add contact details
            $browser->type('phone', '+91-9876543210')
                ->clear('emails[0]')
                ->type('emails[0]', 'contact@amazingtech.test')
                ->screenshot('user-journey-proof/05-contact-details-added');

            // STEP 6: Set country and currency
            $browser->select('country_code', 'IN')
                ->waitFor('select[name="currency"]', 2)
                ->screenshot('user-journey-proof/06-country-currency-selected');

            // STEP 7: Add location details
            $browser->type('location_name', 'Head Office - Bangalore')
                ->type('gstin', '29AABCU9603R1ZV')
                ->screenshot('user-journey-proof/07-location-name-gstin-added');

            // STEP 8: Add complete address
            $browser->type('address_line_1', '123 Tech Park, Electronic City')
                ->type('address_line_2', 'Phase 1, Tower B, Floor 5')
                ->type('city', 'Bangalore')
                ->type('state', 'Karnataka')
                ->type('postal_code', '560100')
                ->screenshot('user-journey-proof/08-complete-address-filled');

            // STEP 9: Submit the form and save
            $browser->press('Update Organization')
                ->waitForText('Organization updated successfully!', 10)
                ->screenshot('user-journey-proof/09-organization-updated-success');

            // STEP 10: Verify in organizations list
            $browser->visit('/organizations')
                ->assertSee('Amazing Tech Solutions Pvt Ltd')
                ->assertSee('Head Office - Bangalore')
                ->assertSee('Bangalore, Karnataka')
                ->assertSee('+91-9876543210')
                ->screenshot('user-journey-proof/10-updated-organization-in-list');

            // STEP 11: Test editing to verify data persistence
            $browser->visit('/organization/edit')
                ->waitForText('Manage Your Business', 5)
                ->assertInputValue('name', 'Amazing Tech Solutions Pvt Ltd')
                ->assertInputValue('phone', '+91-9876543210')
                ->assertInputValue('emails[0]', 'contact@amazingtech.test')
                ->assertInputValue('location_name', 'Head Office - Bangalore')
                ->assertInputValue('gstin', '29AABCU9603R1ZV')
                ->assertInputValue('address_line_1', '123 Tech Park, Electronic City')
                ->assertInputValue('address_line_2', 'Phase 1, Tower B, Floor 5')
                ->assertInputValue('city', 'Bangalore')
                ->assertInputValue('state', 'Karnataka')
                ->assertInputValue('postal_code', '560100')
                ->screenshot('user-journey-proof/11-edit-form-populated-correctly');

            // STEP 12: Make additional changes to test updates
            $browser->clear('phone')
                ->type('phone', '+91-8765432109')
                ->clear('gstin')
                ->type('gstin', '29AABCU9603R1ZK')
                ->screenshot('user-journey-proof/12-making-changes-phone-gstin');

            // STEP 13: Save the changes
            $browser->press('Update Organization')
                ->waitForText('Organization updated successfully!', 10)
                ->screenshot('user-journey-proof/13-changes-saved-successfully');

            // STEP 14: Verify changes were saved
            $browser->visit('/organization/edit')
                ->waitForText('Manage Your Business', 5)
                ->assertInputValue('phone', '+91-8765432109')
                ->assertInputValue('gstin', '29AABCU9603R1ZK')
                ->screenshot('user-journey-proof/14-changes-verified-in-form');

            // STEP 15: Final verification in organizations list
            $browser->visit('/organizations')
                ->assertSee('Amazing Tech Solutions Pvt Ltd')
                ->assertSee('+91-8765432109')
                ->screenshot('user-journey-proof/15-final-verification-organizations-list');

            // STEP 16: Show dashboard with updated organization
            $browser->visit('/dashboard')
                ->assertSee('Amazing Tech Solutions Pvt Ltd')
                ->screenshot('user-journey-proof/16-dashboard-with-updated-organization');

            // Test passes if we reach here
            $this->assertTrue(true, 'Complete organization workflow completed successfully');
        });
    }

    /**
     * Demonstrate adding a new organization (testing the Add Organization button)
     */
    public function test_adding_new_organization_workflow()
    {
        $screenshotDir = 'tests/Browser/screenshots/new-org-workflow';
        if (!is_dir(base_path($screenshotDir))) {
            mkdir(base_path($screenshotDir), 0755, true);
        }

        $timestamp = time();
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Multi Org User',
            'email' => "multiorg{$timestamp}@example.test"
        ]);

        $this->browse(function (Browser $browser) use ($screenshotDir, $user) {
            // Login and navigate to organizations
            loginUserInBrowser($browser, $user);
            $browser->visit('/organizations')
                ->waitForText('Organizations', 5)
                ->screenshot('new-org-workflow/01-organizations-page-initial');

            // Click Add Organization button
            $browser->click('button:contains("Add Organization")')
                ->waitForText('Create Organization', 5)
                ->screenshot('new-org-workflow/02-create-organization-form');

            // Fill in new organization details
            $browser->type('name', 'Second Business Company')
                ->type('phone', '+1-555-0123')
                ->type('emails[0]', 'info@secondbusiness.test')
                ->select('country_code', 'US')
                ->waitFor('select[name="currency"]', 2)
                ->screenshot('new-org-workflow/03-basic-details-filled');

            // Fill location details
            $browser->type('location_name', 'New York Office')
                ->type('address_line_1', '456 Business Avenue')
                ->type('city', 'New York')
                ->type('state', 'New York')
                ->type('postal_code', '10001')
                ->screenshot('new-org-workflow/04-location-details-filled');

            // Create the organization
            $browser->press('Create Organization')
                ->waitForText('Organization created successfully!', 10)
                ->screenshot('new-org-workflow/05-organization-created-success');

            // Verify both organizations exist
            $browser->assertSee('Second Business Company')
                ->assertSee('New York Office')
                ->screenshot('new-org-workflow/06-multiple-organizations-listed');

            $this->assertTrue(true, 'New organization creation workflow completed');
        });
    }
}