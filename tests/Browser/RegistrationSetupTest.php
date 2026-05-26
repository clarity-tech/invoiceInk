<?php

use App\Models\Organization;
use App\Models\User;

/**
 * Create a user with a non-personal team that needs setup.
 *
 * @return array{0: User, 1: Organization}
 */
function createSetupUser(string $email, string $name = 'Maya Sharma'): array
{
    $user = User::factory()->withPersonalTeam()->create([
        'name' => $name,
        'email' => $email,
        'password' => 'password',
    ]);

    $org = $user->currentTeam;
    $org->update([
        'setup_completed_at' => null,
        'company_name' => null,
    ]);

    return [$user, $org];
}

beforeEach(function () {
    $demoDir = base_path('tests/Browser/Screenshots/demo');
    if (! is_dir($demoDir)) {
        mkdir($demoDir, 0755, true);
    }
});

// ─── Test 1: Full registration flow ───

it('completes full registration flow', function () {
    $page = $this->visit('/register');

    $page->assertSee('Register')
        ->screenshot(fullPage: true, filename: 'demo/12-register-form');

    $page->fill('#name', 'Maya Sharma')
        ->fill('#email', 'accounts@claritytech.io')
        ->fill('#password', 'Password123!')
        ->fill('#password_confirmation', 'Password123!')
        ->check('#terms')
        ->screenshot(fullPage: true, filename: 'demo/13-register-filled')
        ->click('Register')
        ->assertPathIs('/email/verify')
        ->screenshot(fullPage: true, filename: 'demo/14-register-verify-email');
});

// ─── Test 2: Non-personal team redirects to setup wizard ───

it('redirects non-personal team to setup wizard', function () {
    [$user] = createSetupUser('setup-redirect@example.test', 'Priya Sharma');

    $this->actingAs($user);

    $this->visit('/dashboard')
        ->assertPathIs('/organization/setup')
        ->assertSee('Organization Setup')
        ->screenshot(fullPage: true, filename: 'demo/15-setup-redirect');
});

// ─── Test 3: Full Indian company setup with all fields ───

it('completes Indian company setup with all fields', function () {
    [$user] = createSetupUser('setup-india@example.test', 'Maya Sharma');

    $this->actingAs($user);

    $page = $this->visit('/organization/setup');

    // Step 1: Company Information (empty)
    $page->assertSee('Company Information')
        ->screenshot(fullPage: true, filename: 'demo/16-setup-step1-empty');

    // Step 1: Fill all fields
    $page->fill('#company_name', 'Clarity Technologies Pvt Ltd')
        ->fill('#tax_number', '29AAFCC1234A1ZV')
        ->fill('#registration_number', 'U72200KA2020PTC123456')
        ->fill('#website', 'https://claritytech.io')
        ->fill('#notes', 'Indian IT services company specializing in web development')
        ->screenshot(fullPage: true, filename: 'demo/17-setup-step1-filled')
        ->click('Next');

    // Step 2: Primary Location - fill all fields including GSTIN
    $page->assertSee('Primary Location')
        ->fill('#location_name', 'Head Office')
        ->fill('#gstin', '29AAFCC1234A1ZV')
        ->fill('#address_line_1', '42 MG Road, Indiranagar')
        ->fill('#address_line_2', '2nd Floor, Prestige Tower')
        ->fill('#city', 'Bangalore')
        ->fill('#state', 'Karnataka')
        ->fill('#postal_code', '560038')
        ->screenshot(fullPage: true, filename: 'demo/18-setup-step2-filled')
        ->click('Next');

    // Step 3: Configuration - select India, assert GST info panel
    $page->assertSee('Configuration')
        ->select('#country_code', 'IN')
        ->assertSee('GST')
        ->select('#currency', 'INR')
        ->screenshot(fullPage: true, filename: 'demo/19-setup-step3-india')
        ->click('Next');

    // Step 4: Contact Details - emails and phone
    $page->assertSee('Contact Details')
        ->fill('input[aria-describedby="error-emails-0"]', 'accounts@claritytech.io')
        ->click('button.text-blue-600')
        ->fill('input[aria-describedby="error-emails-1"]', 'maya@example.test')
        ->fill('#phone', '+91-9876543210')
        ->screenshot(fullPage: true, filename: 'demo/20-setup-step4-filled')
        ->click('Complete Setup')
        ->waitForEvent('networkidle');

    // After Complete Setup, navigate to dashboard to verify setup was saved
    $this->visit('/dashboard')
        ->assertPathIs('/dashboard')
        ->screenshot(fullPage: true, filename: 'demo/21-setup-complete');
});

// ─── Test 4: UAE company setup with different currency ───

it('completes UAE company setup with different currency', function () {
    [$user] = createSetupUser('setup-uae@example.test', 'Ahmed Al Maktoum');

    $this->actingAs($user);

    $page = $this->visit('/organization/setup');

    // Step 1: Company Information
    $page->fill('#company_name', 'Dubai Trading LLC')
        ->fill('#tax_number', '100234567890003')
        ->fill('#registration_number', 'DXB-LLC-2024-001')
        ->screenshot(fullPage: true, filename: 'demo/22-setup-uae-step1')
        ->click('Next');

    // Step 2: Location (no GSTIN for UAE)
    $page->fill('#location_name', 'Dubai Office')
        ->fill('#address_line_1', 'Dubai Healthcare City')
        ->fill('#address_line_2', 'Building 64, Block A')
        ->fill('#city', 'Dubai')
        ->fill('#state', 'Dubai')
        ->fill('#postal_code', '505055')
        ->click('Next');

    // Step 3: Configuration - select UAE, assert VAT and AED
    $page->select('#country_code', 'AE')
        ->assertSee('VAT')
        ->select('#currency', 'AED')
        ->screenshot(fullPage: true, filename: 'demo/23-setup-uae-step3')
        ->click('Next');

    // Step 4: Contact Details
    $page->fill('input[aria-describedby="error-emails-0"]', 'info@dubaitrading.test')
        ->fill('#phone', '+971-4-1234567')
        ->click('Complete Setup')
        ->waitForEvent('networkidle');

    // Navigate to dashboard to verify setup was saved
    $this->visit('/dashboard')
        ->assertPathIs('/dashboard');
});

// ─── Test 5: Minimal setup with only required fields ───

it('completes minimal setup with only required fields', function () {
    [$user] = createSetupUser('setup-minimal@example.test', 'Sarah Chen');

    $this->actingAs($user);

    $page = $this->visit('/organization/setup');

    // Step 1: Only company name (required)
    $page->fill('#company_name', 'Minimal Corp')
        ->click('Next');

    // Step 2: Only required fields (skip location_name, gstin, address_line_2)
    $page->fill('#address_line_1', '100 Main Street')
        ->fill('#city', 'New York')
        ->fill('#state', 'NY')
        ->fill('#postal_code', '10001')
        ->click('Next');

    // Step 3: Select US, currency auto-sets to USD
    $page->select('#country_code', 'US')
        ->select('#currency', 'USD')
        ->click('Next');

    // Step 4: One email only (skip phone)
    $page->fill('input[aria-describedby="error-emails-0"]', 'admin@minimalcorp.test')
        ->click('Complete Setup')
        ->waitForEvent('networkidle');

    // Navigate to dashboard to verify setup was saved
    $this->visit('/dashboard')
        ->assertPathIs('/dashboard');
});

// ─── Test 6: Organization visible after setup completion ───

it('adds additional location after setup completion', function () {
    [$user] = createSetupUser('setup-locations@example.test', 'David Miller');

    $this->actingAs($user);

    // Complete setup
    $page = $this->visit('/organization/setup');
    $page->fill('#company_name', 'Multi-Location Corp')
        ->click('Next')
        ->fill('#address_line_1', '200 Broadway')
        ->fill('#city', 'San Francisco')
        ->fill('#state', 'CA')
        ->fill('#postal_code', '94102')
        ->click('Next')
        ->select('#country_code', 'US')
        ->click('Next')
        ->fill('input[aria-describedby="error-emails-0"]', 'admin@multiloc.test')
        ->click('Complete Setup')
        ->waitForEvent('networkidle');

    // Navigate to dashboard to verify setup was saved
    $this->visit('/dashboard')
        ->assertPathIs('/dashboard');

    // Navigate to organizations list (always shows the list now)
    $this->visit('/organizations')
        ->waitForEvent('networkidle')
        ->assertPathIs('/organizations')
        ->assertSee('Multi-Location Corp')
        ->screenshot(fullPage: true, filename: 'demo/24-org-with-locations');
});

// ─── Test 7: Validates required fields on each step ───

it('validates required fields on each step', function () {
    [$user] = createSetupUser('setup-validation@example.test', 'Emily Johnson');

    $this->actingAs($user);

    $page = $this->visit('/organization/setup');

    // Step 1: Try to proceed without company_name - should stay on step 1
    $page->click('Next')
        ->waitForEvent('networkidle')
        ->assertSee('Company Information');

    // Fill company_name and proceed to step 2
    $page->fill('#company_name', 'Validation Test Corp')
        ->click('Next')
        ->assertSee('Primary Location');

    // Step 2: Try to proceed without required address fields - should stay on step 2
    $page->click('Next')
        ->waitForEvent('networkidle')
        ->assertSee('Primary Location');
});

// ─── Test 8: Back navigation preserves form data ───

it('navigates back and preserves data', function () {
    [$user] = createSetupUser('setup-nav@example.test', 'Raj Patel');

    $this->actingAs($user);

    $page = $this->visit('/organization/setup');

    // Fill Step 1 and advance
    $page->fill('#company_name', 'NavTest Corp')
        ->fill('#tax_number', 'TX-NAV-001')
        ->click('Next');

    // Fill Step 2
    $page->assertSee('Primary Location')
        ->fill('#address_line_1', '500 Navigation Ave')
        ->fill('#city', 'Portland')
        ->fill('#state', 'OR')
        ->fill('#postal_code', '97201');

    // Go back to Step 1 — assert data preserved
    $page->click('Previous')
        ->assertSee('Company Information')
        ->assertValue('#company_name', 'NavTest Corp')
        ->assertValue('#tax_number', 'TX-NAV-001');

    // Go forward to Step 2 — assert data preserved
    $page->click('Next')
        ->assertSee('Primary Location')
        ->assertValue('#address_line_1', '500 Navigation Ave')
        ->assertValue('#city', 'Portland');
});
