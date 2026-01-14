<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TeamController extends Controller
{
    public function show(Request $request, int $teamId)
    {
        $team = Organization::findOrFail($teamId);

        Gate::authorize('view', $team);

        return view('teams.show', [
            'user' => $request->user(),
            'team' => $team,
        ]);
    }

    public function create(Request $request)
    {
        Gate::authorize('create', new Organization);

        return view('teams.create', [
            'user' => $request->user(),
        ]);
    }
}
