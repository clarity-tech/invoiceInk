<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => fn () => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'profile_photo_url' => $request->user()->profile_photo_url,
                    'two_factor_enabled' => ! is_null($request->user()->two_factor_secret),
                ] : null,
                'currentTeam' => $request->user()?->currentTeam ? [
                    'id' => $request->user()->currentTeam->id,
                    'name' => $request->user()->currentTeam->name,
                    'company_name' => $request->user()->currentTeam->company_name,
                    'currency' => $request->user()->currentTeam->currency,
                    'personal_team' => $request->user()->currentTeam->personal_team,
                ] : null,
            ],
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'message' => $request->session()->get('message'),
            ],
        ];
    }
}
