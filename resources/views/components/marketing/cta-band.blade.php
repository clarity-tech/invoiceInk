@guest
    <section class="relative px-6 py-24">
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute top-1/2 left-1/2 h-[600px] w-[600px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-brand-600/15 blur-[150px]"></div>
        </div>
        <div class="relative z-10 mx-auto max-w-3xl text-center">
            <h2 class="mb-6 text-3xl font-extrabold tracking-tight sm:text-5xl">
                {{ __('marketing.cta.heading') }}<br>
                <span class="bg-gradient-to-r from-brand-400 to-fuchsia-400 bg-clip-text text-transparent">{{ __('marketing.cta.heading_accent') }}</span>
            </h2>
            <p class="mb-10 text-lg text-gray-400">{{ __('marketing.cta.body') }}</p>
            <a href="{{ route('register') }}"
               class="group inline-flex items-center rounded-full bg-brand-600 px-10 py-4 text-lg font-bold shadow-2xl shadow-brand-600/30 transition-all hover:scale-105 hover:bg-brand-500 hover:shadow-brand-500/40">
                {{ __('marketing.cta.button') }}
                <span class="ml-2 inline-block transition-transform group-hover:translate-x-1">&rarr;</span>
            </a>
        </div>
    </section>
@endguest
