<?php

namespace App\Actions\Jetstream;

use App\Contracts\Teams\DeletesUsers;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteUser implements DeletesUsers
{
    /**
     * Delete the given user.
     *
     * Owned organizations with other members cannot be deleted—ownership must
     * be transferred first. Personal teams and sole-member organizations are
     * purged automatically.
     */
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Fresh query to get all owned organizations (avoids stale relation cache)
            $ownedTeams = $user->ownedTeams()->get();

            $this->ensureOwnedOrganizationsCanBeDeleted($user, $ownedTeams);

            // Purge owned organizations that have no other members
            $ownedTeams->each(function ($team) use ($user) {
                $otherMembers = $team->users()->where('user_id', '!=', $user->id)->count();

                if ($otherMembers === 0) {
                    $team->purge();
                }
            });

            // Detach from organizations the user is a member of (not owner)
            $user->teams()->detach();

            // Clean up user data
            $user->deleteProfilePhoto();
            $user->tokens->each->delete();
            $user->delete();
        });
    }

    /**
     * Ensure none of the user's owned organizations have other members.
     *
     * If an owned organization has other members, the user must transfer
     * ownership before deleting their account.
     */
    protected function ensureOwnedOrganizationsCanBeDeleted(User $user, \Illuminate\Support\Collection $ownedTeams): void
    {
        $organizationsWithMembers = $ownedTeams
            ->filter(fn ($team) => $team->users()->where('user_id', '!=', $user->id)->count() > 0);

        if ($organizationsWithMembers->isNotEmpty()) {
            $names = $organizationsWithMembers->pluck('name')->join(', ');

            throw ValidationException::withMessages([
                'team' => [__('You must transfer ownership of :names before deleting your account.', ['names' => $names])],
            ]);
        }
    }
}
