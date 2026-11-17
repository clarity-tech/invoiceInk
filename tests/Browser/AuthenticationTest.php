<?php

use App\Models\User;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;
use Pest\Browser\Api\Webpage;

it('redirects guests to login', function () {
    $page = $this->visit('/dashboard');

    $page->assertPathIs('/login');
});

it('allows login with valid credentials', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'auth-test@example.test',
        'password' => 'password',
    ]);

    $page = $this->visit('/login');

    $page->fill('#email', $user->email)
        ->fill('#password', 'password')
        ->click('Log in')
        ->assertPathBeginsWith('/dashboard');
});

/*
 * The title, description and canonical the server prints for log in and
 * register, as the page holds them once its scripts have run.
 */
const SEARCH_HEAD = <<<'JS'
    (() => ({
        title: document.title,
        descriptions: [...document.querySelectorAll('meta[name="description"]')].map((meta) => meta.content),
        canonicals: [...document.querySelectorAll('link[rel="canonical"]')].map((link) => new URL(link.href).pathname),
    }))()
    JS;

/**
 * Waits until the page's title reads $title. Inertia changes the URL first and
 * the head a moment later, once the new page has rendered; WebKit shows the gap.
 * The description and canonical change in the same step as the title.
 */
function waitForDocumentTitle(PendingAwaitablePage|AwaitableWebpage|Webpage $page, string $title): void
{
    $page->script('(async () => { while (document.title !== '.json_encode($title).') { await new Promise((resolve) => setTimeout(resolve, 20)); } })()');
}

it('keeps the search tags of log in and register in step on client-side visits', function () {
    $app = ['app' => config('app.name')];

    $page = $this->visit('/login');
    expect($page->script(SEARCH_HEAD))->toBe([
        'title' => 'Log in - '.config('app.name'),
        'descriptions' => [__('marketing.auth.login.description', $app)],
        'canonicals' => ['/login'],
    ]);

    $page->click('Register')->assertPathIs('/register');
    waitForDocumentTitle($page, 'Register - '.config('app.name'));
    expect($page->script(SEARCH_HEAD))->toBe([
        'title' => 'Register - '.config('app.name'),
        'descriptions' => [__('marketing.auth.register.description', $app)],
        'canonicals' => ['/register'],
    ]);

    // A screen without them must not keep the last page's.
    $page->click('Already registered?')->assertPathIs('/login')
        ->click('Forgot your password?')->assertPathIs('/forgot-password');
    waitForDocumentTitle($page, 'Forgot Password - '.config('app.name'));
    expect($page->script(SEARCH_HEAD))->toBe([
        'title' => 'Forgot Password - '.config('app.name'),
        'descriptions' => [],
        'canonicals' => [],
    ]);
});

it('shows error for invalid credentials', function () {
    $page = $this->visit('/login');

    $page->fill('#email', 'nonexistent@example.test')
        ->fill('#password', 'wrongpassword')
        ->click('Log in')
        ->waitForEvent('networkidle')
        ->assertPathIs('/login')
        ->assertSee('Email');
});
