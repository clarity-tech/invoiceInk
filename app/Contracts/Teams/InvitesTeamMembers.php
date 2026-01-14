<?php

namespace App\Contracts\Teams;

use App\Models\Organization;
use App\Models\User;

interface InvitesTeamMembers
{
    public function invite(User $user, Organization $team, string $email, ?string $role = null): void;
}
