@php
    $t = 'marketing/claude';
    $serverUrl = rtrim(config('app.url'), '/').'/mcp';
@endphp

<x-marketing-layout
    :title="__($t.'.meta.title', ['app' => config('app.name')])"
    :description="__($t.'.meta.description')"
    path="/claude"
>
    <x-marketing.page-hero :badge="__($t.'.hero.badge')" :title="__($t.'.hero.title')" :accent="__($t.'.hero.accent')">
        {{ __($t.'.hero.intro') }}
        <x-slot:visual>
            <div class="space-y-4 rounded-xl border border-white/10 bg-gray-900/80 p-6 shadow-2xl backdrop-blur-xl">
                <div class="ml-auto max-w-[85%] rounded-2xl rounded-br-sm bg-brand-600/80 px-4 py-3 text-sm">{{ __($t.'.mock.question') }}</div>
                <div class="max-w-[90%] text-sm">
                    <div class="mb-2 inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-xs text-gray-400">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">{!! \App\Support\MarketingIcons::path('clock') !!}</svg>
                        {{ __($t.'.mock.tool') }}
                    </div>
                    <p class="leading-relaxed text-gray-200">{{ __($t.'.mock.answer') }}</p>
                </div>
            </div>
        </x-slot:visual>
    </x-marketing.page-hero>

    <section class="px-6 py-24">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-14 text-center text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __($t.'.tools.heading') }}</h2>
            <div class="grid gap-6 md:grid-cols-3">
                <x-marketing.feature-card icon="file-search" :title="__($t.'.tools.invoices.title')">{{ __($t.'.tools.invoices.body') }}</x-marketing.feature-card>
                <x-marketing.feature-card icon="file-text" :title="__($t.'.tools.invoice.title')">{{ __($t.'.tools.invoice.body') }}</x-marketing.feature-card>
                <x-marketing.feature-card icon="users" :title="__($t.'.tools.customers.title')">{{ __($t.'.tools.customers.body') }}</x-marketing.feature-card>
                <x-marketing.feature-card icon="clock" :title="__($t.'.tools.outstanding.title')">{{ __($t.'.tools.outstanding.body') }}</x-marketing.feature-card>
                <x-marketing.feature-card icon="trending-up" :title="__($t.'.tools.revenue.title')">{{ __($t.'.tools.revenue.body') }}</x-marketing.feature-card>
                <x-marketing.feature-card icon="building-2" :title="__($t.'.tools.organizations.title')">{{ __($t.'.tools.organizations.body') }}</x-marketing.feature-card>
            </div>
        </div>
    </section>

    <section class="px-6 py-12">
        <div class="mx-auto flex max-w-3xl gap-5 rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-8">
            <svg class="h-7 w-7 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! \App\Support\MarketingIcons::path('shield-check') !!}</svg>
            <div>
                <h2 class="mb-2 text-xl font-bold">{{ __($t.'.safety.heading') }}</h2>
                <p class="leading-relaxed text-gray-300">{{ __($t.'.safety.body') }}</p>
            </div>
        </div>
    </section>

    <section class="px-6 py-24">
        <div class="mx-auto max-w-4xl">
            <h2 class="mb-10 text-center text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __($t.'.connect.heading') }}</h2>

            @if (config('mcp.web_enabled'))
                <div class="mb-8 rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                    <div class="mb-1 text-xs font-semibold tracking-wide text-gray-500 uppercase">{{ __($t.'.connect.url_label') }}</div>
                    <code class="font-mono break-all text-brand-300">{{ $serverUrl }}</code>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-8">
                        <h3 class="mb-4 text-lg font-bold">{{ __($t.'.connect.web.title') }}</h3>
                        <ol class="list-decimal space-y-2 pl-5 text-sm leading-relaxed text-gray-300">
                            @foreach (__($t.'.connect.web.steps') as $step)
                                <li>{{ $step }}</li>
                            @endforeach
                        </ol>
                        <p class="mt-4 text-sm text-gray-500">{{ __($t.'.connect.web.team') }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-8">
                        <h3 class="mb-4 text-lg font-bold">{{ __($t.'.connect.code.title') }}</h3>
                        <pre class="rounded-lg bg-black/40 p-4 font-mono text-xs break-all whitespace-pre-wrap text-gray-200"><code>claude mcp add --transport http invoicing {{ $serverUrl }}</code></pre>
                        <p class="mt-4 text-sm text-gray-300">{{ __($t.'.connect.code.then') }}</p>
                    </div>
                </div>
                <p class="mt-6 text-center text-sm text-gray-500">{{ __($t.'.connect.other') }}</p>
            @else
                <p class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 text-center text-gray-300">{{ __($t.'.connect.disabled') }}</p>
            @endif
        </div>
    </section>

    <section class="px-6 py-24">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-14 text-center text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __($t.'.in_app.heading') }}</h2>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <x-marketing.feature-card icon="sparkles" :title="__($t.'.in_app.draft.title')">{{ __($t.'.in_app.draft.body') }}</x-marketing.feature-card>
                <x-marketing.feature-card icon="pen-line" :title="__($t.'.in_app.email.title')">{{ __($t.'.in_app.email.body') }}</x-marketing.feature-card>
                <x-marketing.feature-card icon="bell-ring" :title="__($t.'.in_app.reminder.title')">{{ __($t.'.in_app.reminder.body') }}</x-marketing.feature-card>
                <x-marketing.feature-card icon="hash" :title="__($t.'.in_app.codes.title')" :href="route('marketing.hsn-sac-codes')" :link="__('marketing.features.learn_more')">{{ __($t.'.in_app.codes.body') }}</x-marketing.feature-card>
            </div>
            <p class="mx-auto mt-10 max-w-3xl text-center text-sm leading-relaxed text-gray-400">
                {{ __($t.'.in_app.privacy') }}
                <a href="{{ route('policy.show') }}" class="font-semibold text-brand-300 transition hover:text-brand-200">{{ __($t.'.in_app.privacy_link') }}</a>
            </p>
        </div>
    </section>

    <x-marketing.faq :heading="__($t.'.faq.heading')" :items="__($t.'.faq.items')" />

    <x-marketing.cta-band />
</x-marketing-layout>
