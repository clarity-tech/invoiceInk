<?php

namespace App\Http\Controllers;

use App\Contracts\Teams\AddsTeamMembers;
use App\Models\TeamInvitation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TeamInvitationController extends Controller
{
    public function accept(Request $request, int $invitationId)
    {
        $invitation = TeamInvitation::whereKey($invitationId)->firstOrFail();

        app(AddsTeamMembers::class)->add(
            $invitation->team->owner,
            $invitation->team,
            $invitation->email,
            $invitation->role
        );

        $invitation->delete();

        return redirect(config('fortify.home'))->banner(
            __('Great! You have accepted the invitation to join the :team team.', ['team' => $invitation->team->name]),
        );
    }

    public function destroy(Request $request, int $invitationId)
    {
        $invitation = TeamInvitation::whereKey($invitationId)->firstOrFail();

        if (! Gate::forUser($request->user())->check('removeTeamMember', $invitation->team)) {
            throw new AuthorizationException;
        }

        $invitation->delete();

        return back(303);
    }
}
