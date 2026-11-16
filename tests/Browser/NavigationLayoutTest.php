<?php

use App\Models\User;

/**
 * Overflow + layout-switch guard for the shared navigation bar (#57).
 *
 * The desktop nav used to appear at `sm:` (640px) while the hamburger
 * disappeared at the same breakpoint, so between 640px and ~1000px neither
 * layout fitted and the document overflowed horizontally — measured at +300px
 * at 640px and +172px at 768px.
 *
 * Both now switch at `md:` (768px). That threshold is only reachable because
 * organization switching moved into the user menu: it cost ~167px of a ~940px
 * nav, and without it the full set of links fits from 768px.
 *
 * The real guard is `scrollWidth - clientWidth` on the document. `assertSee`
 * would pass on the broken layout too, since overflowing content stays in the
 * DOM and is merely scrolled out of view.
 */
function navUser(string $email, string $orgName): User
{
    $user = User::factory()->withPersonalTeam()->create([
        'name' => 'Maya Sharma',
        'email' => $email,
    ]);

    createOrganizationWithLocation([
        'name' => $orgName,
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ], [], $user);

    return $user;
}

it('does not overflow the viewport at any width', function () {
    // The longest realistic organization name. The switcher truncates at
    // 12rem, so this is the worst case the desktop nav has to fit.
    $this->actingAs(navUser('nav-overflow@example.test', 'Riverbend Institute of Medical Sciences Limited'));

    $page = visit('/dashboard');

    // 640 / 768 / 1000 all overflowed before the fix; 767 and 768 sit either
    // side of the new breakpoint.
    foreach ([375, 640, 700, 767, 768, 900, 1000, 1024, 1280, 1440] as $width) {
        $page->resize($width, 900)
            ->assertScript(
                'function () {
                    const d = document.documentElement;
                    return (d.scrollWidth - d.clientWidth) <= 1;
                }',
            );
    }
});

it('shows exactly one of the desktop nav and the hamburger at every width', function () {
    $this->actingAs(navUser('nav-switch@example.test', 'Clarity Technologies'));

    $page = visit('/dashboard');

    // This does NOT catch the overflow — at 768px the broken layout still showed
    // exactly one (the desktop nav, too wide to fit). It pins the *coordination*
    // instead: moving one of the two breakpoints without the other leaves a band
    // with both layouts, or none at all, which is how the bug arose.
    foreach ([375, 640, 700, 767, 768, 1000, 1024, 1440] as $width) {
        $page->resize($width, 900)
            ->assertScript(
                'function () {
                    const visible = (el) => !!(el && el.offsetParent !== null);
                    // The user menu is the desktop-only right-hand block.
                    // (data-team-dropdown now lives inside it, collapsed.)
                    const desktop = document.querySelector("[data-user-dropdown]");
                    const burger = document.querySelector("[aria-label=\'Toggle navigation menu\']");
                    return visible(desktop) !== visible(burger);
                }',
            );
    }
});

it('opens and closes the mobile panel below the breakpoint', function () {
    $this->actingAs(navUser('nav-mobile@example.test', 'Clarity Technologies'));

    $page = visit('/dashboard')->resize(700, 900);

    $hidden = 'function () {
        const p = document.getElementById("mobile-nav-panel");
        return !!p && p.offsetParent === null;
    }';

    $page->assertScript($hidden)
        ->click('[aria-label="Toggle navigation menu"]')
        ->assertScript(
            'function () {
                const p = document.getElementById("mobile-nav-panel");
                return !!p && p.offsetParent !== null;
            }',
        )
        ->click('[aria-label="Toggle navigation menu"]')
        ->assertScript($hidden)
        ->assertNoJavaScriptErrors();
});

it('shows the tab bar on a phone and the top nav from the md breakpoint', function () {
    $this->actingAs(navUser('nav-tabs@example.test', 'Clarity Technologies'));

    $page = visit('/dashboard');

    foreach ([375 => true, 767 => true, 768 => false, 1280 => false] as $width => $shown) {
        $page->resize($width, 900)
            ->assertScript(
                'function () {
                    const bar = document.querySelector("[data-tab-bar]");
                    return !!bar && getComputedStyle(bar).display !== "none";
                }',
                $shown,
            );
    }
});

it('leaves the tab bar out of the invoice form, which has its own Save bar', function () {
    $this->actingAs(navUser('nav-tabs-form@example.test', 'Clarity Technologies'));

    visit('/invoices/create')->resize(390, 844)
        ->assertScript('function () { return document.querySelector("[data-tab-bar]") === null; }')
        ->assertNoJavaScriptErrors();
});

it('leaves the tab bar out of the setup wizard, where every tab leads back to it', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'nav-tabs-setup@example.test',
    ]);
    $user->currentTeam->update(['setup_completed_at' => null, 'company_name' => null]);

    $this->actingAs($user);

    visit('/organization/setup')->resize(390, 844)
        ->assertPathIs('/organization/setup')
        ->assertSee('Company Information')
        ->assertScript('function () { return document.querySelector("[data-tab-bar]") === null; }')
        ->assertNoJavaScriptErrors();
});

it('lets the end of a long list scroll clear of the tab bar', function () {
    // 12 documents puts the pagination bar at the very end of the page.
    [$user] = seedInvoiceListFixture(12);
    $this->actingAs($user);

    // A calc() that fails to parse leaves no padding at all, and nothing else
    // would notice: the content is still there, just under the bar.
    $gap = visit('/invoices')->resize(390, 844)->script(<<<'JS'
    function () {
        window.scrollTo(0, document.documentElement.scrollHeight);
        const bar = document.querySelector('[data-tab-bar]').getBoundingClientRect();
        const last = document.querySelector('nav[aria-label="Pagination"]').getBoundingClientRect();
        return bar.top - last.bottom;
    }
    JS);

    expect($gap)->toBeGreaterThanOrEqual(0);
});

it('opens the new document menu on screen from the tab bar', function () {
    $this->actingAs(navUser('nav-tabs-new@example.test', 'Clarity Technologies'));

    visit('/dashboard')->resize(390, 844)
        ->click('[aria-label="Create new"]')
        ->assertSee('New invoice')
        ->assertSee('New estimate')
        ->assertScript(
            'function () {
                const r = document.getElementById("tab-bar-new-menu").getBoundingClientRect();
                return r.width > 0 && r.left >= 0 && r.right <= window.innerWidth && r.top >= 0;
            }',
        )
        ->screenshot(filename: 'layout/tab-bar-new-menu')
        ->click('New estimate')
        ->assertPathIs('/estimates/create')
        ->assertNoJavaScriptErrors();
});

it('brings the tab bar back once the field it stepped aside for is gone', function () {
    $this->actingAs(navUser('nav-tabs-typing@example.test', 'Clarity Technologies'));

    $shown = 'function () {
        const bar = document.querySelector("[data-tab-bar]");
        return !!bar && getComputedStyle(bar).display !== "none";
    }';

    // Closing the sheet removes the focused field outright, and a removed
    // element is not guaranteed a focusout. Cancel is clicked from script
    // because a real click would move focus to the button first; on iOS a tap
    // on a button does not, so the field is still focused when it goes.
    $page = visit('/customers')->resize(390, 844);

    $page->assertScript($shown)
        ->click('Add Customer')
        ->type('#customer-name', 'Riverbend Clinics')
        ->assertScript($shown, false)
        ->assertScript('function () { return document.activeElement?.id === "customer-name"; }');

    $page->script('function () {
        Array.from(document.querySelectorAll("button"))
            .find((button) => button.textContent.trim() === "Cancel")
            .click();
    }');

    $page->assertScript('function () { return document.getElementById("customer-name") === null; }')
        ->assertScript($shown)
        ->assertNoJavaScriptErrors();
});
