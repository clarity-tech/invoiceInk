@props(['badge', 'title', 'accent'])

<section class="relative overflow-hidden px-6 pt-32 pb-20 sm:pt-40">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute top-0 left-1/3 h-[600px] w-[600px] -translate-x-1/2 rounded-full bg-brand-600/20 blur-[160px]"></div>
        <div class="absolute right-0 bottom-0 h-[300px] w-[300px] rounded-full bg-violet-400/10 blur-[100px]"></div>
    </div>

    <div class="relative z-10 mx-auto grid max-w-6xl items-center gap-16 lg:grid-cols-2">
        <div>
            <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-brand-500/30 bg-brand-500/10 px-4 py-1.5 text-sm font-medium text-brand-300">
                {{ $badge }}
            </div>

            <h1 class="mb-6 text-4xl font-black leading-[0.95] tracking-tight sm:text-6xl">
                {{ $title }}<br>
                <span class="bg-gradient-to-r from-brand-400 via-violet-400 to-fuchsia-400 bg-clip-text text-transparent">{{ $accent }}</span>
            </h1>

            <p class="mb-10 max-w-xl text-lg leading-relaxed text-gray-400">{{ $slot }}</p>

            <div class="flex flex-col gap-4 sm:flex-row">
                @auth
                    <a href="{{ url('/dashboard') }}"
                       class="rounded-full bg-brand-600 px-8 py-4 text-center font-bold shadow-2xl shadow-brand-600/30 transition-all hover:bg-brand-500">
                        {{ __('marketing.nav.dashboard') }}
                    </a>
                @else
                    <a href="{{ route('register') }}"
                       class="group rounded-full bg-brand-600 px-8 py-4 text-center font-bold shadow-2xl shadow-brand-600/30 transition-all hover:scale-105 hover:bg-brand-500">
                        {{ __('marketing.hero.get_started') }}
                        <span class="ml-2 inline-block transition-transform group-hover:translate-x-1">&rarr;</span>
                    </a>
                    <a href="{{ route('login') }}"
                       class="rounded-full border border-white/10 bg-white/5 px-8 py-4 text-center font-semibold transition-all hover:border-white/25 hover:bg-white/10">
                        {{ __('marketing.hero.sign_in') }}
                    </a>
                @endauth
            </div>
        </div>

        @isset($visual)
            <div class="relative" aria-hidden="true">
                <div class="absolute -inset-4 rounded-2xl bg-gradient-to-r from-brand-600/20 via-violet-500/20 to-fuchsia-500/20 opacity-60 blur-2xl"></div>
                <div class="relative">{{ $visual }}</div>
            </div>
        @endisset
    </div>
</section>
