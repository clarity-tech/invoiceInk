@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
@if (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo-v2.1.png" class="logo" alt="Laravel Logo">
@else
<img src="{{ config('branding.logo_url') ?: asset(config('branding.logo')) }}" alt="{{ config('app.name') }}" width="158" height="36" style="display: block; margin: 0 auto; border: 0;">
@endif
</a>
@unless (trim($slot) === 'Laravel')
<p style="margin: 8px 0 0 0; color: #9ca3af; font-size: 12px; letter-spacing: 0.04em; text-align: center;">{{ config('branding.slogan') }}</p>
@endunless
</td>
</tr>
