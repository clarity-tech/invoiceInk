<?php

use App\Enums\InvoiceStatus;
use App\Models\Organization;
use App\Models\User;
use App\Services\PaymentService;
use App\ValueObjects\ContactCollection;
use Carbon\Carbon;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

/**
 * Screenshot tour of every page, for the dark-mode before/after review.
 *
 * Opt-in: THEME_TOUR=before|after, THEME_TOUR_SCHEME=light|dark.
 * Output: tests/Browser/Screenshots/theme-tour/{THEME_TOUR}-{scheme}/NN-name.png
 */
require_once __DIR__.'/DemoScreenshotTest.php';

function tourEnabled(): bool
{
    return (bool) env('THEME_TOUR');
}

function tourDir(): string
{
    return 'theme-tour/'.env('THEME_TOUR', 'adhoc').'-'.env('THEME_TOUR_SCHEME', 'light');
}

function tourVisit(string $path, bool $mobile = false)
{
    $pending = visit($path);

    // Device first: every call on the `on()` builder opens a fresh page, so
    // the colour scheme is what turns it into one page the tour can drive.
    if ($mobile) {
        $pending = $pending->on()->mobile();
    }

    return env('THEME_TOUR_SCHEME') === 'dark' ? $pending->inDarkMode() : $pending->inLightMode();
}

function tourShot($page, string $name): void
{
    $dir = base_path('tests/Browser/Screenshots/'.tourDir());
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Let transitions and fonts settle so before/after pairs are comparable.
    $page->wait(0.6);
    $page->screenshot(fullPage: true, filename: tourDir().'/'.$name);

    expect("{$dir}/{$name}.png")->toBeFile();
}

/**
 * @return array{user: User, org: Organization, customers: array, invoices: array, estimates: array}
 */
function seedTourData(): array
{
    fake()->seed(20261014);

    $data = seedDemoData();

    $emails = [
        'indian' => 'accounts@brightcare.example.test',
        'uae' => 'billing@sandline.example.test',
        'generic' => 'ap@acme.example.test',
    ];
    foreach ($data['customers'] as $key => $customer) {
        $customer->update(['emails' => new ContactCollection([
            ['name' => 'Accounts', 'email' => $emails[$key]],
        ])]);
    }

    $data['org']->update([
        'emails' => new ContactCollection([
            ['name' => 'Billing', 'email' => 'billing@clarity.example.test'],
        ]),
        'website' => 'https://clarity.example.test',
        'phone' => '+919876543210',
        'phone_country' => 'IN',
    ]);

    $service = new PaymentService;
    $paid = $data['invoices']['paid'];
    $paid->update(['status' => InvoiceStatus::SENT]);
    $service->recordPayment($paid, [
        'amount' => $paid->total,
        'payment_date' => now()->subMonth()->toDateString(),
        'payment_method' => 'bank_transfer',
        'reference' => 'NEFT-20260915-001',
    ]);

    $paid2 = $data['invoices']['paid2'];
    $paid2->update(['status' => InvoiceStatus::SENT]);
    $service->recordPayment($paid2, [
        'amount' => $paid2->total,
        'payment_date' => now()->subMonths(2)->toDateString(),
        'payment_method' => 'bank_transfer',
        'reference' => 'NEFT-20260815-002',
    ]);

    $sent = $data['invoices']['sent'];
    $service->recordPayment($sent, [
        'amount' => (int) ($sent->total * 0.5),
        'payment_date' => now()->subDays(3)->toDateString(),
        'payment_method' => 'card',
        'reference' => 'STRIPE-PAY-001',
    ]);

    $data['user']->switchTeam($data['org']);

    return $data;
}

beforeEach(function () {
    if (! tourEnabled()) {
        $this->markTestSkipped('Set THEME_TOUR=before|after to capture the theme tour.');
    }

    $this->travelTo(Carbon::parse('2026-10-14 10:00:00'));
});

it('tours the guest pages', function () {
    tourShot(tourVisit('/'), '01-welcome');
    tourShot(tourVisit('/login'), '02-login');
    tourShot(tourVisit('/register'), '03-register');
    tourShot(tourVisit('/forgot-password'), '04-forgot-password');
    tourShot(tourVisit('/reset-password/tour-token?email=demo%40example.test'), '05-reset-password');
    tourShot(tourVisit('/terms-of-service'), '06-terms-of-service');
    tourShot(tourVisit('/privacy-policy'), '07-privacy-policy');

    $page = tourVisit('/login');
    $page->fill('email', 'nobody@example.test')->fill('password', 'wrong-password')->press('Log in');
    $page->assertSee('These credentials do not match');
    tourShot($page, '08-login-validation-errors');
});

it('tours the two factor challenge', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'two-factor@example.test',
        'password' => 'password',
        'two_factor_secret' => encrypt(app(TwoFactorAuthenticationProvider::class)->generateSecretKey()),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-one', 'code-two'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $page = tourVisit('/login');
    $page->fill('email', $user->email)->fill('password', 'password')->press('Log in');
    $page->assertPathIs('/two-factor-challenge');
    tourShot($page, '09-two-factor-challenge');
});

it('tours the account gate pages', function () {
    $unverified = User::factory()->withPersonalTeam()->unverified()->create();
    $this->actingAs($unverified);
    tourShot(tourVisit('/email/verify'), '10-verify-email');

    $user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($user);
    tourShot(tourVisit('/user/confirm-password'), '11-confirm-password');
});

it('tours the setup wizard', function () {
    $user = User::factory()->withPersonalTeam()->create(['name' => 'Maya Sharma']);
    $org = $user->currentTeam;
    $org->update([
        'company_name' => 'Fresh Start Studio',
        'emails' => new ContactCollection([['name' => '', 'email' => 'hello@fresh.example.test']]),
        'country_code' => 'IN',
        'currency' => 'INR',
        'setup_completed_at' => null,
    ]);
    $this->actingAs($user);

    $page = tourVisit('/organization/setup');
    tourShot($page, '12-setup-step-1-company');

    $page->press('Next');
    $page->assertSee('Your main business address');
    tourShot($page, '13-setup-step-2-location');

    $page->fill('#address_line_1', '42 MG Road')
        ->fill('#city', 'Bangalore')
        ->fill('#state', 'Karnataka')
        ->fill('#postal_code', '560038');
    $page->press('Next');
    $page->assertSee('Currency');
    tourShot($page, '14-setup-step-3-financial');
});

it('tours the dashboard and documents', function () {
    $data = seedTourData();
    $this->actingAs($data['user']);

    tourShot(tourVisit('/dashboard'), '15-dashboard');

    $page = tourVisit('/dashboard');
    $page->click('[data-user-dropdown] > button');
    tourShot($page, '16-user-menu-open');

    $page = tourVisit('/dashboard', mobile: true);
    // Inertia's first `navigate` event closes the menu, so a tap that lands
    // before it is undone.
    $page->wait(0.5);
    $page->click('[aria-label="Toggle navigation menu"]')->assertVisible('#mobile-nav-panel');
    tourShot($page, '17-mobile-menu-open');

    tourShot(tourVisit('/invoices'), '18-invoices-list');
    tourShot(tourVisit('/invoices?type=estimate'), '19-estimates-list');
    tourShot(tourVisit('/invoices', mobile: true), '20-invoices-list-mobile');

    $data['invoices']['draft']->delete();
    tourShot(tourVisit('/invoices/trash'), '21-invoices-trash');

    tourShot(tourVisit('/invoices/create'), '22-invoice-create');
    tourShot(tourVisit('/estimates/create'), '23-estimate-create');

    $sent = $data['invoices']['sent'];
    tourShot(tourVisit("/invoices/{$sent->id}"), '24-invoice-show');

    // Sending lives in the editor; its "Save & Send" lands on ?send=1.
    $page = tourVisit("/invoices/{$sent->id}/edit?send=1");
    $page->wait(0.5);
    tourShot($page, '25-invoice-email-modal');

    $page = tourVisit("/invoices/{$sent->id}");
    $page->click('Record payment');
    $page->wait(0.5);
    tourShot($page, '26-invoice-payment-modal');

    $page = tourVisit("/invoices/{$sent->id}");
    $page->click("[aria-label=\"Delete invoice {$sent->invoice_number}\"]");
    $page->wait(0.5);
    tourShot($page, '27-invoice-delete-confirmation');

    tourShot(tourVisit("/invoices/{$data['estimates']['sent']->id}"), '28-estimate-show');
    tourShot(tourVisit("/invoices/{$data['invoices']['paid']->id}"), '29-invoice-show-paid');
    tourShot(tourVisit("/invoices/{$sent->id}/edit"), '30-invoice-edit');
    tourShot(tourVisit("/invoices/{$sent->id}", mobile: true), '31-invoice-show-mobile');
});

it('tours customers', function () {
    $data = seedTourData();
    $this->actingAs($data['user']);

    tourShot(tourVisit('/customers'), '32-customers-list');

    $page = tourVisit('/customers');
    $page->click('Add Customer');
    $page->wait(0.5);
    tourShot($page, '33-customer-add-dialog');

    $customer = $data['customers']['indian'];
    tourShot(tourVisit("/customers/{$customer->id}"), '34-customer-show');

    $page = tourVisit("/customers/{$customer->id}");
    $page->click('button:has-text("Add location")');
    $page->wait(0.5);
    tourShot($page, '35-customer-location-modal');

    $data['customers']['generic']->delete();
    tourShot(tourVisit('/customers/trash'), '36-customers-trash');
});

it('tours organizations and settings', function () {
    $data = seedTourData();
    $this->actingAs($data['user']);
    $org = $data['org'];

    tourShot(tourVisit('/organizations'), '37-organizations-list');
    tourShot(tourVisit("/organizations/{$org->id}"), '38-organization-show');

    foreach (['basics', 'location', 'bank', 'logo', 'numbering'] as $i => $tab) {
        tourShot(tourVisit("/organizations/{$org->id}/edit?tab={$tab}"), sprintf('%02d-organization-edit-%s', 39 + $i, $tab));
    }

    tourShot(tourVisit('/settings'), '44-settings');
    tourShot(tourVisit('/settings/ai-connection'), '45-ai-connection');
    tourShot(tourVisit('/email-templates'), '46-email-templates');
    tourShot(tourVisit('/email-templates/invoice_initial'), '47-email-template-edit');
    tourShot(tourVisit('/user/profile'), '48-profile');
    tourShot(tourVisit('/teams/create'), '49-team-create');
    tourShot(tourVisit("/teams/{$org->id}"), '50-team-show');
});

it('tours the empty states', function () {
    $user = User::factory()->withPersonalTeam()->create(['name' => 'New User']);
    $user->currentTeam->update([
        'company_name' => 'Empty Co',
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ]);
    $this->actingAs($user);

    tourShot(tourVisit('/dashboard'), '51-dashboard-empty');
    tourShot(tourVisit('/invoices'), '52-invoices-empty');
    tourShot(tourVisit('/customers'), '53-customers-empty');
});

it('tours the public documents', function () {
    $data = seedTourData();

    tourShot(tourVisit("/invoices/view/{$data['invoices']['sent']->ulid}"), '54-public-invoice');
    tourShot(tourVisit("/estimates/view/{$data['estimates']['sent']->ulid}"), '55-public-estimate');
});
