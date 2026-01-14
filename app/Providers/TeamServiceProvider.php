<?php

namespace App\Providers;

use App\Actions\Jetstream\AddTeamMember;
use App\Actions\Jetstream\CreateTeam;
use App\Actions\Jetstream\DeleteTeam;
use App\Actions\Jetstream\DeleteUser;
use App\Actions\Jetstream\InviteTeamMember;
use App\Actions\Jetstream\RemoveTeamMember;
use App\Actions\Jetstream\UpdateTeamName;
use App\Support\Jetstream;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class TeamServiceProvider extends ServiceProvider
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
        $this->configurePermissions();
        $this->registerLivewireComponents();
        $this->registerRedirectMacros();

        // Configure team/organization actions
        Jetstream::createTeamsUsing(CreateTeam::class);
        Jetstream::updateTeamNamesUsing(UpdateTeamName::class);
        Jetstream::addTeamMembersUsing(AddTeamMember::class);
        Jetstream::inviteTeamMembersUsing(InviteTeamMember::class);
        Jetstream::removeTeamMembersUsing(RemoveTeamMember::class);
        Jetstream::deleteTeamsUsing(DeleteTeam::class);

        // Configure user deletion
        Jetstream::deleteUsersUsing(DeleteUser::class);
    }

    /**
     * Configure the roles and permissions that are available within the application.
     */
    protected function configurePermissions(): void
    {
        Jetstream::defaultApiTokenPermissions(['read']);

        Jetstream::role('admin', 'Administrator', [
            'create',
            'read',
            'update',
            'delete',
        ])->description('Administrator users can perform any action.');

        Jetstream::role('editor', 'Editor', [
            'read',
            'create',
            'update',
        ])->description('Editor users have the ability to read, create, and update.');
    }

    /**
     * Register Livewire components previously provided by Jetstream.
     */
    protected function registerLivewireComponents(): void
    {
        Livewire::component('navigation-menu', \App\Livewire\NavigationMenu::class);
        Livewire::component('profile.update-profile-information-form', \App\Livewire\Profile\UpdateProfileInformationForm::class);
        Livewire::component('profile.update-password-form', \App\Livewire\Profile\UpdatePasswordForm::class);
        Livewire::component('profile.two-factor-authentication-form', \App\Livewire\Profile\TwoFactorAuthenticationForm::class);
        Livewire::component('profile.logout-other-browser-sessions-form', \App\Livewire\Profile\LogoutOtherBrowserSessionsForm::class);
        Livewire::component('profile.delete-user-form', \App\Livewire\Profile\DeleteUserForm::class);

        if (\App\Support\JetstreamFeatures::hasTeamFeatures()) {
            Livewire::component('teams.create-team-form', \App\Livewire\Teams\CreateTeamForm::class);
            Livewire::component('teams.update-team-name-form', \App\Livewire\Teams\UpdateTeamNameForm::class);
            Livewire::component('teams.team-member-manager', \App\Livewire\Teams\TeamMemberManager::class);
            Livewire::component('teams.delete-team-form', \App\Livewire\Teams\DeleteTeamForm::class);
        }

        if (\App\Support\JetstreamFeatures::hasApiFeatures()) {
            Livewire::component('api.api-token-manager', \App\Livewire\Api\ApiTokenManager::class);
        }
    }

    /**
     * Register redirect response macros for flash banners.
     */
    protected function registerRedirectMacros(): void
    {
        RedirectResponse::macro('banner', function ($message): RedirectResponse {
            /** @var RedirectResponse $this */
            return $this->with('flash', [
                'bannerStyle' => 'success',
                'banner' => $message,
            ]);
        });

        RedirectResponse::macro('warningBanner', function ($message): RedirectResponse {
            /** @var RedirectResponse $this */
            return $this->with('flash', [
                'bannerStyle' => 'warning',
                'banner' => $message,
            ]);
        });

        RedirectResponse::macro('dangerBanner', function ($message): RedirectResponse {
            /** @var RedirectResponse $this */
            return $this->with('flash', [
                'bannerStyle' => 'danger',
                'banner' => $message,
            ]);
        });
    }
}
