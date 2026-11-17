<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? null) === \App\Enums\Appearance::DARK])>
<head>
    <meta charset="utf-8">
    {{-- viewport-fit=cover lets the installed app reach the screen's edges; the tab bar and app.css pad back in from them with env(safe-area-inset-*). --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('partials.appearance')
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    {{-- Read by app.ts, which registers or removes the service worker to match (config app.pwa_enabled). --}}
    <meta name="pwa" content="{{ config('app.pwa_enabled') ? 'enabled' : 'disabled' }}">
    @if (config('app.pwa_enabled'))
        <link rel="manifest" href="/manifest.webmanifest">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
    @endif
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="theme-color" content="#7c3aed">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
    <x-inertia::head>
        {{-- Same "Page - App" form as the title callback in app.ts, which replaces this once the page runs. --}}
        <title>{{ isset($pageTitle) ? $pageTitle.' - ' : '' }}{{ config('app.name', 'InvoiceInk') }}</title>
        {{-- A page's `head` prop (FortifyServiceProvider::searchable()), here for crawlers that don't run scripts; app.ts manages it from then on. --}}
        @foreach ($page['props']['head'] ?? [] as $element)
            {!! $element !!}
        @endforeach
    </x-inertia::head>
</head>
<body class="bg-canvas font-sans text-foreground antialiased">
    <x-inertia::app />
</body>
</html>
