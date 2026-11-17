<?php

use App\Models\User;

/*
 * What search engines may index, checked as a whole rather than page by page:
 * the private sign-in screens must say noindex, and everything the sitemap
 * offers must be indexable and say so consistently.
 */

beforeEach(function () {
    config(['app.url' => 'https://invoiceink.test']);
});

it('keeps the private and throwaway sign-in screens out of search results', function (string $path) {
    $this->get($path)
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
})->with([
    'forgot password' => '/forgot-password',
    'a password reset link' => '/reset-password/a-token?email=someone%40example.test',
]);

it('keeps the screens partway through signing in out of search results', function (string $path, Closure $as) {
    $as($this, User::factory()->withPersonalTeam()->create())
        ->get($path)
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
})->with([
    'two-factor challenge' => ['/two-factor-challenge', fn ($test, User $user) => $test->withSession(['login.id' => $user->id])],
    'confirm password' => ['/user/confirm-password', fn ($test, User $user) => $test->actingAs($user)],
    'verify email' => ['/email/verify', fn ($test, User $user) => $test->actingAs(tap($user)->update(['email_verified_at' => null]))],
]);

it('leaves log in and register to search engines', function (string $path) {
    $this->get($path)
        ->assertOk()
        ->assertHeaderMissing('X-Robots-Tag');
})->with(['/login', '/register']);

it('marks log in and register noindex when the deployment is not indexable', function (string $path) {
    config(['app.indexable' => false]);

    $this->get($path)
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
})->with(['/login', '/register']);
