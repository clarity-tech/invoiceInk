<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center">
<a href="{{ \App\Support\Branding::poweredByUrl('email') }}" style="text-decoration: none; display: inline-block; margin-bottom: 10px;">
<img src="{{ config('branding.logo_url') ?: asset(config('branding.logo')) }}" alt="{{ config('app.name') }}" width="123" height="28" style="display: inline-block; border: 0;">
</a>
<p style="margin: 0 0 14px 0; color: #6b7280; font-size: 13px;">{{ config('branding.tagline') }}</p>
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
</tr>
</table>
</td>
</tr>
