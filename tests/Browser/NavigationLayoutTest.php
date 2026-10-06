<?php

use App\Models\User;

/**
 * Overflow + layout-switch guard for the shared navigation bar (#57).
 *
 * The desktop nav used to appear at `sm:` (640px) while the hamburger
 * disappeared at the same breakpoint, so between 640px and ~1000px neither
 * layout fitted and the document overflowed horizontally — measured at +300px
 * at 640px and +172px at 768px. Both now switch at `lg:` (1024px), the first
 * standard breakpoint the full desktop nav fits in.
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

    // 640 / 768 / 1000 all overflowed before the fix; 1023 and 1024 sit either
    // side of the new breakpoint.
    foreach ([375, 640, 768, 900, 1000, 1023, 1024, 1280, 1440] as $width) {
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
    foreach ([375, 640, 768, 1000, 1023, 1024, 1440] as $width) {
        $page->resize($width, 900)
            ->assertScript(
                'function () {
                    const visible = (el) => !!(el && el.offsetParent !== null);
                    const desktop = document.querySelector("[data-team-dropdown]");
                    const burger = document.querySelector("[aria-label=\'Toggle navigation menu\']");
                    return visible(desktop) !== visible(burger);
                }',
            );
    }
});

it('opens and closes the mobile panel below the breakpoint', function () {
    $this->actingAs(navUser('nav-mobile@example.test', 'Clarity Technologies'));

    $page = visit('/dashboard')->resize(768, 900);

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
