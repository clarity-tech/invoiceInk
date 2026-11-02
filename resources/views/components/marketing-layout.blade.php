@props(['title', 'description', 'path' => '/'])

@php
    // Rooted at APP_URL rather than the request, so a copy reached on another
    // host (the *.run.app address) still names the public domain as canonical.
    $root = rtrim(config('app.url'), '/');
    $canonical = $root.'/'.ltrim($path, '/');
    $appName = config('app.name', 'InvoiceInk');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title }}</title>
        <meta name="description" content="{{ $description }}">
        <link rel="canonical" href="{{ $canonical }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $appName }}">
        <meta property="og:locale" content="en_IN">
        <meta property="og:url" content="{{ $canonical }}">
        <meta property="og:title" content="{{ $title }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:image" content="{{ $root }}/images/og-image.png">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="600">
        <meta property="og:image:alt" content="{{ __('marketing.meta.image_alt', ['app' => $appName]) }}">
        <meta name="twitter:card" content="summary_large_image">

        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css'])

        {{ $head ?? '' }}
    </head>
    <body class="font-sans antialiased overflow-x-hidden bg-gray-950 text-white">

        {{-- ===== NAVBAR ===== --}}
        <nav class="fixed top-0 inset-x-0 z-50 backdrop-blur-md bg-gray-950/70 border-b border-white/5">
            <div class="max-w-7xl mx-auto flex items-center justify-between px-6 h-16">
                <a href="/" class="flex items-center group" aria-label="{{ __('marketing.nav.home', ['app' => $appName]) }}">
                    <x-application-logo class="h-7 w-auto transition-transform group-hover:scale-105" />
                </a>

                @if (Route::has('login'))
                    <div class="flex items-center gap-2">
                        @auth
                            <a href="{{ url('/dashboard') }}"
                               class="px-5 py-2 text-sm font-semibold rounded-full bg-brand-600 hover:bg-brand-500 transition-all shadow-lg shadow-brand-600/25">
                                {{ __('marketing.nav.dashboard') }}
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="px-5 py-2 text-sm font-medium text-gray-300 hover:text-white transition">
                                {{ __('marketing.nav.log_in') }}
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="px-5 py-2 text-sm font-semibold rounded-full bg-brand-600 hover:bg-brand-500 transition-all shadow-lg shadow-brand-600/25">
                                    {{ __('marketing.nav.start_free') }}
                                </a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>
        </nav>

        {{ $slot }}

        {{-- ===== FOOTER ===== --}}
        <footer class="border-t border-white/5 py-8 px-6">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <x-application-mark class="size-5" />
                    <span>&copy; {{ date('Y') }} {{ $appName }}</span>
                </div>
                <nav aria-label="{{ __('marketing.footer.label') }}" class="flex items-center gap-5 text-sm text-gray-500">
                    <a href="{{ route('terms.show') }}" class="hover:text-gray-300 transition">{{ __('marketing.footer.terms') }}</a>
                    <a href="{{ route('policy.show') }}" class="hover:text-gray-300 transition">{{ __('marketing.footer.privacy') }}</a>
                    <a href="https://github.com/clarity-tech/invoiceInk" target="_blank" rel="noopener" class="hover:text-gray-300 transition">{{ __('marketing.footer.source') }}</a>
                </nav>
                <a href="https://claritytech.io" target="_blank" rel="noopener" class="text-sm text-gray-600 hover:text-gray-400 transition">
                    {{ __('marketing.footer.made_by') }} <span class="text-gray-500">{{ __('marketing.footer.company') }}</span>
                </a>
            </div>
        </footer>

    </body>
</html>
