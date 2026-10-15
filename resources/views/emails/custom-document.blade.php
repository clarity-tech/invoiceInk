@extends('emails.layouts.branded')

@section('title', ucfirst($invoice->type).' '.$invoice->invoice_number)

@section('content')
<div class="email-body">{!! $customBody !!}</div>

@if($includeViewOnlineButton)
<div class="cta-wrapper">
    <a href="{{ $shareLink->url }}" class="cta-button">
        View {{ ucfirst($invoice->type) }} Online
    </a>
    <p class="cta-note">{{ __('messages.share_link.works_until', ['date' => $shareLink->expiresAt->format('j M Y')]) }}</p>
</div>
@endif

@include('emails.partials.organization-footer', [
    'organization' => $invoice->organization,
    'location' => $invoice->organizationLocation,
    'tight' => $includeViewOnlineButton,
])
@endsection
