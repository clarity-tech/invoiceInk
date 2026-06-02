<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        // The settings hub only links to manage-only screens (email templates,
        // numbering series); gate it to match the client-side nav.
        $organization = $request->user()->currentTeam;
        abort_unless($organization && $request->user()->hasTeamPermission($organization, 'manage'), 403);

        return Inertia::render('Settings/Index');
    }
}
