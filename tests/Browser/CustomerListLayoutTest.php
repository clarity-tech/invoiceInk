<?php

use App\Models\Customer;
use App\Models\User;
use App\ValueObjects\ContactCollection;

/**
 * Visual snapshot + overflow regression guard for the customer list table.
 *
 * The customer table repeated the defect fixed for the invoice list in f6550132:
 * seven columns at `px-6`, three of them (`Name`, `Email`, `Location`) unbounded and
 * `whitespace-nowrap`, inside a `max-w-7xl` container. The Actions column was pushed
 * off the right edge — `overflow-x-auto` kept it in the DOM but scrolled out of view,
 * so the "Actions" header rendered as "AC" and Delete was clipped on every row.
 *
 * As with the invoice guard, the `scrollWidth <= clientWidth` assertion is the real
 * test. `assertSee` alone passes on the broken layout, because clipped content is
 * still present in the DOM.
 */
it('renders the customer list actions without clipping at desktop width', function () {
    $screenshotDir = base_path('tests/Browser/Screenshots/layout');
    if (! is_dir($screenshotDir)) {
        mkdir($screenshotDir, 0755, true);
    }

    $user = User::factory()->withPersonalTeam()->create([
        'name' => 'Maya Sharma',
        'email' => 'customer-list-layout@example.test',
    ]);

    $org = createOrganizationWithLocation([
        'name' => 'Clarity Technologies',
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ], [], $user);

    // Worst case: the longest name, a long email, a real phone (the seeded demo data
    // uses "-", which understates the width), and a long two-part location summary.
    createCustomerWithLocation([
        'name' => 'Riverbend Institute of Medical Sciences Limited',
        'phone' => '+914012345678',
        'phone_country' => 'IN',
        'emails' => new ContactCollection([['name' => 'Accounts Payable', 'email' => 'accounts.payable@riverbend-hospitals.test']]),
    ], [
        'city' => 'Secunderabad',
        'state' => 'Telangana',
        'country' => 'IN',
    ], $org);

    createCustomerWithLocation([
        'name' => 'BRIGHTCARE HEALTH INDIA PRIVATE LIMITED',
        'phone' => '+914066778899',
        'phone_country' => 'IN',
    ], [
        'city' => 'Bangalore',
        'state' => 'Karnataka',
        'country' => 'IN',
    ], $org);

    // A customer with no locations at all — and no archived ones either, so the
    // disclosure label is unambiguously "Locations (0)". This is the case that had no
    // expander whatsoever before the fix, leaving the location manager unreachable.
    Customer::factory()->create([
        'name' => 'Locationless Trading Company Limited',
        'organization_id' => $org->id,
    ]);

    $this->actingAs($user);

    $page = visit('/customers')->resize(1440, 900);

    $page->assertPathIs('/customers')
        ->assertNoJavaScriptErrors()
        // Confirms the action set renders at all. Note: presence in the DOM does NOT
        // prove the actions are un-clipped — the scrollWidth guard below does that.
        ->assertSee('Actions')
        ->assertSee('Edit')
        ->assertSee('Delete')
        ->assertSee('Riverbend Institute of Medical Sciences Limited')
        // The zero-location customer still offers a way into the location manager.
        ->assertSee('Locations (0)')
        // The real regression guard: the table's scroll container must not overflow
        // horizontally at desktop width (1px tolerance for sub-pixel rounding).
        ->assertScript(
            "function() { const el = document.querySelector('.overflow-x-auto'); return el ? (el.scrollWidth - el.clientWidth) <= 1 : false; }",
        )
        ->screenshot(fullPage: true, filename: 'layout/customer-list-desktop');
});

it('opens the location manager for a customer that has no locations', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'customer-list-empty-locations@example.test',
    ]);

    $org = createOrganizationWithLocation([
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ], [], $user);

    Customer::factory()->create([
        'name' => 'Locationless Trading Company Limited',
        'organization_id' => $org->id,
    ]);

    $this->actingAs($user);

    visit('/customers')
        ->assertSee('Locations (0)')
        ->click('button:has-text("Locations (0)")')
        ->waitForText('No locations yet')
        ->assertSee('+ Add location');
});
