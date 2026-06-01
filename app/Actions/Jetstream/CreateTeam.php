<?php

namespace App\Actions\Jetstream;

use App\Contracts\Teams\CreatesTeams;
use App\Currency;
use App\Events\AddingTeam;
use App\Models\Organization;
use App\Models\User;
use App\Support\Jetstream;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CreateTeam implements CreatesTeams
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  array<string, string>  $input
     */
    public function create(User $user, array $input): Organization
    {
        Gate::forUser($user)->authorize('create', Jetstream::newTeamModel());

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
        ])->validateWithBag('createTeam');

        AddingTeam::dispatch($user);

        // currency is NOT NULL with no DB default; seed a sensible default that the
        // setup wizard lets the owner change. Mirrors CreateNewUser's first org.
        $user->switchTeam($team = $user->ownedTeams()->create([
            'name' => $input['name'],
            'currency' => Currency::default(),
            'setup_completed_at' => null,
        ]));

        return $team;
    }

    /**
     * Get the redirect URL after team creation.
     */
    public function redirectTo(): string
    {
        return route('organization.setup');
    }
}
