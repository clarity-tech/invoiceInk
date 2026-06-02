<?php

namespace App\Http\Controllers;

use App\Contracts\Teams\AddsTeamMembers;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TeamInvitationController extends Controller
{
    public function accept(Request $request, int $invitationId)
    {
        $invitation = TeamInvitation::with('team.owner')->whereKey($invitationId)->firstOrFail();

        // Brand-new invitee: register through the accept flow (no org of their own).
        // CreateNewUser reads this session key and attaches them on registration.
        if (! User::where('email', $invitation->email)->exists()) {
            session(['pending_invitation_id' => $invitation->id]);

            return redirect()->route('register');
        }

        // Existing user: the signed link is proof, so attach via the owner's authority.
        app(AddsTeamMembers::class)->add(
            $invitation->team->owner,
            $invitation->team,
            $invitation->email,
            $invitation->role
        );

        $invitation->delete();

        $joinedMessage = __('Great! You have accepted the invitation to join the :team team.', ['team' => $invitation->team->name]);

        if ($request->user()?->email === $invitation->email) {
            return redirect(config('fortify.home'))->banner($joinedMessage);
        }

        return redirect()->route('login')->with('status', $joinedMessage.' '.__('Please log in to continue.'));
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
