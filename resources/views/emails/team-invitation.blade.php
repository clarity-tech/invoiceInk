@component('mail::message')
{{ __('You have been invited to join :team!', ['team' => $invitation->team->name]) }}

{{ __('Click the button below to accept. If you do not have an account yet, you will be able to create one first.') }}

@component('mail::button', ['url' => $acceptUrl])
{{ __('Accept Invitation') }}
@endcomponent

{{ __('If you did not expect to receive an invitation to this organization, you may discard this email.') }}
@endcomponent
