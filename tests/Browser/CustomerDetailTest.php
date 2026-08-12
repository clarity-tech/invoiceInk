<?php

use App\Models\Customer;
use App\Models\Location;
use App\Models\Organization;
use App\Models\User;

/**
 * The customer detail page owns editing and the full location lifecycle. Before it
 * existed, all of this was crammed into an expandable listing row behind an
 * unlabeled chevron that did not render at all when a customer had no locations.
 */
function detailPageOrganization(User $user): Organization
{
    return createOrganizationWithLocation([
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ], [], $user);
}

it('shows customer details and locations', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'customer-detail@example.test',
    ]);

    $org = detailPageOrganization($user);

    $customer = createCustomerWithLocation([
        'name' => 'Riverbend Institute of Medical Sciences Limited',
        'phone' => '+914012345678',
        'phone_country' => 'IN',
        'currency' => 'INR',
    ], [
        'name' => 'Head Office',
        'city' => 'Secunderabad',
        'state' => 'Telangana',
        'country' => 'IN',
    ], $org);

    $this->actingAs($user);

    visit("/customers/{$customer->id}")
        ->assertNoJavaScriptErrors()
        ->assertSee('Riverbend Institute of Medical Sciences Limited')
        ->assertSee('Locations (1)')
        ->assertSee('Head Office')
        ->assertSee('Primary')
        ->assertSee('Secunderabad')
        ->assertSee('Edit Customer');
});

it('adds a location from the detail page', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'customer-detail-add@example.test',
    ]);

    $org = detailPageOrganization($user);

    $customer = Customer::factory()->create([
        'name' => 'Locationless Trading Company',
        'organization_id' => $org->id,
    ]);

    $this->actingAs($user);

    visit("/customers/{$customer->id}")
        ->assertSee('No locations yet')
        ->click('button:has-text("+ Add location")')
        ->waitForText('Add Location')
        ->fill('#location-name', 'Head Office')
        ->select('#location-country', 'IN')
        ->fill('#location-address-line-1', '1 Banjara Hills Road')
        ->fill('#location-city', 'Hyderabad')
        ->fill('#location-state', 'Telangana')
        ->fill('#location-postal-code', '500034')
        // Scoped to the submit button: "+ Add location" also matches loosely.
        ->click('button[type="submit"]:has-text("Add Location")')
        ->waitForText('Locations (1)')
        ->assertSee('Head Office');

    expect($customer->fresh()->locations()->count())->toBe(1);
    // The first location becomes primary automatically.
    expect($customer->fresh()->primary_location_id)->not->toBeNull();
});

it('promotes a second location to primary', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'customer-detail-primary@example.test',
    ]);

    $org = detailPageOrganization($user);
    $customer = createCustomerWithLocation(['name' => 'Two Site Corp'], [
        'name' => 'Head Office',
    ], $org);

    Location::create([
        'name' => 'Branch Office',
        'address_line_1' => '2 Branch Road',
        'city' => 'Chennai',
        'state' => 'Tamil Nadu',
        'country' => 'IN',
        'postal_code' => '600001',
        'locatable_type' => Customer::class,
        'locatable_id' => $customer->id,
    ]);

    $this->actingAs($user);

    visit("/customers/{$customer->id}")
        ->assertSee('Branch Office')
        ->click('button:has-text("Set Primary")')
        ->waitForText('Primary');

    expect($customer->fresh()->primaryLocation->name)->toBe('Branch Office');
});

it('shows archived locations in a collapsed section', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'customer-detail-archived@example.test',
    ]);

    $org = detailPageOrganization($user);
    $customer = createCustomerWithLocation(['name' => 'Archive Corp'], [
        'name' => 'Head Office',
    ], $org);

    $archived = Location::create([
        'name' => 'Old Warehouse',
        'address_line_1' => '9 Disused Road',
        'city' => 'Pune',
        'state' => 'Maharashtra',
        'country' => 'IN',
        'postal_code' => '411001',
        'locatable_type' => Customer::class,
        'locatable_id' => $customer->id,
    ]);
    $archived->delete();

    $this->actingAs($user);

    visit("/customers/{$customer->id}")
        ->assertSee('Archived (1)')
        // Collapsed by default so archived rows do not compete with live ones.
        ->assertDontSee('Old Warehouse')
        ->click('button:has-text("Archived (1)")')
        ->waitForText('Old Warehouse')
        ->assertSee('Restore');
});

it('edits a customer from the detail page', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'customer-detail-edit@example.test',
    ]);

    $org = detailPageOrganization($user);
    $customer = createCustomerWithLocation(['name' => 'Before Rename Ltd'], [], $org);

    $this->actingAs($user);

    visit("/customers/{$customer->id}")
        ->click('button:has-text("Edit Customer")')
        ->waitForText('Edit Customer')
        ->fill('#customer-name', 'After Rename Ltd')
        ->click('button:has-text("Update Customer")')
        ->waitForText('After Rename Ltd');

    expect($customer->fresh()->name)->toBe('After Rename Ltd');
});
