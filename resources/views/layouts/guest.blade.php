<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? null) === \App\Enums\Appearance::DARK])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @include('partials.appearance')

        <title>{{ $title ? $title.' - ' : '' }}{{ config('app.name', 'InvoiceInk') }}</title>
        @if ($attributes->has('description'))
            <meta name="description" content="{{ $attributes->get('description') }}">
        @endif
        {{-- Rooted at APP_URL like the marketing pages, so a copy reached on another host still names the public domain. --}}
        <link rel="canonical" href="{{ rtrim(config('app.url'), '/').'/'.ltrim(request()->path(), '/') }}">
        @unless (config('app.indexable'))
            <meta name="robots" content="noindex, nofollow">
        @endunless

        <!-- Favicon -->
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css'])
    </head>
    <body>
        <div class="font-sans text-foreground antialiased">
            {{ $slot }}
        </div>
    </body>
</html>
