@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
@if (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo-v2.1.png" class="logo" alt="Laravel Logo">
@else
<table cellpadding="0" cellspacing="0" border="0" role="presentation" style="margin: 0 auto; border-collapse: collapse;">
<tr>
<td style="vertical-align: middle; padding-right: 10px;">
<img src="{{ asset('images/email-logo-mark.png') }}" alt="" width="36" height="36" style="display: block; border: 0;">
</td>
<td style="vertical-align: middle;">
<span class="logo-text">{!! $slot !!}</span>
</td>
</tr>
</table>
@endif
</a>
</td>
</tr>
