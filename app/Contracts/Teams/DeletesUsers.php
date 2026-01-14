<?php

namespace App\Contracts\Teams;

use App\Models\User;

interface DeletesUsers
{
    public function delete(User $user): void;
}
