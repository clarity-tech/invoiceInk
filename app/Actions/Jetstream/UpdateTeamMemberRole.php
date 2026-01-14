<?php

namespace App\Actions\Jetstream;

use App\Events\TeamMemberUpdated;
use App\Rules\Role;
use App\Support\Jetstream;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateTeamMemberRole
{
    public function update($user, $team, int $teamMemberId, string $role): void
    {
        Gate::forUser($user)->authorize('updateTeamMember', $team);

        Validator::make([
            'role' => $role,
        ], [
            'role' => ['required', 'string', new Role],
        ])->validate();

        $team->users()->updateExistingPivot($teamMemberId, [
            'role' => $role,
        ]);

        TeamMemberUpdated::dispatch($team->fresh(), Jetstream::findUserByIdOrFail($teamMemberId));
    }
}
