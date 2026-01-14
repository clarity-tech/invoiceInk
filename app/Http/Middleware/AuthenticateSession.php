<?php

namespace App\Http\Middleware;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Session\Middleware\AuthenticateSession as BaseAuthenticateSession;

class AuthenticateSession extends BaseAuthenticateSession
{
    /**
     * Get the guard instance that should be used by the middleware.
     */
    protected function guard(): StatefulGuard
    {
        return app(StatefulGuard::class);
    }
}
