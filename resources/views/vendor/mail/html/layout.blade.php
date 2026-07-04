<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<style>
@media only screen and (max-width: 600px) {
.inner-body {
width: 100% !important;
}

.footer {
width: 100% !important;
}
}

@media only screen and (max-width: 500px) {
.button {
width: 100% !important;
}
}

/* Violet "Ink" half of the wordmark + footer wordmark colour, kept as classes
   (not inline) so the dark-mode block below can lighten them for legibility.
   The theme CSS lives in themes/default.css, but Laravel passes it as the CSS
   argument to the inliner — which strips @media blocks — so the dark theme has
   to live here, inside a <style> tag that survives inlining. Every rule needs
   !important because the theme colours are inlined onto elements. */
.brand-ink {
color: #7c3aed;
}

.footer-brand-text {
color: #1f2937;
}

@media (prefers-color-scheme: dark) {

body,
.wrapper,
.body {
background-color: #0f0f10 !important;
color: #e6e6e6 !important;
}

.inner-body {
background-color: #1e1e1e !important;
border-color: #333333 !important;
}

h1, h2, h3,
.header .logo-text,
.footer-brand-text {
color: #f3f4f6 !important;
}

.brand-ink {
color: #a78bfa !important;
}

a,
.header a {
color: #a78bfa !important;
}

.button-blue,
.button-primary {
background-color: #8b5cf6 !important;
border-color: #8b5cf6 !important;
}

.subcopy {
border-top-color: #333333 !important;
}

.table th {
color: #f3f4f6 !important;
border-bottom-color: #333333 !important;
}

.table td {
color: #cbd5e1 !important;
}

.panel-content {
background-color: #2a2440 !important;
color: #d6d3e0 !important;
}

.panel-content p {
color: #d6d3e0 !important;
}
}
</style>
{!! $head ?? '' !!}
</head>
<body>

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
{!! $header ?? '' !!}

<!-- Email Body -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<!-- Body content -->
<tr>
<td class="content-cell">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</body>
</html>
