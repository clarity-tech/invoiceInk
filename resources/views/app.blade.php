<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? null) === \App\Enums\Appearance::DARK])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.appearance')
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
    <x-inertia::head>
        <title>{{ config('app.name', 'InvoiceInk') }}</title>
    </x-inertia::head>
</head>
<body class="bg-canvas font-sans text-foreground antialiased">
    <x-inertia::app />
</body>
</html>
