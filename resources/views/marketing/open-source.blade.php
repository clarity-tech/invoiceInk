@php
    $t = 'marketing/open_source';
    $repository = 'https://github.com/clarity-tech/invoiceInk';
    $stackIcons = ['layers', 'code-xml', 'database', 'file-text', 'server', 'zap'];
@endphp

<x-marketing-layout
    :title="__($t.'.meta.title', ['app' => config('app.name')])"
    :description="__($t.'.meta.description')"
    path="/open-source"
>
    <x-marketing.page-hero :badge="__($t.'.hero.badge')" :title="__($t.'.hero.title')" :accent="__($t.'.hero.accent')">
        {{ __($t.'.hero.intro') }}
        <x-slot:visual>
            <div class="overflow-hidden rounded-xl border border-white/10 bg-gray-950/90 shadow-2xl">
                <div class="flex items-center gap-2 border-b border-white/5 px-4 py-3">
                    <span class="h-3 w-3 rounded-full bg-red-500/80"></span>
                    <span class="h-3 w-3 rounded-full bg-yellow-500/80"></span>
                    <span class="h-3 w-3 rounded-full bg-green-500/80"></span>
                </div>
                <pre class="overflow-x-auto p-5 font-mono text-xs leading-6 text-gray-300 sm:text-sm"><code><span class="text-brand-400">$</span> git clone {{ $repository }}.git
<span class="text-brand-400">$</span> cd invoiceInk && cp .env.example .env
<span class="text-gray-500"># {{ __($t.'.hero.terminal_comment') }}</span>
<span class="text-brand-400">$</span> ./vendor/bin/sail up -d
<span class="text-brand-400">$</span> sail php artisan app:setup --seed
<span class="text-brand-400">$</span> sail bun install && sail bun run build</code></pre>
            </div>
        </x-slot:visual>
    </x-marketing.page-hero>

    <div class="-mt-8 px-6 text-center lg:text-left">
        <div class="mx-auto max-w-6xl">
            <a href="{{ $repository }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-6 py-3 font-semibold transition hover:border-white/25 hover:bg-white/10">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! \App\Support\MarketingIcons::path('code-xml') !!}</svg>
                {{ __($t.'.hero.github') }}
            </a>
        </div>
    </div>

    <section class="px-6 py-24">
        <div class="mx-auto max-w-3xl text-center">
            <h2 class="mb-6 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __($t.'.why.heading') }}</h2>
            <p class="text-lg leading-relaxed text-gray-400">{{ __($t.'.why.body') }}</p>
        </div>
    </section>

    <section class="px-6 py-12">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-14 text-center text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __($t.'.stack.heading') }}</h2>
            <div class="grid gap-6 md:grid-cols-3">
                @foreach (__($t.'.stack.items') as $index => $item)
                    <x-marketing.feature-card :icon="$stackIcons[$index]" :title="$item['title']">{{ $item['body'] }}</x-marketing.feature-card>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-6 py-24">
        <div class="mx-auto max-w-3xl rounded-2xl border border-white/5 bg-white/[0.02] p-8 sm:p-10">
            <h2 class="mb-4 text-2xl font-extrabold tracking-tight">{{ __($t.'.self_host.heading') }}</h2>
            <p class="mb-6 leading-relaxed text-gray-300">{{ __($t.'.self_host.intro') }}</p>
            <ul class="mb-8 space-y-2 text-gray-300">
                @foreach (__($t.'.self_host.requirements') as $requirement)
                    <li class="flex gap-3">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-400" aria-hidden="true"></span>
                        {{ $requirement }}
                    </li>
                @endforeach
            </ul>
            <p class="text-gray-400">
                {{ __($t.'.self_host.local') }}
                <a href="{{ $repository }}#readme" target="_blank" rel="noopener" class="font-semibold text-brand-300 transition hover:text-brand-200">{{ __($t.'.self_host.readme') }} <span aria-hidden="true">&rarr;</span></a>
            </p>
        </div>
    </section>

    <x-marketing.faq :heading="__($t.'.faq.heading')" :items="__($t.'.faq.items')" />

    <x-marketing.cta-band />
</x-marketing-layout>
