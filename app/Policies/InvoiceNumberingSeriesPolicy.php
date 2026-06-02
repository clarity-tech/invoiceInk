<?php

namespace App\Policies;

use App\Models\InvoiceNumberingSeries;
use App\Models\User;

class InvoiceNumberingSeriesPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentTeam !== null && $user->hasTeamPermission($user->currentTeam, 'read');
    }

    public function view(User $user, InvoiceNumberingSeries $series): bool
    {
        return $user->hasTeamPermission($series->organization, 'read');
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null && $user->hasTeamPermission($user->currentTeam, 'manage');
    }

    public function update(User $user, InvoiceNumberingSeries $series): bool
    {
        return $user->hasTeamPermission($series->organization, 'manage');
    }

    public function delete(User $user, InvoiceNumberingSeries $series): bool
    {
        return $user->hasTeamPermission($series->organization, 'manage');
    }
}
