<?php

namespace App\Actions\Jetstream;

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ValidateTeamDeletion
{
    public function validate($user, $team): void
    {
        Gate::forUser($user)->authorize('delete', $team);

        if ($team->personal_team) {
            throw ValidationException::withMessages([
                'team' => __('You may not delete your personal team.'),
            ])->errorBag('deleteTeam');
        }
    }
}
