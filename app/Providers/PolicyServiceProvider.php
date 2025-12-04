<?php

namespace App\Providers;

use App\Models\Organization;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Policies\TeamPolicy;

class PolicyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Organization::class, TeamPolicy::class);
    }
}
