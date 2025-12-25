<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'InvoiceInk') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 font-sans antialiased">
        <div class="min-h-screen flex flex-col items-center justify-center px-6">
            <!-- Navigation -->
            <header class="fixed top-0 w-full py-4 px-6">
                @if (Route::has('login'))
                    <nav class="flex items-center justify-end gap-3 max-w-7xl mx-auto">
                        @auth
                            <a href="{{ url('/dashboard') }}"
                               class="inline-flex items-center px-4 py-2 bg-brand-600 text-white text-sm font-medium rounded-lg hover:bg-brand-700 transition">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition">
                                Log in
                            </a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="inline-flex items-center px-4 py-2 bg-brand-600 text-white text-sm font-medium rounded-lg hover:bg-brand-700 transition">
                                    Register
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </header>

            <!-- Hero -->
            <main class="text-center max-w-2xl">
                <div class="mb-8">
                    <x-application-mark class="size-20 mx-auto" />
                </div>

                <h1 class="text-4xl sm:text-5xl font-bold tracking-tight mb-4">
                    <span class="text-gray-900 dark:text-white">Invoice</span><span class="text-brand-600">Ink</span>
                </h1>

                <p class="text-lg sm:text-xl text-gray-600 dark:text-gray-400 mb-10">
                    Simple, powerful invoicing for your business
                </p>

                @guest
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center px-8 py-3 bg-brand-600 text-white text-base font-semibold rounded-lg hover:bg-brand-700 shadow-sm transition">
                            Get Started
                        </a>
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center px-8 py-3 text-base font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm transition">
                            Log in
                        </a>
                    </div>
                @endguest
            </main>

            <!-- Footer -->
            <footer class="absolute bottom-0 py-6 text-center text-sm text-gray-400 dark:text-gray-600">
                &copy; {{ date('Y') }} {{ config('app.name', 'InvoiceInk') }}. All rights reserved.
            </footer>
        </div>
    </body>
</html>
