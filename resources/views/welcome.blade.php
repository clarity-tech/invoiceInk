@php
    $appName = config('app.name', 'InvoiceInk');
    $root = rtrim(config('app.url'), '/');

    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => $appName,
        'url' => $root.'/',
        'description' => __('marketing.meta.description'),
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'image' => $root.'/images/og-image.png',
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'INR'],
        'publisher' => ['@type' => 'Organization', 'name' => 'Clarity Technologies', 'url' => 'https://claritytech.io'],
        'sameAs' => ['https://github.com/clarity-tech/invoiceInk'],
    ];

    $features = [
        'instant' => 'zap',
        'currencies' => 'currency',
        'pdf' => 'document',
        'organizations' => 'office',
        'tax' => 'calculator',
        'estimates' => 'arrows',
        'ai' => 'sparkles',
        'payments' => 'hand-coins',
        'numbering' => 'hash',
        'templates' => 'mail',
        'claude' => 'message-square-text',
        'open_source' => 'code-xml',
    ];

    $featureLinks = [
        'tax' => route('marketing.gst-invoicing'),
        'ai' => route('marketing.hsn-sac-codes'),
        'claude' => route('marketing.claude'),
        'open_source' => route('marketing.open-source'),
    ];
@endphp

<x-marketing-layout
    :title="__('marketing.meta.title', ['app' => $appName])"
    :description="__('marketing.meta.description')"
>
    <x-slot:head>
        <script type="application/ld+json">@json($structuredData)</script>

        <style>
            /* Heights must match translateY steps in their respective keyframes */
            .hero-line-item { height: 1.25rem; }
            .hero-total-item { height: 2.5rem; }
            .hero-line-wrapper { height: 1.25rem; min-width: 7rem; justify-content: flex-end; }
            .hero-dirham-icon { filter: brightness(0) invert(1); }
            .hero-dirham-icon-gradient { filter: brightness(0) saturate(100%) invert(70%) sepia(50%) saturate(500%) hue-rotate(200deg); }

            /* Each step = .hero-line-item height (1.25rem) */
            @keyframes line-cycle {
                0%, 16% { transform: translateY(0); }
                20%, 36% { transform: translateY(-1.25rem); }
                40%, 56% { transform: translateY(-2.5rem); }
                60%, 76% { transform: translateY(-3.75rem); }
                80%, 96% { transform: translateY(-5rem); }
                100% { transform: translateY(0); }
            }
            /* Each step = .hero-total-item height (2.5rem) */
            @keyframes total-cycle {
                0%, 16% { transform: translateY(0); }
                20%, 36% { transform: translateY(-2.5rem); }
                40%, 56% { transform: translateY(-5rem); }
                60%, 76% { transform: translateY(-7.5rem); }
                80%, 96% { transform: translateY(-10rem); }
                100% { transform: translateY(0); }
            }
            .hero-line-cycle {
                animation: line-cycle 10s ease-in-out infinite;
            }
            .hero-total-cycle {
                animation: total-cycle 10s ease-in-out infinite;
            }
        </style>
    </x-slot:head>

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
                {{ __('marketing.hero.badge') }}
            </div>

            {{-- Headline --}}
            <h1 class="text-5xl sm:text-7xl lg:text-8xl font-black tracking-tight leading-[0.9] mb-6">
                {{ __('marketing.hero.headline') }}<br>
                <span class="bg-gradient-to-r from-brand-400 via-violet-400 to-fuchsia-400 bg-clip-text text-transparent">
                    {{ __('marketing.hero.headline_accent') }}
                </span>
            </h1>

            {{-- Sub --}}
            <p class="max-w-2xl mx-auto text-lg sm:text-xl text-gray-400 leading-relaxed mb-10">
                {{ __('marketing.hero.intro') }}
            </p>

            {{-- CTA --}}
            @guest
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
                    <a href="{{ route('register') }}"
                       class="group relative px-8 py-4 text-base font-bold rounded-full bg-brand-600 hover:bg-brand-500 shadow-2xl shadow-brand-600/30 transition-all hover:shadow-brand-500/40 hover:scale-105">
                        {{ __('marketing.hero.get_started') }}
                        <span class="ml-2 inline-block transition-transform group-hover:translate-x-1">&rarr;</span>
                    </a>
                    <a href="{{ route('login') }}"
                       class="px-8 py-4 text-base font-semibold rounded-full border border-white/10 hover:border-white/25 bg-white/5 hover:bg-white/10 backdrop-blur transition-all">
                        {{ __('marketing.hero.sign_in') }}
                    </a>
                </div>
            @endguest

            {{-- Hero visual — floating invoice mockup --}}
            <div class="relative mx-auto max-w-3xl" aria-hidden="true">
                <div class="absolute -inset-4 bg-gradient-to-r from-brand-600/20 via-violet-500/20 to-fuchsia-500/20 rounded-2xl blur-2xl opacity-60"></div>

                {{-- Floating currency pills --}}
                <div class="absolute -top-3 -left-3 sm:-left-6 px-3 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold font-mono animate-bounce [animation-delay:0s] [animation-duration:3s]">
                    ₹ INR
                </div>
                <div class="absolute -top-3 right-1/4 px-3 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-bold font-mono animate-bounce [animation-delay:0.5s] [animation-duration:3s]">
                    $ USD
                </div>
                <div class="absolute top-1/4 -right-3 sm:-right-6 px-3 py-1.5 rounded-full bg-violet-500/10 border border-violet-500/20 text-violet-400 text-xs font-bold font-mono animate-bounce [animation-delay:1s] [animation-duration:3s]">
                    € EUR
                </div>
                <div class="absolute bottom-1/3 -left-3 sm:-left-6 px-3 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-bold font-mono animate-bounce [animation-delay:1.5s] [animation-duration:3s]">
                    <img src="{{ asset('images/dirham-symbol.svg') }}" alt="AED" class="inline h-3 hero-dirham-icon opacity-80"> AED
                </div>
                <div class="absolute -bottom-3 right-1/3 px-3 py-1.5 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-bold font-mono animate-bounce [animation-delay:2s] [animation-duration:3s]">
                    £ GBP
                </div>

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
                                <div class="text-xs font-bold text-brand-400 tracking-widest uppercase mb-1">{{ __('marketing.mock.invoice') }}</div>
                                <div class="text-2xl font-extrabold text-white">INV-2026-0042</div>
                                <div class="text-sm text-gray-500 mt-1">Feb 10, 2026</div>
                            </div>
                            <div class="text-right">
                                <div class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold">
                                    {{ __('marketing.mock.paid') }}
                                </div>
                            </div>
                        </div>

                        {{-- Line items — all amounts cycle together across currencies --}}
                        <div class="border-t border-white/5">
                            <div class="flex justify-between py-3 text-sm border-b border-white/5">
                                <span class="text-gray-300">{{ __('marketing.mock.web_development') }}</span>
                                <span class="font-mono text-gray-200 relative overflow-hidden inline-flex hero-line-wrapper">
                                    <span class="hero-line-cycle flex flex-col items-end">
                                        <span class="hero-line-item flex items-center gap-1">₹10,37,500</span>
                                        <span class="hero-line-item flex items-center gap-1">$12,500.00</span>
                                        <span class="hero-line-item flex items-center gap-1">€11,600.00</span>
                                        <span class="hero-line-item flex items-center gap-1"><img src="{{ asset('images/dirham-symbol.svg') }}" alt="AED" class="h-3 shrink-0 hero-dirham-icon"> 45,900</span>
                                        <span class="hero-line-item flex items-center gap-1">£9,800.00</span>
                                    </span>
                                </span>
                            </div>
                            <div class="flex justify-between py-3 text-sm border-b border-white/5">
                                <span class="text-gray-300">{{ __('marketing.mock.design') }}</span>
                                <span class="font-mono text-gray-200 relative overflow-hidden inline-flex hero-line-wrapper">
                                    <span class="hero-line-cycle flex flex-col items-end">
                                        <span class="hero-line-item flex items-center gap-1">₹3,98,400</span>
                                        <span class="hero-line-item flex items-center gap-1">$4,800.00</span>
                                        <span class="hero-line-item flex items-center gap-1">€4,450.00</span>
                                        <span class="hero-line-item flex items-center gap-1"><img src="{{ asset('images/dirham-symbol.svg') }}" alt="AED" class="h-3 shrink-0 hero-dirham-icon"> 17,600</span>
                                        <span class="hero-line-item flex items-center gap-1">£3,760.00</span>
                                    </span>
                                </span>
                            </div>
                            <div class="flex justify-between py-3 text-sm border-b border-white/5">
                                <span class="text-gray-300">{{ __('marketing.mock.cloud_setup') }}</span>
                                <span class="font-mono text-gray-200 relative overflow-hidden inline-flex hero-line-wrapper">
                                    <span class="hero-line-cycle flex flex-col items-end">
                                        <span class="hero-line-item flex items-center gap-1">₹1,82,600</span>
                                        <span class="hero-line-item flex items-center gap-1">$2,200.00</span>
                                        <span class="hero-line-item flex items-center gap-1">€2,050.00</span>
                                        <span class="hero-line-item flex items-center gap-1"><img src="{{ asset('images/dirham-symbol.svg') }}" alt="AED" class="h-3 shrink-0 hero-dirham-icon"> 8,080</span>
                                        <span class="hero-line-item flex items-center gap-1">£1,720.00</span>
                                    </span>
                                </span>
                            </div>
                        </div>

                        {{-- Cycling total --}}
                        <div class="flex justify-between items-center pt-6 mt-2">
                            <span class="text-sm text-gray-500 uppercase tracking-wide font-semibold">{{ __('marketing.mock.total') }}</span>
                            <div class="hero-total-item relative overflow-hidden">
                                <div class="hero-total-cycle flex flex-col items-end">
                                    <span class="hero-total-item flex items-center text-3xl font-black bg-gradient-to-r from-brand-400 to-violet-400 bg-clip-text text-transparent">₹16,18,500</span>
                                    <span class="hero-total-item flex items-center text-3xl font-black bg-gradient-to-r from-brand-400 to-violet-400 bg-clip-text text-transparent">$19,500.00</span>
                                    <span class="hero-total-item flex items-center text-3xl font-black bg-gradient-to-r from-brand-400 to-violet-400 bg-clip-text text-transparent">€18,100.00</span>
                                    {{-- Dirham uses a separate gradient-tinted filter since bg-clip-text hides the <img> --}}
                                    <span class="hero-total-item flex items-center gap-1.5 text-3xl font-black"><img src="{{ asset('images/dirham-symbol.svg') }}" alt="AED" class="h-5 shrink-0 hero-dirham-icon-gradient"> <span class="bg-gradient-to-r from-brand-400 to-violet-400 bg-clip-text text-transparent">71,580</span></span>
                                    <span class="hero-total-item flex items-center text-3xl font-black bg-gradient-to-r from-brand-400 to-violet-400 bg-clip-text text-transparent">£15,280.00</span>
                                </div>
                            </div>
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
                    {{ __('marketing.features.heading') }}<br>
                    <span class="text-gray-500">{{ __('marketing.features.heading_accent') }}</span>
                </h2>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                @foreach ($features as $key => $icon)
                    <x-marketing.feature-card
                        :title="__('marketing.features.'.$key.'.title')"
                        :icon="$icon"
                        :href="$featureLinks[$key] ?? null"
                        :link="__('marketing.features.learn_more')"
                    >{{ __("marketing.features.{$key}.body") }}</x-marketing.feature-card>
                @endforeach
            </div>
        </div>
    </section>

    <x-marketing.cta-band />
</x-marketing-layout>
