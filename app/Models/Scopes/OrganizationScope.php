<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrganizationScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Organization scope should only apply when there's an authenticated user
        // Public views (invoices, estimates) should not be filtered by organization
        if (! auth()->check()) {
            return;
        }

        // Get the current user's team (organization) context
        $currentTeam = auth()->user()->currentTeam;

        if (! $currentTeam) {
            // If no current team, user shouldn't see any organization-scoped data
            $builder->whereNull('organization_id');

            return;
        }

        // Apply organization filtering based on the model's organization_id column
        // This will restrict data to only the current user's active organization
        $builder->where('organization_id', $currentTeam->id);
    }
}
