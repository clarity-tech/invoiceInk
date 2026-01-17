<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Policies\CustomerPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\TeamPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PolicyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Organization::class, TeamPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
    }
}
