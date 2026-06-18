<?php

namespace App\Mail;

use App\Mail\Concerns\AppendsBrandSubjectTag;
use App\Mail\Concerns\EmbedsBrandLogo;
use App\Models\TeamInvitation as TeamInvitationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mime\Email;

class TeamInvitation extends Mailable
{
    use AppendsBrandSubjectTag, EmbedsBrandLogo, Queueable, SerializesModels;

    /**
     * The team invitation instance.
     */
    public TeamInvitationModel $invitation;

    public function __construct(TeamInvitationModel $invitation)
    {
        $this->invitation = $invitation;
    }

    public function build(): static
    {
        return $this->withSymfonyMessage(fn (Email $message) => $this->embedBrandLogo($message))
            ->markdown('emails.team-invitation', ['acceptUrl' => URL::signedRoute('team-invitations.accept', [
                'invitation' => $this->invitation,
            ])])->subject($this->withBrandSubjectTag(__('Team Invitation')));
    }
}
