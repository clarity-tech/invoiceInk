<?php

use App\Models\Organization;
use App\Models\User;
use App\ValueObjects\ContactCollection;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser tests for organization management functionality.
 * Tests the complete user interface interaction for organization CRUD operations.
 */
class OrganizationManagementTest extends DuskTestCase
{
    use RefreshDatabase;

    /**
     * Test that a user can view the organization management page.
     */
    public function test_user_can_view_organization_page(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'email' => 'test@example.test',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            loginUserInBrowser($browser, $user);

            $browser->visit('/organizations')
                ->assertSee('Organizations')
                ->assertSee('Add Organization')
                ->assertDontSee('Edit Organization');
        });
    }

    /**
     * Test that a user can create a new organization through the UI.
     */
    public function test_user_can_create_organization_through_ui(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'email' => 'creator@example.test',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            loginUserInBrowser($browser, $user);

            $browser->visit('/organizations')
                ->click('Add Organization')
                ->waitFor('form')
                ->assertSee('Add New Organization')

                // Fill organization details
                ->type('input[wire\\:model="name"]', 'Test Organization')
                ->type('input[wire\\:model="phone"]', '+1-555-0123')

                // Select country first (this will auto-set currency)
                ->select('select[wire\\:model\\.live="country_code"]', 'US')
                ->waitFor('select[wire\\:model="currency"]')

                // Fill email
                ->type('input[wire\\:model="emails.0"]', 'contact@testorg.test')

                // Fill location details
                ->type('input[wire\\:model="location_name"]', 'Test HQ')
                ->type('input[wire\\:model="gstin"]', 'GSTIN123456789')
                ->type('input[wire\\:model="address_line_1"]', '123 Test Street')
                ->type('input[wire\\:model="address_line_2"]', 'Suite 100')
                ->type('input[wire\\:model="city"]', 'Test City')
                ->type('input[wire\\:model="state"]', 'Test State')
                ->type('input[wire\\:model="postal_code"]', '12345')

                // Submit form
                ->click('button[type="submit"]')
                ->waitForText('Organization created successfully!')
                ->assertSee('Organization created successfully!')
                ->assertSee('Test Organization');

            // Verify organization was created in database
            $organization = Organization::where('name', 'Test Organization')->first();
            $this->assertNotNull($organization);
            $this->assertEquals('+1-555-0123', $organization->phone);
            $this->assertEquals('contact@testorg.test', $organization->emails->first());
            $this->assertEquals('USD', $organization->currency->value);
            $this->assertNotNull($organization->primaryLocation);
            $this->assertEquals('Test HQ', $organization->primaryLocation->name);
            $this->assertEquals('GSTIN123456789', $organization->primaryLocation->gstin);
        });
    }

    /**
     * Test that a user can edit an existing organization through the UI.
     */
    public function test_user_can_edit_organization_through_ui(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'email' => 'editor@example.test',
        ]);

        // Create an organization to edit
        $organization = createOrganizationWithLocation([
            'name' => 'Original Organization',
            'phone' => '+1-555-0100',
            'emails' => new ContactCollection([['name' => 'Original Contact', 'email' => 'original@test.test']]),
            'currency' => 'USD',
        ], [
            'name' => 'Original Location',
            'gstin' => 'ORIGINAL123',
            'address_line_1' => '100 Original St',
            'city' => 'Original City',
            'state' => 'Original State',
            'country' => 'US',
            'postal_code' => '10000',
        ], $user);

        $this->browse(function (Browser $browser) use ($user, $organization) {
            loginUserInBrowser($browser, $user);

            $browser->visit('/organizations')
                ->assertSee('Original Organization')
                ->clickLink('Edit')
                ->waitFor('form')
                ->assertSee('Edit Organization')

                // Verify form is populated with existing data
                ->assertInputValue('input[wire\\:model="name"]', 'Original Organization')
                ->assertInputValue('input[wire\\:model="phone"]', '+1-555-0100')
                ->assertInputValue('input[wire\\:model="emails.0"]', 'original@test.test')
                ->assertInputValue('input[wire\\:model="location_name"]', 'Original Location')
                ->assertInputValue('input[wire\\:model="gstin"]', 'ORIGINAL123')
                ->assertInputValue('input[wire\\:model="address_line_1"]', '100 Original St')
                ->assertInputValue('input[wire\\:model="city"]', 'Original City')
                ->assertInputValue('input[wire\\:model="state"]', 'Original State')
                ->assertInputValue('input[wire\\:model="postal_code"]', '10000')

                // Make changes to the form
                ->clear('input[wire\\:model="name"]')
                ->type('input[wire\\:model="name"]', 'Updated Organization')
                ->clear('input[wire\\:model="phone"]')
                ->type('input[wire\\:model="phone"]', '+1-555-0200')
                ->clear('input[wire\\:model="emails.0"]')
                ->type('input[wire\\:model="emails.0"]', 'updated@test.test')
                ->clear('input[wire\\:model="location_name"]')
                ->type('input[wire\\:model="location_name"]', 'Updated Location')
                ->clear('input[wire\\:model="gstin"]')
                ->type('input[wire\\:model="gstin"]', 'UPDATED456')
                ->clear('input[wire\\:model="address_line_1"]')
                ->type('input[wire\\:model="address_line_1"]', '200 Updated Ave')
                ->clear('input[wire\\:model="city"]')
                ->type('input[wire\\:model="city"]', 'Updated City')
                ->clear('input[wire\\:model="state"]')
                ->type('input[wire\\:model="state"]', 'Updated State')
                ->clear('input[wire\\:model="postal_code"]')
                ->type('input[wire\\:model="postal_code"]', '20000')

                // Submit the form
                ->click('button[type="submit"]')
                ->waitForText('Organization updated successfully!')
                ->assertSee('Organization updated successfully!')
                ->assertSee('Updated Organization');

            // Verify the organization was updated in the database
            $organization->refresh();
            $this->assertEquals('Updated Organization', $organization->name);
            $this->assertEquals('+1-555-0200', $organization->phone);
            $this->assertEquals('updated@test.test', $organization->emails->first());

            $organization->load('primaryLocation');
            $this->assertEquals('Updated Location', $organization->primaryLocation->name);
            $this->assertEquals('UPDATED456', $organization->primaryLocation->gstin);
            $this->assertEquals('200 Updated Ave', $organization->primaryLocation->address_line_1);
            $this->assertEquals('Updated City', $organization->primaryLocation->city);
            $this->assertEquals('Updated State', $organization->primaryLocation->state);
            $this->assertEquals('20000', $organization->primaryLocation->postal_code);
        });
    }

    /**
     * Test that form validation works correctly in the browser.
     */
    public function test_organization_form_validation_works(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'email' => 'validator@example.test',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            loginUserInBrowser($browser, $user);

            $browser->visit('/organizations')
                ->click('Add Organization')
                ->waitFor('form')

                // Try to submit form without filling required fields
                ->click('button[type="submit"]')
                ->waitForText('The name field is required')

                // Verify validation errors are displayed
                ->assertSee('The name field is required')
                ->assertSee('The currency field is required')
                ->assertSee('The country code field is required')
                ->assertSee('The address line 1 field is required')
                ->assertSee('The city field is required')
                ->assertSee('The state field is required')
                ->assertSee('The postal code field is required');
        });
    }

    /**
     * Test that a user can add multiple email addresses.
     */
    public function test_user_can_add_multiple_emails(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'email' => 'multiemails@example.test',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            loginUserInBrowser($browser, $user);

            $browser->visit('/organizations')
                ->click('Add Organization')
                ->waitFor('form')

                // Fill basic required fields
                ->type('input[wire\\:model="name"]', 'Multi Email Org')
                ->select('select[wire\\:model\\.live="country_code"]', 'US')
                ->waitFor('select[wire\\:model="currency"]')
                ->type('input[wire\\:model="address_line_1"]', '123 Multi St')
                ->type('input[wire\\:model="city"]', 'Multi City')
                ->type('input[wire\\:model="state"]', 'Multi State')
                ->type('input[wire\\:model="postal_code"]', '12345')

                // Add multiple emails
                ->type('input[wire\\:model="emails.0"]', 'primary@multi.test')
                ->click('button:contains("Add another email")')
                ->waitFor('input[wire\\:model="emails.1"]')
                ->type('input[wire\\:model="emails.1"]', 'secondary@multi.test')
                ->click('button:contains("Add another email")')
                ->waitFor('input[wire\\:model="emails.2"]')
                ->type('input[wire\\:model="emails.2"]', 'billing@multi.test')

                // Submit form
                ->click('button[type="submit"]')
                ->waitForText('Organization created successfully!')
                ->assertSee('Organization created successfully!');

            // Verify multiple emails were saved
            $organization = Organization::where('name', 'Multi Email Org')->first();
            $this->assertNotNull($organization);
            $this->assertEquals(3, $organization->emails->count());
            $this->assertTrue($organization->emails->contains('primary@multi.test'));
            $this->assertTrue($organization->emails->contains('secondary@multi.test'));
            $this->assertTrue($organization->emails->contains('billing@multi.test'));
        });
    }

    /**
     * Test error handling when organization update fails.
     */
    public function test_error_handling_when_update_fails(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'email' => 'errortest@example.test',
        ]);

        // Create an organization but then delete it to simulate a failure scenario
        $organization = createOrganizationWithLocation([
            'name' => 'Test Organization',
            'emails' => new ContactCollection([['name' => 'Test Contact', 'email' => 'test@test.test']]),
        ], [], $user);

        $this->browse(function (Browser $browser) use ($user, $organization) {
            loginUserInBrowser($browser, $user);

            // Delete the organization to simulate it being deleted by another process
            $organization->delete();

            $browser->visit('/organizations')
                ->clickLink('Edit')
                ->waitFor('form')

                // Try to make changes and submit
                ->clear('input[wire\\:model="name"]')
                ->type('input[wire\\:model="name"]', 'Updated Name')
                ->click('button[type="submit"]')

                // Should see error message
                ->waitForText('Organization not found')
                ->assertSee('Organization not found');
        });
    }

    /**
     * Test the cancel functionality works correctly.
     */
    public function test_user_can_cancel_organization_form(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'email' => 'canceller@example.test',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            loginUserInBrowser($browser, $user);

            $browser->visit('/organizations')
                ->click('Add Organization')
                ->waitFor('form')
                ->assertSee('Add New Organization')

                // Fill some data
                ->type('input[wire\\:model="name"]', 'Test Organization')
                ->type('input[wire\\:model="phone"]', '+1-555-0123')

                // Cancel the form
                ->click('button:contains("Cancel")')
                ->waitUntilMissing('form')
                ->assertDontSee('Add New Organization')
                ->assertSee('Add Organization');
        });
    }

    /**
     * Test that the form shows loading states correctly.
     */
    public function test_form_shows_loading_states(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'email' => 'loader@example.test',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            loginUserInBrowser($browser, $user);

            $browser->visit('/organizations')
                ->click('Add Organization')
                ->waitFor('form')

                // Fill minimum required fields
                ->type('input[wire\\:model="name"]', 'Loading Test Org')
                ->select('select[wire\\:model\\.live="country_code"]', 'US')
                ->waitFor('select[wire\\:model="currency"]')
                ->type('input[wire\\:model="emails.0"]', 'loading@test.test')
                ->type('input[wire\\:model="address_line_1"]', '123 Loading St')
                ->type('input[wire\\:model="city"]', 'Loading City')
                ->type('input[wire\\:model="state"]', 'Loading State')
                ->type('input[wire\\:model="postal_code"]', '12345')

                // Click submit and verify loading state
                ->click('button[type="submit"]')
                ->assertSee('Creating...')
                ->waitForText('Organization created successfully!')
                ->assertSee('Organization created successfully!');
        });
    }
}
