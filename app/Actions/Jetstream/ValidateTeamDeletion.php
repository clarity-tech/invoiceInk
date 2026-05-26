<?php

namespace App\Actions\Jetstream;

use Illuminate\Support\Facades\Gate;

class ValidateTeamDeletion
{
    public function validate($user, $team): void
    {
        Gate::forUser($user)->authorize('delete', $team);
    }
}
