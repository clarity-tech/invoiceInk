<?php

namespace App\Http\Controllers;

use App\Support\Agent;
use App\Support\Jetstream;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class UserProfileController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('Profile/Show', [
            'user' => $request->user(),
            'sessions' => $this->sessions($request),
            'confirmsTwoFactorAuthentication' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
            'canManageTwoFactorAuthentication' => Features::canManageTwoFactorAuthentication(),
            'canUpdateProfileInformation' => Features::canUpdateProfileInformation(),
            'canUpdatePassword' => Features::enabled(Features::updatePasswords()),
            'hasAccountDeletionFeatures' => Jetstream::hasAccountDeletionFeatures(),
            'sessionsEnabled' => config('session.driver') === 'database',
            'managesProfilePhotos' => Jetstream::managesProfilePhotos(),
        ]);
    }

    /**
     * Get the current sessions for the authenticated user.
     *
     * @return array<int, object>
     */
    protected function sessions(Request $request): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        return collect(
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $request->user()->getAuthIdentifier())
                ->orderBy('last_activity', 'desc')
                ->get()
        )->map(function ($session) use ($request) {
            $agent = tap(new Agent, fn ($agent) => $agent->setUserAgent($session->user_agent));

            return (object) [
                'ip_address' => $session->ip_address,
                'is_current_device' => $session->id === $request->session()->getId(),
                'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                'platform' => $agent->platform(),
                'browser' => $agent->browser(),
                'is_desktop' => $agent->isDesktop(),
            ];
        })->all();
    }
}
