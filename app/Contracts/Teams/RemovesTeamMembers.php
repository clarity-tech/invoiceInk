<?php

namespace App\Contracts\Teams;

use App\Models\Organization;
use App\Models\User;

interface RemovesTeamMembers
{
    public function remove(User $user, Organization $team, User $teamMember): void;
}
