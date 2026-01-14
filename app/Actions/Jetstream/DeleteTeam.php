<?php

namespace App\Actions\Jetstream;

use App\Contracts\Teams\DeletesTeams;
use App\Models\Organization;

class DeleteTeam implements DeletesTeams
{
    /**
     * Delete the given team.
     */
    public function delete(Organization $team): void
    {
        $team->purge();
    }
}
