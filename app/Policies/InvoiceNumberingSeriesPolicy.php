<?php

namespace App\Policies;

use App\Models\InvoiceNumberingSeries;
use App\Models\User;

class InvoiceNumberingSeriesPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(User $user, InvoiceNumberingSeries $series): bool
    {
        return $user->allTeams()->contains('id', $series->organization_id);
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(User $user, InvoiceNumberingSeries $series): bool
    {
        return $user->allTeams()->contains('id', $series->organization_id);
    }

    public function delete(User $user, InvoiceNumberingSeries $series): bool
    {
        return $user->allTeams()->contains('id', $series->organization_id);
    }
}
