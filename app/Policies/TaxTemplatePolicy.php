<?php

namespace App\Policies;

use App\Models\TaxTemplate;
use App\Models\User;

class TaxTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentTeam !== null && $user->hasTeamPermission($user->currentTeam, 'read');
    }

    public function view(User $user, TaxTemplate $taxTemplate): bool
    {
        return $user->hasTeamPermission($taxTemplate->organization, 'read');
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null && $user->hasTeamPermission($user->currentTeam, 'manage');
    }

    public function update(User $user, TaxTemplate $taxTemplate): bool
    {
        return $user->hasTeamPermission($taxTemplate->organization, 'manage');
    }

    public function delete(User $user, TaxTemplate $taxTemplate): bool
    {
        return $user->hasTeamPermission($taxTemplate->organization, 'manage');
    }
}
