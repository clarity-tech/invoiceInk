<?php

use Laravel\Dusk\Browser;
use App\Models\User;
use Tests\DuskTestCase;

class CompleteUserJourneyTest extends DuskTestCase
{
    /**
     * Test complete user journey from login to organization setup
     * This test captures screenshots at every major step
     */
    public function test_complete_new_user_registration_and_organization_setup_journey()
    {
        // Create screenshots directory for this test
        $screenshotDir = 'tests/Browser/screenshots/complete-user-journey';
        if (!is_dir(base_path($screenshotDir))) {
            mkdir(base_path($screenshotDir), 0755, true);
        }

        // Create a fresh user with personal team (simulating new registration)
        $timestamp = time();
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'New Journey User',
            'email' => "newjourney{$timestamp}@example.test"
        ]);

        $this->browse(function (Browser $browser) use ($screenshotDir, $user) {
            // Step 1: Login as new user
            loginUserInBrowser($browser, $user);
            $browser->visit('/dashboard')
                ->screenshot('complete-user-journey/01-after-login-dashboard');

            // Step 2: Visit profile to show user info
            $browser->visit('/user/profile')
                ->screenshot('complete-user-journey/02-user-profile-page');

            // Step 3: Navigate to organizations page
            $browser->visit('/organizations')
                ->waitForText('Organizations', 5)
                ->screenshot('complete-user-journey/03-organizations-page-initial');

            // Step 4: First, let's edit the existing personal organization to add location details
            $browser->clickLink('Edit')
                ->waitForText('Edit Organization', 3)
                ->screenshot('complete-user-journey/04-edit-existing-organization-empty');

            // Step 5: View the current organization details (personal team without location)
            $browser->screenshot('complete-user-journey/05-personal-org-no-location');

            // Step 6: Update organization name and add phone
            $browser->clear('name')
                ->type('name', 'My Test Business Company')
                ->type('phone', '+91-9876543210')
                ->screenshot('complete-user-journey/06-basic-details-updated');

            // Step 7: Add business email address
            $browser->clear('emails[0]')
                ->type('emails[0]', 'contact@mytestbusiness.test')
                ->screenshot('complete-user-journey/07-email-updated');

            // Step 8: Select country and currency for business operations
            $browser->select('country_code', 'IN')
                ->waitFor('select[name="currency"]', 2)
                ->screenshot('complete-user-journey/08-country-selected-currency-auto-set');

            // Step 9: Fill location details - name and GSTIN
            $browser->type('location_name', 'Head Office Mumbai')
                ->type('gstin', '27AABCU9603R1ZX')
                ->screenshot('complete-user-journey/09-location-name-gstin-filled');

            // Step 10: Fill address details
            $browser->type('address_line_1', '123 Business Park Complex')
                ->type('address_line_2', 'Tower A, Floor 5, Unit 501')
                ->screenshot('complete-user-journey/10-address-lines-filled');

            // Step 11: Fill city and state
            $browser->type('city', 'Mumbai')
                ->type('state', 'Maharashtra')
                ->screenshot('complete-user-journey/11-city-state-filled');

            // Step 12: Fill postal code
            $browser->type('postal_code', '400001')
                ->screenshot('complete-user-journey/12-postal-code-filled-form-complete');

            // Step 13: Submit the update
            $browser->press('Update Organization')
                ->waitForText('Organization updated successfully!', 5)
                ->screenshot('complete-user-journey/13-organization-updated-success');

            // Step 14: Verify updated organization appears in list with location
            $browser->assertSee('My Test Business Company')
                ->assertSee('Head Office Mumbai')
                ->assertSee('Mumbai, Maharashtra')
                ->screenshot('complete-user-journey/14-updated-organization-in-list');

            // Step 15: Click edit again to verify all data was saved correctly
            $browser->clickLink('Edit')
                ->waitForText('Edit Organization', 3)
                ->screenshot('complete-user-journey/15-edit-form-populated-with-data');

            // Step 16: Verify all form fields are populated correctly
            $browser->assertInputValue('name', 'My Test Business Company')
                ->assertInputValue('phone', '+91-9876543210')
                ->assertInputValue('emails[0]', 'contact@mytestbusiness.test')
                ->assertSelected('country_code', 'IN')
                ->assertSelected('currency', 'INR')
                ->screenshot('complete-user-journey/16-basic-fields-verified');

            // Step 17: Verify location fields are populated correctly
            $browser->assertInputValue('location_name', 'Head Office Mumbai')
                ->assertInputValue('gstin', '27AABCU9603R1ZX')
                ->assertInputValue('address_line_1', '123 Business Park Complex')
                ->assertInputValue('address_line_2', 'Tower A, Floor 5, Unit 501')
                ->assertInputValue('city', 'Mumbai')
                ->assertInputValue('state', 'Maharashtra')
                ->assertInputValue('postal_code', '400001')
                ->screenshot('complete-user-journey/17-location-fields-verified');

            // Step 18: Make additional updates to test update functionality
            $browser->clear('phone')
                ->type('phone', '+91-8765432109')
                ->clear('gstin')
                ->type('gstin', '29AABCU9603R1ZV')
                ->screenshot('complete-user-journey/18-making-additional-updates');

            // Step 19: Submit the additional updates
            $browser->press('Update Organization')
                ->waitForText('Organization updated successfully!', 5)
                ->screenshot('complete-user-journey/19-additional-updates-success');

            // Step 20: Verify the new updates were saved
            $browser->clickLink('Edit')
                ->waitForText('Edit Organization', 3)
                ->assertInputValue('phone', '+91-8765432109')
                ->assertInputValue('gstin', '29AABCU9603R1ZV')
                ->screenshot('complete-user-journey/20-additional-updates-verified');

            // Step 21: Test the "Manage Your Business" direct edit flow
            $browser->visit('/organization/edit')
                ->waitForText('Manage Your Business', 5)
                ->screenshot('complete-user-journey/21-manage-business-direct-edit');

            // Step 22: Update business name via direct edit
            $browser->clear('name')
                ->type('name', 'My Updated Business Company Ltd')
                ->clear('address_line_2')
                ->type('address_line_2', 'Tower B, Floor 6, Unit 602')
                ->screenshot('complete-user-journey/22-direct-edit-updates');

            // Step 23: Save via direct edit
            $browser->press('Update Organization')
                ->waitForText('Organization updated successfully!', 5)
                ->screenshot('complete-user-journey/23-direct-edit-success');

            // Step 24: Navigate back to dashboard to see the updated organization
            $browser->visit('/dashboard')
                ->assertSee('My Updated Business Company Ltd')
                ->screenshot('complete-user-journey/24-dashboard-with-updated-org');

            // Step 25: Final verification - check organizations list
            $browser->visit('/organizations')
                ->assertSee('My Updated Business Company Ltd')
                ->assertSee('Head Office Mumbai')
                ->assertSee('+91-8765432109')
                ->screenshot('complete-user-journey/25-final-organizations-list');

            // Log completion
            $this->assertTrue(true, 'Complete user journey test completed successfully');
        });
    }

    /**
     * Test user journey with validation errors and recovery
     */
    public function test_user_journey_with_validation_errors_and_recovery()
    {
        $screenshotDir = 'tests/Browser/screenshots/user-journey-validation';
        if (!is_dir(base_path($screenshotDir))) {
            mkdir(base_path($screenshotDir), 0755, true);
        }

        // Create a user with personal team to test the validation flow
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Validation Test User',
            'email' => 'validation@example.test'
        ]);

        $this->browse(function (Browser $browser) use ($user, $screenshotDir) {
            // Step 1: Login as the test user
            loginUserInBrowser($browser, $user);
            $browser->visit('/organizations')
                ->screenshot('user-journey-validation/01-logged-in-organizations');

            // Step 2: Try to create organization with missing required fields
            $browser->click('button:contains("Create Organization")')
                ->waitForText('Create Organization', 3)
                ->type('name', 'Test Company')
                ->screenshot('user-journey-validation/02-minimal-form-data');

            // Step 3: Submit with missing fields to trigger validation
            $browser->press('Create Organization')
                ->waitForText('required', 3) // Wait for validation errors
                ->screenshot('user-journey-validation/03-validation-errors-shown');

            // Step 4: Fill required fields one by one
            $browser->type('emails[0]', 'test@company.test')
                ->select('country_code', 'IN')
                ->screenshot('user-journey-validation/04-filling-required-fields');

            // Step 5: Fill location details
            $browser->type('address_line_1', '456 Test Street')
                ->type('city', 'Test City')
                ->type('state', 'Test State')
                ->type('postal_code', '123456')
                ->screenshot('user-journey-validation/05-location-fields-filled');

            // Step 6: Submit the corrected form
            $browser->press('Create Organization')
                ->waitForText('Organization created successfully!', 5)
                ->screenshot('user-journey-validation/06-validation-errors-resolved');

            // Step 7: Test email validation
            $browser->click('button:contains("Edit")')
                ->waitForText('Edit Organization', 3)
                ->clear('emails[0]')
                ->type('emails[0]', 'invalid-email-format')
                ->screenshot('user-journey-validation/07-invalid-email-entered');

            // Step 8: Submit with invalid email
            $browser->press('Update Organization')
                ->waitForText('email', 3) // Wait for email validation error
                ->screenshot('user-journey-validation/08-email-validation-error');

            // Step 9: Fix email and submit successfully
            $browser->clear('emails[0]')
                ->type('emails[0]', 'corrected@company.test')
                ->press('Update Organization')
                ->waitForText('Organization updated successfully!', 5)
                ->screenshot('user-journey-validation/09-email-validation-resolved');
        });
    }

    /**
     * Test multiple organizations management workflow
     */
    public function test_multiple_organizations_management_workflow()
    {
        $screenshotDir = 'tests/Browser/screenshots/multiple-organizations';
        if (!is_dir(base_path($screenshotDir))) {
            mkdir(base_path($screenshotDir), 0755, true);
        }

        // Create a user with personal team
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Multi Org User',
            'email' => 'multiorg@example.test'
        ]);

        $this->browse(function (Browser $browser) use ($user, $screenshotDir) {
            // Step 1: Login and navigate to organizations
            loginUserInBrowser($browser, $user);
            $browser->visit('/organizations')
                ->screenshot('multiple-organizations/01-initial-organizations-page');

            // Step 2: Create first business organization
            $browser->click('button:contains("Create Organization")')
                ->waitForText('Create Organization', 3)
                ->type('name', 'Tech Startup Inc')
                ->type('emails[0]', 'info@techstartup.test')
                ->select('country_code', 'US')
                ->type('location_name', 'Silicon Valley Office')
                ->type('address_line_1', '123 Innovation Drive')
                ->type('city', 'San Francisco')
                ->type('state', 'California')
                ->type('postal_code', '94105')
                ->screenshot('multiple-organizations/02-first-org-form-filled')
                ->press('Create Organization')
                ->waitForText('Organization created successfully!', 5)
                ->screenshot('multiple-organizations/03-first-org-created');

            // Step 3: Create second business organization
            $browser->click('button:contains("Create Organization")')
                ->waitForText('Create Organization', 3)
                ->type('name', 'Consulting Services Ltd')
                ->type('emails[0]', 'contact@consulting.test')
                ->select('country_code', 'IN')
                ->type('location_name', 'Mumbai Office')
                ->type('gstin', '27AABCU9603R1ZX')
                ->type('address_line_1', '456 Business Park')
                ->type('city', 'Mumbai')
                ->type('state', 'Maharashtra')
                ->type('postal_code', '400001')
                ->screenshot('multiple-organizations/04-second-org-form-filled')
                ->press('Create Organization')
                ->waitForText('Organization created successfully!', 5)
                ->screenshot('multiple-organizations/05-second-org-created');

            // Step 4: View all organizations
            $browser->assertSee('Tech Startup Inc')
                ->assertSee('Consulting Services Ltd')
                ->assertSee('Silicon Valley Office')
                ->assertSee('Mumbai Office')
                ->screenshot('multiple-organizations/06-multiple-orgs-listed');

            // Step 5: Switch between editing different organizations
            $browser->click('tr:contains("Tech Startup Inc") button:contains("Edit")')
                ->waitForText('Edit Organization', 3)
                ->assertInputValue('name', 'Tech Startup Inc')
                ->assertInputValue('city', 'San Francisco')
                ->screenshot('multiple-organizations/07-editing-first-org');

            // Step 6: Cancel and edit second organization
            $browser->press('Cancel')
                ->waitUntilMissing('.modal', 3)
                ->click('tr:contains("Consulting Services Ltd") button:contains("Edit")')
                ->waitForText('Edit Organization', 3)
                ->assertInputValue('name', 'Consulting Services Ltd')
                ->assertInputValue('gstin', '27AABCU9603R1ZX')
                ->assertInputValue('city', 'Mumbai')
                ->screenshot('multiple-organizations/08-editing-second-org');

            // Step 7: Update second organization
            $browser->clear('phone')
                ->type('phone', '+91-9876543210')
                ->press('Update Organization')
                ->waitForText('Organization updated successfully!', 5)
                ->screenshot('multiple-organizations/09-second-org-updated');

            // Step 8: Verify the update in the list
            $browser->assertSee('+91-9876543210')
                ->screenshot('multiple-organizations/10-updated-phone-visible');
        });
    }
}