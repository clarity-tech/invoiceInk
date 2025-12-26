<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'InvoiceInk') }}</title>

        <!-- Favicon -->
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased overflow-x-hidden bg-gray-950 text-white">

        {{-- ===== NAVBAR ===== --}}
        <nav class="fixed top-0 inset-x-0 z-50 backdrop-blur-md bg-gray-950/70 border-b border-white/5">
            <div class="max-w-7xl mx-auto flex items-center justify-between px-6 h-16">
                {{-- Logo --}}
                <a href="/" class="flex items-center gap-2.5 group">
                    <x-application-mark class="size-8 transition-transform group-hover:scale-110" />
                    <span class="text-lg font-extrabold tracking-tight">
                        Invoice<span class="text-brand-400">Ink</span>
                    </span>
                </a>

                {{-- Nav links --}}
                @if (Route::has('login'))
                    <div class="flex items-center gap-2">
                        @auth
                            <a href="{{ url('/dashboard') }}"
                               class="px-5 py-2 text-sm font-semibold rounded-full bg-brand-600 hover:bg-brand-500 transition-all shadow-lg shadow-brand-600/25">
                                Dashboard &rarr;
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="px-5 py-2 text-sm font-medium text-gray-300 hover:text-white transition">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="px-5 py-2 text-sm font-semibold rounded-full bg-brand-600 hover:bg-brand-500 transition-all shadow-lg shadow-brand-600/25">
                                    Start Free
                                </a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>
        </nav>

        {{-- ===== HERO ===== --}}
        <section class="relative min-h-screen flex items-center justify-center pt-16">
            {{-- Background glow --}}
            <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
                <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] rounded-full bg-brand-600/20 blur-[160px]"></div>
                <div class="absolute bottom-0 left-1/4 w-[400px] h-[400px] rounded-full bg-brand-500/10 blur-[120px]"></div>
                <div class="absolute top-1/3 right-0 w-[300px] h-[300px] rounded-full bg-violet-400/10 blur-[100px]"></div>
            </div>

            {{-- Grid pattern overlay --}}
            <div class="absolute inset-0 pointer-events-none opacity-[0.03]" aria-hidden="true"
                 style="background-image: linear-gradient(rgba(255,255,255,.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.1) 1px, transparent 1px); background-size: 60px 60px;">
            </div>

            <div class="relative z-10 max-w-5xl mx-auto px-6 text-center">
                {{-- Badge --}}
                <div class="inline-flex items-center gap-2 px-4 py-1.5 mb-8 rounded-full border border-brand-500/30 bg-brand-500/10 text-brand-300 text-sm font-medium">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-400"></span>
                    </span>
                    Invoicing that doesn't suck
                </div>

                {{-- Headline --}}
                <h1 class="text-5xl sm:text-7xl lg:text-8xl font-black tracking-tight leading-[0.9] mb-6">
                    Create invoices<br>
                    <span class="bg-gradient-to-r from-brand-400 via-violet-400 to-fuchsia-400 bg-clip-text text-transparent">
                        at the speed of thought.
                    </span>
                </h1>

                {{-- Sub --}}
                <p class="max-w-2xl mx-auto text-lg sm:text-xl text-gray-400 leading-relaxed mb-10">
                    Stop wrestling with spreadsheets. InvoiceInk lets you draft, send, and track
                    invoices in seconds &mdash; multi-currency, tax-ready, beautifully formatted.
                </p>

                {{-- CTA --}}
                @guest
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
                        <a href="{{ route('register') }}"
                           class="group relative px-8 py-4 text-base font-bold rounded-full bg-brand-600 hover:bg-brand-500 shadow-2xl shadow-brand-600/30 transition-all hover:shadow-brand-500/40 hover:scale-105">
                            Get Started Free
                            <span class="ml-2 inline-block transition-transform group-hover:translate-x-1">&rarr;</span>
                        </a>
                        <a href="{{ route('login') }}"
                           class="px-8 py-4 text-base font-semibold rounded-full border border-white/10 hover:border-white/25 bg-white/5 hover:bg-white/10 backdrop-blur transition-all">
                            Sign in
                        </a>
                    </div>
                @endguest

                {{-- Hero visual — floating invoice mockup --}}
                <div class="relative mx-auto max-w-3xl">
                    <div class="absolute -inset-4 bg-gradient-to-r from-brand-600/20 via-violet-500/20 to-fuchsia-500/20 rounded-2xl blur-2xl opacity-60"></div>
                    <div class="relative rounded-xl border border-white/10 bg-gray-900/80 backdrop-blur-xl shadow-2xl overflow-hidden">
                        {{-- Window chrome --}}
                        <div class="flex items-center gap-2 px-4 py-3 border-b border-white/5">
                            <span class="w-3 h-3 rounded-full bg-red-500/80"></span>
                            <span class="w-3 h-3 rounded-full bg-yellow-500/80"></span>
                            <span class="w-3 h-3 rounded-full bg-green-500/80"></span>
                            <span class="flex-1 text-center text-xs text-gray-500 font-mono">invoiceink.app</span>
                        </div>
                        {{-- Invoice preview --}}
                        <div class="p-6 sm:p-10">
                            <div class="flex items-start justify-between mb-8">
                                <div>
                                    <div class="text-xs font-bold text-brand-400 tracking-widest uppercase mb-1">Invoice</div>
                                    <div class="text-2xl font-extrabold text-white">INV-2026-0042</div>
                                    <div class="text-sm text-gray-500 mt-1">Feb 10, 2026</div>
                                </div>
                                <div class="text-right">
                                    <div class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold">
                                        Paid
                                    </div>
                                </div>
                            </div>

                            {{-- Line items --}}
                            <div class="border-t border-white/5">
                                <div class="flex justify-between py-3 text-sm border-b border-white/5">
                                    <span class="text-gray-300">Web Application Development</span>
                                    <span class="font-mono text-gray-200">$12,500.00</span>
                                </div>
                                <div class="flex justify-between py-3 text-sm border-b border-white/5">
                                    <span class="text-gray-300">UI/UX Design &mdash; 40 hrs</span>
                                    <span class="font-mono text-gray-200">$4,800.00</span>
                                </div>
                                <div class="flex justify-between py-3 text-sm border-b border-white/5">
                                    <span class="text-gray-300">Cloud Infrastructure Setup</span>
                                    <span class="font-mono text-gray-200">$2,200.00</span>
                                </div>
                            </div>

                            {{-- Total --}}
                            <div class="flex justify-between items-center pt-6 mt-2">
                                <span class="text-sm text-gray-500 uppercase tracking-wide font-semibold">Total</span>
                                <span class="text-3xl font-black bg-gradient-to-r from-brand-400 to-violet-400 bg-clip-text text-transparent">$19,500.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===== FEATURES ===== --}}
        <section class="relative py-32 px-6">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-20">
                    <h2 class="text-3xl sm:text-5xl font-extrabold tracking-tight mb-4">
                        Everything you need.<br>
                        <span class="text-gray-500">Nothing you don't.</span>
                    </h2>
                </div>

                <div class="grid md:grid-cols-3 gap-6">
                    {{-- Feature 1 --}}
                    <div class="group relative p-8 rounded-2xl border border-white/5 bg-white/[0.02] hover:bg-white/[0.04] hover:border-brand-500/20 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-brand-500/10 flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold mb-2">Instant Invoices</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">Multi-step wizard walks you through it. Line items, taxes, discounts &mdash; done in under a minute.</p>
                    </div>

                    {{-- Feature 2 --}}
                    <div class="group relative p-8 rounded-2xl border border-white/5 bg-white/[0.02] hover:bg-white/[0.04] hover:border-brand-500/20 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-brand-500/10 flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold mb-2">Multi-Currency</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">INR, USD, EUR, AED, GBP and more. Each organization gets its own currency with matching tax templates.</p>
                    </div>

                    {{-- Feature 3 --}}
                    <div class="group relative p-8 rounded-2xl border border-white/5 bg-white/[0.02] hover:bg-white/[0.04] hover:border-brand-500/20 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-brand-500/10 flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold mb-2">PDF & Email</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">Generate pixel-perfect PDFs and email them directly. Public shareable links for your clients, no login needed.</p>
                    </div>

                    {{-- Feature 4 --}}
                    <div class="group relative p-8 rounded-2xl border border-white/5 bg-white/[0.02] hover:bg-white/[0.04] hover:border-brand-500/20 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-brand-500/10 flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3H21m-3.75 3H21" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold mb-2">Multi-Organization</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">Run multiple businesses from one account. Each org gets its own customers, numbering series, and tax setup.</p>
                    </div>

                    {{-- Feature 5 --}}
                    <div class="group relative p-8 rounded-2xl border border-white/5 bg-white/[0.02] hover:bg-white/[0.04] hover:border-brand-500/20 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-brand-500/10 flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zM8.25 6h7.5v2.25h-7.5V6zM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0012 2.25z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold mb-2">GST & Tax Ready</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">India GST, UAE VAT, US Sales Tax, EU VAT &mdash; flexible tax templates per country, per organization.</p>
                    </div>

                    {{-- Feature 6 --}}
                    <div class="group relative p-8 rounded-2xl border border-white/5 bg-white/[0.02] hover:bg-white/[0.04] hover:border-brand-500/20 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-brand-500/10 flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold mb-2">Estimates &rarr; Invoices</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">Send estimates, get approval, convert to invoice with one click. The whole quote-to-cash flow, sorted.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===== CTA BAND ===== --}}
        @guest
            <section class="relative py-24 px-6">
                <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full bg-brand-600/15 blur-[150px]"></div>
                </div>
                <div class="relative z-10 max-w-3xl mx-auto text-center">
                    <h2 class="text-3xl sm:text-5xl font-extrabold tracking-tight mb-6">
                        Ready to get paid<br>
                        <span class="bg-gradient-to-r from-brand-400 to-fuchsia-400 bg-clip-text text-transparent">faster?</span>
                    </h2>
                    <p class="text-gray-400 text-lg mb-10">Create your first invoice in under 60 seconds. No credit card required.</p>
                    <a href="{{ route('register') }}"
                       class="group inline-flex items-center px-10 py-4 text-lg font-bold rounded-full bg-brand-600 hover:bg-brand-500 shadow-2xl shadow-brand-600/30 transition-all hover:shadow-brand-500/40 hover:scale-105">
                        Start for Free
                        <span class="ml-2 inline-block transition-transform group-hover:translate-x-1">&rarr;</span>
                    </a>
                </div>
            </section>
        @endguest

        {{-- ===== FOOTER ===== --}}
        <footer class="border-t border-white/5 py-8 px-6">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <x-application-mark class="size-5" />
                    <span>&copy; {{ date('Y') }} {{ config('app.name', 'InvoiceInk') }}</span>
                </div>
                <a href="https://claritytech.io" target="_blank" rel="noopener" class="text-sm text-gray-600 hover:text-gray-400 transition">
                    Made with precision by <span class="text-gray-500">Clarity Technologies</span>
                </a>
            </div>
        </footer>

    </body>
</html>
