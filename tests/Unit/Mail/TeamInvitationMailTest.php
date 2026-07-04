<?php

use App\Mail\TeamInvitation;
use App\Models\Organization;
use App\Models\TeamInvitation as TeamInvitationModel;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->invitation = TeamInvitationModel::factory()->create(['team_id' => $this->organization->id]);
});

test('team invitation email carries the shared embedded platform branding', function () {
    $html = (new TeamInvitation($this->invitation))->render();

    // Shares the transactional emails' branding: the embedded ink-drop mark (CID)
    // + "InvoiceInk" wordmark, the footer tagline, and the header slogan. It no
    // longer references the hosted lockup PNG, which is not dark-mode safe.
    expect($html)->toContain('cid:'.config('branding.logo_cid'))
        ->and($html)->toContain(config('branding.tagline'))
        ->and($html)->toContain((string) config('branding.slogan'))
        ->and($html)->not->toContain(asset(config('branding.logo')));
});

test('team invitation ships dark-mode support in the shared markdown layout', function () {
    $html = (new TeamInvitation($this->invitation))->render();

    // The shared layout now declares dark support and carries a
    // prefers-color-scheme dark theme (in the layout <style>, which survives the
    // markdown CSS inliner — the theme file's @media rules would be stripped).
    expect($html)->toContain('content="light dark"')
        ->and($html)->toContain('prefers-color-scheme: dark');
});

test('team invitation uses the embedded icon + wordmark when logo_external is off', function () {
    config(['branding.logo_external' => false]);

    $html = (new TeamInvitation($this->invitation))->render();

    // Embedded mode references the CID mark and not the external lockup URL.
    expect($html)->toContain('cid:'.config('branding.logo_cid'))
        ->and($html)->not->toContain(asset(config('branding.logo')));
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
