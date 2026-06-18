<?php

use App\Mail\TeamInvitation;
use App\Models\Organization;
use App\Models\TeamInvitation as TeamInvitationModel;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->invitation = TeamInvitationModel::factory()->create(['team_id' => $this->organization->id]);
});

test('team invitation email carries the shared platform branding', function () {
    $html = (new TeamInvitation($this->invitation))->render();

    // Same footer logo lockup + tagline as the transactional emails.
    expect($html)->toContain(config('branding.tagline'))
        ->and($html)->toContain('email-logo-mark')
        // Slogan rendered under the header wordmark.
        ->and($html)->toContain((string) config('branding.slogan'));
});
