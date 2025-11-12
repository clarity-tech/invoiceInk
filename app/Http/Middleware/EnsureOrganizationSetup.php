<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationSetup
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware if user is not authenticated
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        $currentTeam = $user->currentTeam;

        // Skip middleware if no current team
        if (! $currentTeam) {
            return $next($request);
        }

        // Allow access to setup route regardless of setup status
        if ($request->routeIs('organization.setup')) {
            return $next($request);
        }

        // Allow access to logout and profile routes
        if ($request->routeIs(['logout', 'profile.*', 'user-profile-information.*', 'user-password.*', 'two-factor.*'])) {
            return $next($request);
        }

        // Skip setup enforcement for personal teams (they don't need full business setup)
        if ($currentTeam->personal_team) {
            return $next($request);
        }

        // Check if organization setup is complete
        if ($currentTeam->needsSetup()) {
            // Redirect to setup wizard with a helpful message
            session()->flash('setup-required', 'Please complete your organization setup to continue using the application.');

            return redirect()->route('organization.setup');
        }

        return $next($request);
    }
}
