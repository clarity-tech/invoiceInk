<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $organization = $this->managedOrganization($request);

        return Inertia::render('Settings/Index', [
            'ai' => [
                'enabled' => (bool) $organization->ai_drafting_enabled,
                'configured' => filled(config('ai.providers.anthropic.key')),
            ],
        ]);
    }

    public function updateAi(Request $request): RedirectResponse
    {
        $organization = $this->managedOrganization($request);

        $request->validate(['enabled' => ['required', 'boolean']]);

        $organization->update(['ai_drafting_enabled' => $request->boolean('enabled')]);

        return back()->with('success', $organization->ai_drafting_enabled ? 'AI features turned on.' : 'AI features turned off.');
    }

    /**
     * The settings hub only holds manage-only settings; gate it to match the
     * client-side nav.
     */
    private function managedOrganization(Request $request): Organization
    {
        $organization = $request->user()->currentTeam;

        abort_unless($organization && $request->user()->hasTeamPermission($organization, 'manage'), 403);

        return $organization;
    }
}
