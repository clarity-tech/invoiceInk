@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
@if (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo-v2.1.png" class="logo" alt="Laravel Logo">
@elseif (config('branding.logo_external'))
<img src="{{ config('branding.logo_url') ?: asset(config('branding.logo')) }}" alt="{{ config('app.name') }}" width="158" height="36" style="display: block; margin: 0 auto; border: 0;">
@else
<table cellpadding="0" cellspacing="0" border="0" role="presentation" style="margin: 0 auto; border-collapse: collapse;">
<tr>
<td style="vertical-align: middle; padding-right: 10px;">
<img src="{{ 'cid:'.config('branding.logo_cid') }}" alt="{{ config('app.name') }}" width="36" height="36" style="display: block; border: 0;">
</td>
<td style="vertical-align: middle;">
<span class="logo-text">Invoice<span class="brand-ink">Ink</span></span>
</td>
</tr>
</table>
@endif
</a>
@unless (trim($slot) === 'Laravel')
<p style="margin: 8px 0 0 0; color: #9ca3af; font-size: 12px; letter-spacing: 0.04em; text-align: center;">{{ config('branding.slogan') }}</p>
@endunless
</td>
</tr>
