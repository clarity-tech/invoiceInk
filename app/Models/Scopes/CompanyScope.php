<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Company scope should only apply when there's an authenticated user
        // Public views (invoices, estimates) should not be filtered by company
        if (! auth()->check()) {
            return;
        }

        // For now, we'll implement a basic company scope
        // In a full multi-tenant setup, this would check the current user's selected company
        // For Phase 3, we can implement company-level filtering if needed
    }
}
