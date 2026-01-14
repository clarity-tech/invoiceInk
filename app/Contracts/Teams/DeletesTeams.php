<?php

namespace App\Contracts\Teams;

use App\Models\Organization;

interface DeletesTeams
{
    public function delete(Organization $team): void;
}
