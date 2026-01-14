<?php

namespace App\Contracts\Teams;

use App\Models\Organization;
use App\Models\User;

interface CreatesTeams
{
    public function create(User $user, array $input): Organization;
}
