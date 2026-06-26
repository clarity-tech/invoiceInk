<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center">
<a href="{{ \App\Support\Branding::poweredByUrl('email') }}" style="text-decoration: none; display: inline-block; margin-bottom: 10px;">
@if (config('branding.logo_external'))
<img src="{{ config('branding.logo_url') ?: asset(config('branding.logo')) }}" alt="{{ config('app.name') }}" width="123" height="28" style="display: inline-block; border: 0;">
@else
<img src="{{ 'cid:'.config('branding.logo_cid') }}" alt="{{ config('app.name') }}" width="28" height="28" style="vertical-align: middle; border: 0;"><span style="vertical-align: middle; color: #1f2937; font-weight: 700; font-size: 15px; padding-left: 8px;">Invoice<span style="color: {{ config('branding.color') }};">Ink</span></span>
@endif
</a>
<p style="margin: 0 0 14px 0; color: #6b7280; font-size: 13px;">{{ config('branding.tagline') }}</p>
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
</tr>
</table>
</td>
</tr>
