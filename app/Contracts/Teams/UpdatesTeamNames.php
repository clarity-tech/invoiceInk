<?php

namespace App\Contracts\Teams;

use App\Models\Organization;
use App\Models\User;

interface UpdatesTeamNames
{
    public function update(User $user, Organization $team, array $input): void;
}
