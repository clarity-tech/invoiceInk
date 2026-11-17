<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\TeamInvitation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // Register Fortify views using Inertia
        Fortify::loginView(fn () => $this->searchable(
            Inertia::render('Auth/Login', ['status' => session('status')]),
            'login',
        ));
        Fortify::registerView(function () {
            $invitationId = session('pending_invitation_id');
            $invitation = $invitationId
                ? TeamInvitation::with('team')->find($invitationId)
                : null;

            return $this->searchable(Inertia::render('Auth/Register', [
                'invitation' => $invitation ? [
                    'email' => $invitation->email,
                    'organization' => $invitation->team->name,
                ] : null,
            ]), 'register');
        });
        Fortify::requestPasswordResetLinkView(fn () => Inertia::render('Auth/ForgotPassword', ['status' => session('status')]));
        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('Auth/ResetPassword', ['email' => $request->email, 'token' => $request->route('token')]));
        Fortify::verifyEmailView(fn () => Inertia::render('Auth/VerifyEmail', ['status' => session('status')]));
        Fortify::confirmPasswordView(fn () => Inertia::render('Auth/ConfirmPassword'));
        Fortify::twoFactorChallengeView(fn () => Inertia::render('Auth/TwoFactorChallenge'));

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }

    /**
     * Puts a sign-in screen's title, meta description and canonical URL in the
     * HTML the server sends, for the two screens search engines may index.
     *
     * There is no SSR, so the page's <Head> only runs in the browser and a
     * crawler that doesn't run scripts would see the bare app name. The title
     * goes to the root view and must match the page's own <Head title>. The
     * description and canonical travel in the `head` prop: app.blade.php
     * prints it, and `serverHead` in app.ts hands it to Inertia's head manager,
     * so both leave with the page on the next client-side visit.
     */
    private function searchable(InertiaResponse $page, string $routeName): InertiaResponse
    {
        $canonical = rtrim(config('app.url'), '/').route($routeName, absolute: false);
        $description = __("marketing.auth.{$routeName}.description", ['app' => config('app.name')]);

        return $page
            ->with('head', [
                '<meta name="description" content="'.e($description).'" data-inertia="description">',
                '<link rel="canonical" href="'.e($canonical).'" data-inertia="canonical">',
            ])
            ->withViewData('pageTitle', __("marketing.auth.{$routeName}.title"));
    }
}
