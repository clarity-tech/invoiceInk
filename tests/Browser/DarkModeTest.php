<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\ValueObjects\ContactCollection;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Passport\ClientRepository;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;
use Pest\Browser\Api\Webpage;

/**
 * Dark mode: the System / Light / Dark choice, where it is offered, and that
 * every page stays legible in the dark theme.
 *
 * `inDarkMode()` only emulates the operating system's setting, so it exercises
 * the `system` choice. An explicit choice is made the way a person makes it,
 * through the toggle, which also proves the cookie it writes is honoured on
 * the next page load.
 */
const IS_DARK = "document.documentElement.classList.contains('dark')";

function darkModeUser(): User
{
    $user = User::factory()->withPersonalTeam()->create([
        'name' => 'Maya Sharma',
        'email' => 'dark-mode-'.uniqid().'@example.test',
    ]);

    createOrganizationWithLocation(['name' => 'Clarity Technologies'], [], $user);

    return $user;
}

function chooseAppearance(PendingAwaitablePage|AwaitableWebpage|Webpage $page, string $appearance): AwaitableWebpage|Webpage
{
    return $page->click('[data-user-dropdown] > button')
        ->click("[data-user-dropdown] [data-appearance-option=\"{$appearance}\"]");
}

/**
 * axe's colour-contrast rule only: the one accessibility rule a theme can
 * break. The light theme is not held to it here because it carries contrast
 * failures that predate dark mode (gray-400 hint text, 2.6:1).
 *
 * @param  list<string>  $exclude  selectors left out of the check, each with a reason at the call
 */
function assertNoContrastFailures(PendingAwaitablePage|AwaitableWebpage|Webpage $page, array $exclude = []): void
{
    $exclude = json_encode($exclude);

    $failures = $page->script(<<<JS
        async () => {
            // A colour mid-transition (a button fading in from `disabled`) is
            // not one a person reads, so let finite animations finish first.
            await Promise.all(
                document.getAnimations()
                    .filter((animation) => animation.effect?.getComputedTiming().iterations !== Infinity)
                    .map((animation) => animation.finished.catch(() => null)),
            );

            const result = await window.axe.run({ exclude: {$exclude} }, { runOnly: ['color-contrast'] });
            return result.violations.flatMap((violation) =>
                violation.nodes.map((node) => node.target.join(' ') + ' -- ' + (node.any[0]?.message ?? '')),
            );
        }
        JS);

    expect($failures)->toBeEmpty(implode("\n", $failures ?? []));
}

it('follows a dark system setting until a choice is made', function () {
    $this->actingAs(darkModeUser());

    visit('/dashboard')->inDarkMode()
        ->assertScript(IS_DARK, true)
        ->assertNoJavascriptErrors();
});

it('follows a light system setting until a choice is made', function () {
    $this->actingAs(darkModeUser());

    visit('/dashboard')->inLightMode()
        ->assertScript(IS_DARK, false);
});

it('switches to dark on a light system and keeps the choice on later pages', function () {
    $this->actingAs(darkModeUser());

    $page = chooseAppearance(visit('/dashboard')->inLightMode(), 'dark');

    $page->assertScript(IS_DARK, true)
        ->assertChecked('[data-user-dropdown] input[value="dark"]');

    $page->navigate('/invoices')->assertScript(IS_DARK, true);
});

it('switches to light on a dark system and keeps the choice after a reload', function () {
    $this->actingAs(darkModeUser());

    $page = chooseAppearance(visit('/dashboard')->inDarkMode(), 'light');

    $page->assertScript(IS_DARK, false);

    $page->refresh()->assertScript(IS_DARK, false);
});

it('goes back to following the system', function () {
    $this->actingAs(darkModeUser());

    $page = chooseAppearance(visit('/dashboard')->inLightMode(), 'dark');
    $page->assertScript(IS_DARK, true);

    $page->click('[data-user-dropdown] [data-appearance-option="system"]')
        ->assertScript(IS_DARK, false);

    $page->refresh()->assertScript(IS_DARK, false);
});

it('offers the choice in the mobile menu', function () {
    $this->actingAs(darkModeUser());

    // Device first: every call on the `on()` builder opens a fresh page, so the
    // colour scheme is what turns it into one page that the chain then drives.
    $page = visit('/dashboard')->on()->mobile()->inLightMode();

    $page->click('[aria-label="Toggle navigation menu"]')
        ->click('#mobile-nav-panel [data-appearance-option="dark"]')
        ->assertScript(IS_DARK, true);
});

// The bar holds two switches, one for each layout; only one is ever shown.
const BAR_SWITCH = '[data-appearance-switch]:visible';

it('switches to dark from the bar in one click and keeps it', function () {
    $this->actingAs(darkModeUser());

    $page = visit('/dashboard')->inLightMode();
    $page->click(BAR_SWITCH)->assertScript(IS_DARK, true);

    $page->navigate('/invoices')->assertScript(IS_DARK, true);
    $page->click(BAR_SWITCH)->assertScript(IS_DARK, false);
});

it('switches to light from the bar when the system is dark', function () {
    $this->actingAs(darkModeUser());

    $page = visit('/dashboard')->inDarkMode();
    $page->assertAttribute(BAR_SWITCH, 'aria-pressed', 'true')
        ->click(BAR_SWITCH)
        ->assertScript(IS_DARK, false)
        ->assertAttribute(BAR_SWITCH, 'aria-pressed', 'false');
});

it('offers the switch in the bar on a phone, without opening the menu', function () {
    $this->actingAs(darkModeUser());

    visit('/dashboard')->on()->mobile()->inLightMode()
        ->assertMissing('#mobile-nav-panel:visible')
        ->click(BAR_SWITCH)
        ->assertScript(IS_DARK, true);
});

it('fits the switch in the bar at its narrowest', function () {
    // The full bar shows from 768px, and NavigationMenu.vue budgets that
    // width. The switch and the user menu do not shrink, so a bar that is too
    // full pushes the menu past the edge instead of squeezing it.
    $this->actingAs(darkModeUser());

    visit('/dashboard')->resize(768, 900)
        ->assertVisible(BAR_SWITCH)
        ->assertScript(
            "(() => { const row = document.querySelector('nav .h-16').getBoundingClientRect(); const menu = document.querySelector('[data-user-dropdown]').getBoundingClientRect(); return menu.right <= row.right + 1 ? 'fits' : 'past the edge by ' + Math.round(menu.right - row.right); })()",
            'fits',
        );
});

it('draws no white halo around a focused checkbox in dark mode', function () {
    // The forms plugin gives a focused checkbox a 2px ring offset painted
    // white. axe does not look at focus states, so this checks the colour.
    visit('/login')->inDarkMode()->assertScript(
        "(() => { const box = document.querySelector('input[type=checkbox]'); box.focus(); const offset = getComputedStyle(box).getPropertyValue('--tw-ring-offset-color').trim(); const card = getComputedStyle(document.documentElement).getPropertyValue('--card').trim(); return offset === card ? 'card' : offset; })()",
        'card',
    );
});

it('offers the switch on the signed-out pages', function () {
    $page = visit('/login')->inLightMode();
    $page->click(BAR_SWITCH)->assertScript(IS_DARK, true);

    $page->navigate('/register')->assertScript(IS_DARK, true);
});

it('offers the switch on the shared invoice', function () {
    $invoice = createInvoiceWithItems(['status' => 'sent']);

    $page = visit(sharePath($invoice))->inDarkMode();
    $page->assertAttribute('[data-appearance-switch]', 'aria-pressed', 'true')
        ->click('[data-appearance-switch]')
        ->assertScript(IS_DARK, false)
        ->assertAttribute('[data-appearance-switch]', 'aria-pressed', 'false');

    // Stored, so the page no longer follows the dark system.
    $page->navigate(sharePath($invoice))->assertScript(IS_DARK, false);
});

it('carries the choice to the signed-out pages', function () {
    $this->actingAs(darkModeUser());

    $page = chooseAppearance(visit('/dashboard')->inLightMode(), 'dark');

    // The terms page is plain Blade, so this is the server and the inline
    // head script applying the cookie, with no Vue involved.
    $page->navigate('/terms-of-service')->assertScript(IS_DARK, true);
});

const SHARED_DOCUMENT = '.document-card';

/**
 * The page's background, and every colour inside the document card: text,
 * fills, borders and image filters, element by element.
 */
const SHARED_PAGE_COLOURS = <<<'JS'
    (() => ({
        page: getComputedStyle(document.body).backgroundColor,
        document: [...document.querySelectorAll('.document-card, .document-card *')].map((el) => {
            const style = getComputedStyle(el);
            return [style.color, style.backgroundColor, style.borderTopColor, style.borderBottomColor, style.filter].join(' | ');
        }),
    }))()
    JS;

it('darkens the page around the shared document but not the document', function (string $type) {
    // The document is the sheet that prints and becomes the PDF, so it looks
    // the same in both themes; only the page around it follows dark mode.
    $document = createInvoiceWithItems(['type' => $type, 'status' => 'sent']);
    $url = sharePath($document);

    $light = visit($url)->inLightMode()->script(SHARED_PAGE_COLOURS);
    $page = visit($url)->inDarkMode();
    $dark = $page->script(SHARED_PAGE_COLOURS);

    $page->assertSee($document->invoice_number)
        ->assertScript(IS_DARK, true)
        ->assertNoJavascriptErrors();
    expect($dark['page'])->not->toBe($light['page'])
        ->and($dark['document'])->not->toBeEmpty()->toBe($light['document']);

    // The card is the light sheet, which is why it is identical above, and
    // light mode is not held to the contrast rule (see
    // assertNoContrastFailures), so this checks the page around it.
    assertNoContrastFailures($page, [SHARED_DOCUMENT]);
})->with(['invoice', 'estimate']);

it('carries a choice made in the app to the shared invoice', function () {
    $this->actingAs(darkModeUser());
    $invoice = createInvoiceWithItems(['status' => 'sent']);

    $page = chooseAppearance(visit('/dashboard')->inDarkMode(), 'light');

    $page->navigate(sharePath($invoice))->assertScript(IS_DARK, false);
});

/**
 * A signed-in user whose organization has something on every page, including
 * a trashed invoice and customer for the two trash pages.
 *
 * @return array{user: User, organization: Organization, customer: Customer, invoice: Invoice}
 */
function darkModeFixture(): array
{
    $user = darkModeUser();
    $organization = $user->fresh()->currentTeam;
    $customer = createCustomerWithLocation(['name' => 'Sandline Pharmacy LLC'], [], $organization);
    // Dated, so the editor's "Save & Send" can save it on the way to the email.
    $invoice = createInvoiceWithItems([
        'status' => 'sent',
        'issued_at' => now()->subDays(2),
        'due_at' => now()->addDays(28),
    ], null, $organization, $customer);
    createInvoiceWithItems(['type' => 'estimate', 'status' => 'accepted'], null, $organization, $customer);
    createInvoiceWithItems(['status' => 'draft'], null, $organization, $customer)->delete();
    createCustomerWithLocation(['name' => 'Archived Customer'], [], $organization)->delete();
    createNumberingSeries([], $organization);

    return compact('user', 'organization', 'customer', 'invoice');
}

/**
 * @param  array{user: User, organization: Organization, customer: Customer, invoice: Invoice}  $fixture
 */
function fixturePath(string $path, array $fixture): string
{
    return strtr($path, [
        '{invoice}' => $fixture['invoice']->id,
        '{customer}' => $fixture['customer']->id,
        '{organization}' => $fixture['organization']->id,
    ]);
}

it('keeps every signed-in page legible in dark mode', function (string $path) {
    $fixture = darkModeFixture();
    $this->actingAs($fixture['user']);

    $page = visit(fixturePath($path, $fixture))->inDarkMode();

    $page->assertScript(IS_DARK, true)->assertNoJavascriptErrors();
    assertNoContrastFailures($page);
})->with([
    'dashboard' => '/dashboard',
    'invoice list' => '/invoices',
    'invoice trash' => '/invoices/trash',
    'invoice' => '/invoices/{invoice}',
    'invoice form' => '/invoices/{invoice}/edit',
    'new invoice' => '/invoices/create',
    'new estimate' => '/estimates/create',
    'customer list' => '/customers',
    'customer trash' => '/customers/trash',
    'customer' => '/customers/{customer}',
    'organization list' => '/organizations',
    'organization' => '/organizations/{organization}',
    'organization basics' => '/organizations/{organization}/edit?tab=basics',
    'organization location' => '/organizations/{organization}/edit?tab=location',
    'bank details' => '/organizations/{organization}/edit?tab=bank',
    'logo' => '/organizations/{organization}/edit?tab=logo',
    'numbering series' => '/organizations/{organization}/edit?tab=numbering',
    'settings' => '/settings',
    'connect AI tools' => '/settings/ai-connection',
    'email templates' => '/email-templates',
    'email template' => '/email-templates/invoice_initial',
    'profile' => '/user/profile',
    'team' => '/teams/{organization}',
    'new organization' => '/teams/create',
]);

it('keeps the OAuth consent screen legible in dark mode', function () {
    // The page an AI client's sign-in lands on. It is outside the app layout,
    // so the list above does not reach it.
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        name: 'Claude',
        redirectUris: ['https://claude.ai/api/mcp/auth_callback'],
        confidential: false,
    );

    $this->actingAs(darkModeUser());

    $page = visit('/oauth/authorize?'.http_build_query([
        'response_type' => 'code',
        'client_id' => $client->getKey(),
        'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
        'code_challenge' => str_repeat('a', 43),
        'code_challenge_method' => 'S256',
        'scope' => 'mcp:use',
    ]))->inDarkMode();

    $page->assertSee('Connect Claude')
        ->assertSee('claude.ai')
        ->assertScript(IS_DARK, true)
        ->assertNoJavascriptErrors();
    assertNoContrastFailures($page);
});

it('keeps the empty states legible in dark mode', function (string $path) {
    $this->actingAs(darkModeUser());

    $page = visit($path)->inDarkMode();

    assertNoContrastFailures($page);
})->with([
    'dashboard' => '/dashboard',
    'invoice list' => '/invoices',
    'customer list' => '/customers',
]);

it('keeps the open user menu legible in dark mode', function () {
    $this->actingAs(darkModeUser());

    $page = visit('/dashboard')->inDarkMode();
    $page->click('[data-user-dropdown] > button');

    assertNoContrastFailures($page);
});

it('keeps the dialogs legible in dark mode', function (string $path, string $opener, string $dialogText) {
    $fixture = darkModeFixture();
    $this->actingAs($fixture['user']);

    $page = visit(fixturePath($path, $fixture))->inDarkMode();
    $page->click($opener)->assertSee($dialogText);

    assertNoContrastFailures($page);
})->with([
    'record payment' => ['/invoices/{invoice}', 'Record payment', 'Balance Due'],
    'email' => ['/invoices/{invoice}/edit', 'Save & Send', 'Email to Sandline Pharmacy LLC'],
    'delete confirmation' => ['/invoices/{invoice}', '[aria-label^="Delete invoice"]', 'moves to Trash'],
    'add customer' => ['/customers', 'Add Customer', 'Create Customer'],
    'add location' => ['/customers/{customer}', 'button:has-text("Add location")', 'Location Name'],
    'reminder' => ['/invoices/{invoice}', 'button:has-text("Remind")', 'Email to Sandline Pharmacy LLC'],
    'customer reminder' => ['/customers/{customer}', 'button:has-text("Send reminder")', 'Remind Sandline Pharmacy LLC'],
]);

it('keeps the setup wizard legible in dark mode', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $user->currentTeam->update([
        'company_name' => 'Fresh Start Studio',
        'emails' => new ContactCollection([['name' => '', 'email' => 'hello@fresh.example.test']]),
        'country_code' => 'IN',
        'currency' => 'INR',
        'setup_completed_at' => null,
    ]);
    $this->actingAs($user);

    $page = visit('/organization/setup')->inDarkMode();
    assertNoContrastFailures($page);

    $page->press('Next')->assertSee('Your main business address');
    assertNoContrastFailures($page);

    $page->fill('#address_line_1', '42 MG Road')
        ->fill('#city', 'Bangalore')
        ->fill('#state', 'Karnataka')
        ->fill('#postal_code', '560038')
        ->press('Next')
        ->assertSee('Set your currency and financial year');
    assertNoContrastFailures($page);
});

it('keeps the account checks legible in dark mode', function () {
    $this->actingAs(User::factory()->withPersonalTeam()->unverified()->create());
    $page = visit('/email/verify')->inDarkMode();
    $page->assertSee('verify your email address');
    assertNoContrastFailures($page);

    $this->actingAs(darkModeUser());
    $page = visit('/user/confirm-password')->inDarkMode();
    $page->assertSee('This is a secure area');
    assertNoContrastFailures($page);
});

it('keeps the two-factor challenge legible in dark mode', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'password' => 'password',
        'two_factor_secret' => encrypt(app(TwoFactorAuthenticationProvider::class)->generateSecretKey()),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-one', 'code-two'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $page = visit('/login')->inDarkMode();
    $page->fill('email', $user->email)->fill('password', 'password')->press('Log in')
        ->assertPathIs('/two-factor-challenge');

    assertNoContrastFailures($page);
});

it('keeps the signed-out pages legible in dark mode', function (string $path) {
    $page = visit($path)->inDarkMode();

    $page->assertScript(IS_DARK, true)->assertNoJavascriptErrors();
    assertNoContrastFailures($page);
})->with([
    'log in' => '/login',
    'register' => '/register',
    'forgot password' => '/forgot-password',
    'reset password' => '/reset-password/a-token?email=someone%40example.test',
    'terms of service' => '/terms-of-service',
    'privacy policy' => '/privacy-policy',
]);

it('keeps the page around the shared invoice legible on a phone in dark mode', function () {
    $invoice = createInvoiceWithItems(['status' => 'sent']);

    $page = visit(sharePath($invoice))->on()->mobile()->inDarkMode();

    $page->assertScript(IS_DARK, true);
    assertNoContrastFailures($page, [SHARED_DOCUMENT]);
});

it('keeps the phone layout legible in dark mode', function () {
    $fixture = darkModeFixture();
    $this->actingAs($fixture['user']);

    $page = visit("/invoices/{$fixture['invoice']->id}")->on()->mobile()->inDarkMode();
    assertNoContrastFailures($page);

    // Inertia's first `navigate` event closes the menu, so let it pass.
    $page->wait(0.5);
    $page->click('[aria-label="Toggle navigation menu"]')->assertVisible('#mobile-nav-panel');
    assertNoContrastFailures($page);
});
