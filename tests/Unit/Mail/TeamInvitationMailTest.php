<?php

use App\Mail\TeamInvitation;
use App\Models\Organization;
use App\Models\TeamInvitation as TeamInvitationModel;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->invitation = TeamInvitationModel::factory()->create(['team_id' => $this->organization->id]);
});

test('team invitation email carries the shared platform branding', function () {
    $html = (new TeamInvitation($this->invitation))->render();

    // Same footer logo lockup + tagline as the transactional emails.
    expect($html)->toContain(config('branding.tagline'))
        ->and($html)->toContain(config('branding.logo'))
        // Slogan rendered under the header wordmark.
        ->and($html)->toContain((string) config('branding.slogan'));
});

test('team invitation subject carries the attribution tag when enabled', function () {
    config(['mail.default' => 'array', 'queue.default' => 'sync', 'branding.subject_tag' => true]);

    Mail::to('invitee@example.test')->send(new TeamInvitation($this->invitation));

    $email = Mail::mailer('array')->getSymfonyTransport()->messages()->first()->getOriginalMessage();

    expect($email->getSubject())->toContain('Sent Using '.config('app.name'));
});

test('team invitation subject is clean when the tag is disabled', function () {
    config(['mail.default' => 'array', 'queue.default' => 'sync', 'branding.subject_tag' => false]);

    Mail::to('invitee@example.test')->send(new TeamInvitation($this->invitation));

    $email = Mail::mailer('array')->getSymfonyTransport()->messages()->first()->getOriginalMessage();

    expect($email->getSubject())->not->toContain('Sent Using');
});
