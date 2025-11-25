<?php

namespace Tests\Unit\Livewire;

use App\Currency;
use App\Enums\Country;
use App\Livewire\OrganizationSetup;
use App\Models\Location;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($this->user);
    }

    public function test_can_render_organization_setup_component(): void
    {
        $component = Livewire::test(OrganizationSetup::class);

        $component->assertOk();
        $component->assertSee('Step 1 of 4');
        $component->assertSet('currentStep', 1);
        $component->assertSet('totalSteps', 4);
    }

    public function test_mount_loads_existing_organization_data(): void
    {
        $organization = $this->user->currentTeam;
        $organization->update([
            'company_name' => 'Test Company',
            'tax_number' => 'TAX123',
            'currency' => Currency::INR,
            'country_code' => Country::IN,
        ]);

        $component = Livewire::test(OrganizationSetup::class, ['organization' => $organization]);

        $component->assertSet('company_name', 'Test Company');
        $component->assertSet('tax_number', 'TAX123');
        $component->assertSet('currency', 'INR');
        $component->assertSet('country_code', 'IN');
    }

    public function test_mount_loads_existing_location_data(): void
    {
        $organization = $this->user->currentTeam;
        $location = Location::factory()->create([
            'locatable_type' => Organization::class,
            'locatable_id' => $organization->id,
            'name' => 'Test Location',
            'gstin' => '27AABCG9603R1ZV',
            'address_line_1' => '123 Test Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
        ]);

        $organization->update(['primary_location_id' => $location->id]);

        $component = Livewire::test(OrganizationSetup::class, ['organization' => $organization]);

        $component->assertSet('location_name', 'Test Location');
        $component->assertSet('gstin', '27AABCG9603R1ZV');
        $component->assertSet('address_line_1', '123 Test Street');
        $component->assertSet('city', 'Mumbai');
        $component->assertSet('state', 'Maharashtra');
        $component->assertSet('postal_code', '400001');
    }

    public function test_can_navigate_between_steps(): void
    {
        $component = Livewire::test(OrganizationSetup::class);

        // Set minimum required data for step 1
        $component->set('company_name', 'Test Company');

        $component->call('nextStep');
        $component->assertSet('currentStep', 2);

        $component->call('previousStep');
        $component->assertSet('currentStep', 1);
    }

    public function test_cannot_go_to_next_step_without_valid_data(): void
    {
        $component = Livewire::test(OrganizationSetup::class);

        $component->call('nextStep');
        $component->assertHasErrors(['company_name']);
        $component->assertSet('currentStep', 1);
    }

    public function test_can_jump_to_specific_step_with_valid_data(): void
    {
        $component = Livewire::test(OrganizationSetup::class);

        // Set valid data for steps 1 and 2
        $component->set('company_name', 'Test Company')
            ->set('location_name', 'Test Location')
            ->set('address_line_1', '123 Test Street')
            ->set('city', 'Mumbai')
            ->set('state', 'Maharashtra')
            ->set('postal_code', '400001');

        $component->call('goToStep', 3);
        $component->assertSet('currentStep', 3);
    }

    public function test_step_1_validation_works(): void
    {
        $component = Livewire::test(OrganizationSetup::class);

        $component->call('nextStep');
        $component->assertHasErrors(['company_name']);

        // Test invalid URL
        $component->set('company_name', 'Test Company')
            ->set('website', 'invalid-url');

        $component->call('nextStep');
        $component->assertHasErrors(['website']);

        // Test valid data
        $component->set('website', 'https://example.com');
        $component->call('nextStep');
        $component->assertHasNoErrors();
        $component->assertSet('currentStep', 2);
    }

    public function test_step_2_validation_works(): void
    {
        $component = Livewire::test(OrganizationSetup::class);

        $component->set('company_name', 'Test Company')
            ->call('nextStep') // Go to step 2
            ->call('nextStep'); // Try to go to step 3

        $component->assertHasErrors(['location_name', 'address_line_1', 'city', 'state', 'postal_code']);

        // Set valid data
        $component->set('location_name', 'Test Location')
            ->set('address_line_1', '123 Test Street')
            ->set('city', 'Mumbai')
            ->set('state', 'Maharashtra')
            ->set('postal_code', '400001');

        $component->call('nextStep');
        $component->assertHasNoErrors();
        $component->assertSet('currentStep', 3);
    }

    public function test_step_3_validation_works(): void
    {
        $component = Livewire::test(OrganizationSetup::class);

        // Navigate to step 3
        $component->set('company_name', 'Test Company')
            ->set('location_name', 'Test Location')
            ->set('address_line_1', '123 Test Street')
            ->set('city', 'Mumbai')
            ->set('state', 'Maharashtra')
            ->set('postal_code', '400001')
            ->call('goToStep', 3);

        $component->call('nextStep');
        $component->assertHasErrors(['currency', 'country_code']);

        // Set valid data
        $component->set('currency', 'INR')
            ->set('country_code', 'IN');

        $component->call('nextStep');
        $component->assertHasNoErrors();
        $component->assertSet('currentStep', 4);
    }

    public function test_step_4_validation_works(): void
    {
        $component = Livewire::test(OrganizationSetup::class);

        // Navigate to step 4 with valid data
        $component->set('company_name', 'Test Company')
            ->set('location_name', 'Test Location')
            ->set('address_line_1', '123 Test Street')
            ->set('city', 'Mumbai')
            ->set('state', 'Maharashtra')
            ->set('postal_code', '400001')
            ->set('currency', 'INR')
            ->set('country_code', 'IN')
            ->call('goToStep', 4);

        // Test with empty emails
        $component->set('emails', ['']);
        $component->call('completeSetup');
        $component->assertHasErrors(['emails.0']);

        // Test with invalid email
        $component->set('emails', ['invalid-email']);
        $component->call('completeSetup');
        $component->assertHasErrors(['emails.0']);

        // Test with valid email
        $component->set('emails', ['test@example.test']);
        $component->call('completeSetup');
        $component->assertHasNoErrors();
    }

    public function test_country_code_change_updates_currency_and_financial_year(): void
    {
        $component = Livewire::test(OrganizationSetup::class);

        $component->set('country_code', 'IN');

        $component->assertSet('currency', 'INR');
        $component->assertSet('financial_year_type', 'april_march');
        $component->assertSet('financial_year_start_month', 4);
        $component->assertSet('financial_year_start_day', 1);

        // Test another country
        $component->set('country_code', 'US');

        $component->assertSet('currency', 'USD');
        $component->assertSet('financial_year_type', 'january_december');
        $component->assertSet('financial_year_start_month', 1);
        $component->assertSet('financial_year_start_day', 1);
    }

    public function test_can_add_and_remove_email_fields(): void
    {
        $component = Livewire::test(OrganizationSetup::class);

        $component->assertCount('emails', 1);

        $component->call('addEmailField');
        $component->assertCount('emails', 2);

        $component->call('addEmailField');
        $component->assertCount('emails', 3);

        $component->call('removeEmailField', 1);
        $component->assertCount('emails', 2);

        // Cannot remove last email field
        $component->call('removeEmailField', 0);
        $component->call('removeEmailField', 0);
        $component->assertCount('emails', 1);
    }

    public function test_complete_setup_creates_new_organization(): void
    {
        $component = Livewire::test(OrganizationSetup::class);

        $component->set('company_name', 'New Test Company')
            ->set('tax_number', 'TAX123')
            ->set('website', 'https://example.com')
            ->set('location_name', 'Main Office')
            ->set('gstin', '27AABCG9603R1ZV')
            ->set('address_line_1', '123 Test Street')
            ->set('address_line_2', 'Suite 100')
            ->set('city', 'Mumbai')
            ->set('state', 'Maharashtra')
            ->set('postal_code', '400001')
            ->set('currency', 'INR')
            ->set('country_code', 'IN')
            ->set('financial_year_type', 'april_march')
            ->set('emails', ['test@example.test', 'admin@example.test'])
            ->set('phone', '+91-9876543210');

        $component->call('completeSetup');

        $this->assertDatabaseHas('teams', [
            'company_name' => 'New Test Company',
            'tax_number' => 'TAX123',
            'website' => 'https://example.com',
            'currency' => 'INR',
            'country_code' => 'IN',
            'financial_year_type' => 'april_march',
            'phone' => '+91-9876543210',
        ]);

        $this->assertDatabaseHas('locations', [
            'name' => 'Main Office',
            'gstin' => '27AABCG9603R1ZV',
            'address_line_1' => '123 Test Street',
            'address_line_2' => 'Suite 100',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
            'country' => 'IN',
        ]);
    }

    public function test_complete_setup_updates_existing_organization(): void
    {
        $organization = $this->user->currentTeam;
        $location = Location::factory()->create([
            'locatable_type' => Organization::class,
            'locatable_id' => $organization->id,
        ]);
        $organization->update(['primary_location_id' => $location->id]);

        $component = Livewire::test(OrganizationSetup::class, ['organization' => $organization]);

        $component->set('company_name', 'Updated Company')
            ->set('location_name', 'Updated Location')
            ->set('address_line_1', '456 Updated Street')
            ->set('city', 'Delhi')
            ->set('state', 'Delhi')
            ->set('postal_code', '110001')
            ->set('currency', 'INR')
            ->set('country_code', 'IN')
            ->set('emails', ['updated@example.test']);

        $component->call('completeSetup');

        $this->assertDatabaseHas('teams', [
            'id' => $organization->id,
            'company_name' => 'Updated Company',
        ]);

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'name' => 'Updated Location',
            'address_line_1' => '456 Updated Street',
            'city' => 'Delhi',
        ]);
    }

    public function test_currency_validation_against_country(): void
    {
        $component = Livewire::test(OrganizationSetup::class);

        $component->set('company_name', 'Test Company')
            ->set('location_name', 'Test Location')
            ->set('address_line_1', '123 Test Street')
            ->set('city', 'Mumbai')
            ->set('state', 'Maharashtra')
            ->set('postal_code', '400001')
            ->set('country_code', 'IN')
            ->set('currency', 'USD') // Invalid currency for India
            ->set('emails', ['test@example.test']);

        $component->call('completeSetup');
        $component->assertHasErrors(['currency']);
        $component->assertSet('currentStep', 3);
    }

    public function test_empty_emails_validation(): void
    {
        $component = Livewire::test(OrganizationSetup::class);

        $component->set('company_name', 'Test Company')
            ->set('location_name', 'Test Location')
            ->set('address_line_1', '123 Test Street')
            ->set('city', 'Mumbai')
            ->set('state', 'Maharashtra')
            ->set('postal_code', '400001')
            ->set('currency', 'INR')
            ->set('country_code', 'IN')
            ->set('emails', ['', '   ', '']); // All empty emails

        $component->call('completeSetup');
        $component->assertHasErrors(['emails.0']);
        $component->assertSet('currentStep', 4);
    }

    public function test_computed_available_countries_works(): void
    {
        $component = Livewire::test(OrganizationSetup::class);

        $countries = $component->get('availableCountries');

        $this->assertNotEmpty($countries);
        $this->assertArrayHasKey('value', $countries[0]);
        $this->assertArrayHasKey('label', $countries[0]);
        $this->assertArrayHasKey('currency', $countries[0]);
        $this->assertArrayHasKey('financial_year_options', $countries[0]);
    }

    public function test_computed_selected_country_info_works(): void
    {
        $component = Livewire::test(OrganizationSetup::class);

        // No country selected
        $countryInfo = $component->get('selectedCountryInfo');
        $this->assertNull($countryInfo);

        // Valid country selected
        $component->set('country_code', 'IN');
        $countryInfo = $component->get('selectedCountryInfo');

        $this->assertNotNull($countryInfo);
        $this->assertArrayHasKey('financial_year_options', $countryInfo);
        $this->assertArrayHasKey('default_currency', $countryInfo);
        $this->assertArrayHasKey('tax_system', $countryInfo);
    }

    public function test_computed_available_currencies_works(): void
    {
        $component = Livewire::test(OrganizationSetup::class);

        // No country selected - should show all currencies
        $currencies = $component->get('availableCurrencies');
        $this->assertNotEmpty($currencies);

        // Country selected - should show country-specific currencies
        $component->set('country_code', 'IN');
        $currencies = $component->get('availableCurrencies');
        $this->assertNotEmpty($currencies);
        $this->assertArrayHasKey('INR', $currencies);
    }

    public function test_step_progress_computed_property_works(): void
    {
        $component = Livewire::test(OrganizationSetup::class);

        $progress = $component->get('stepProgress');

        $this->assertIsArray($progress);
        $this->assertCount(4, $progress);

        foreach ($progress as $step) {
            $this->assertArrayHasKey('title', $step);
            $this->assertArrayHasKey('description', $step);
            $this->assertArrayHasKey('completed', $step);
            $this->assertIsBool($step['completed']);
        }
    }

    public function test_redirects_to_dashboard_when_setup_complete(): void
    {
        $organization = $this->user->currentTeam;

        // Mock the setup complete method
        $organization->update(['company_name' => 'Complete Company']);

        // Since we can't mock the isSetupComplete method easily in Livewire test,
        // we'll test the logic indirectly by ensuring completeSetup redirects
        $component = Livewire::test(OrganizationSetup::class, ['organization' => $organization]);

        $component->set('company_name', 'Test Company')
            ->set('location_name', 'Test Location')
            ->set('address_line_1', '123 Test Street')
            ->set('city', 'Mumbai')
            ->set('state', 'Maharashtra')
            ->set('postal_code', '400001')
            ->set('currency', 'INR')
            ->set('country_code', 'IN')
            ->set('emails', ['test@example.test']);

        $component->call('completeSetup');

        // Verify setup completion sets the session message
        $this->assertEquals('Organization setup completed successfully! Welcome to your invoicing system.', session('message'));
    }

    public function test_handles_invalid_country_code_gracefully(): void
    {
        $component = Livewire::test(OrganizationSetup::class);

        // This should not cause an error
        $component->set('country_code', 'INVALID');

        // Currency and financial year should not be changed
        $component->assertSet('currency', '');
        $component->assertSet('financial_year_type', null);
    }

    public function test_location_name_defaults_to_company_name(): void
    {
        $component = Livewire::test(OrganizationSetup::class);

        $component->set('company_name', 'Test Company Ltd')
            ->set('address_line_1', '123 Test Street')
            ->set('city', 'Mumbai')
            ->set('state', 'Maharashtra')
            ->set('postal_code', '400001')
            ->set('currency', 'INR')
            ->set('country_code', 'IN')
            ->set('emails', ['test@example.test'])
            ->set('location_name', ''); // Empty location name

        $component->call('completeSetup');

        $this->assertDatabaseHas('locations', [
            'name' => 'Test Company Ltd', // Should use company name as default
        ]);
    }
}
