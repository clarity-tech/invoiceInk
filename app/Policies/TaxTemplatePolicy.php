<?php

namespace App\Policies;

use App\Models\TaxTemplate;
use App\Models\User;

class TaxTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(User $user, TaxTemplate $taxTemplate): bool
    {
        return $user->allTeams()->contains('id', $taxTemplate->organization_id);
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(User $user, TaxTemplate $taxTemplate): bool
    {
        return $user->allTeams()->contains('id', $taxTemplate->organization_id);
    }

    public function delete(User $user, TaxTemplate $taxTemplate): bool
    {
        return $user->allTeams()->contains('id', $taxTemplate->organization_id);
    }
}
