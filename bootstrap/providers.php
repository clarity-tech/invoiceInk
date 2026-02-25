<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\PolicyServiceProvider;
use App\Providers\TeamServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    TeamServiceProvider::class,
    PolicyServiceProvider::class,
];
