@php
    $t = 'marketing/hsn_sac_codes';
@endphp

<x-marketing-layout
    :title="__($t.'.meta.title', ['app' => config('app.name')])"
    :description="__($t.'.meta.description')"
    path="/hsn-sac-codes"
>
    <x-marketing.page-hero :badge="__($t.'.hero.badge')" :title="__($t.'.hero.title')" :accent="__($t.'.hero.accent')">
        {{ __($t.'.hero.intro') }}
        <x-slot:visual>
            <div class="rounded-xl border border-white/10 bg-gray-900/80 p-6 shadow-2xl backdrop-blur-xl">
                <div class="rounded-lg border border-white/10 bg-white/[0.03] px-4 py-3 text-gray-200">{{ __($t.'.mock.description') }}</div>
                <div class="mt-3 flex flex-wrap gap-2 text-sm">
                    <span class="rounded-full border border-brand-500/30 bg-brand-500/10 px-3 py-1 font-mono text-brand-300">{{ __($t.'.mock.code') }}</span>
                    <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-gray-300">{{ __($t.'.mock.rate') }}</span>
                </div>
                <div class="mt-4 flex gap-3 rounded-lg border border-violet-400/20 bg-violet-400/5 p-3 text-sm text-gray-300">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-violet-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">{!! \App\Support\MarketingIcons::path('sparkles') !!}</svg>
                    {{ __($t.'.mock.suggestion') }}
                </div>
            </div>
        </x-slot:visual>
    </x-marketing.page-hero>

    <section class="px-6 py-24">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-14 text-center text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __($t.'.kinds.heading') }}</h2>
            <div class="mx-auto grid max-w-4xl gap-6 md:grid-cols-2">
                <x-marketing.feature-card icon="package" :title="__($t.'.kinds.hsn.title')">{{ __($t.'.kinds.hsn.body') }}</x-marketing.feature-card>
                <x-marketing.feature-card icon="wrench" :title="__($t.'.kinds.sac.title')">{{ __($t.'.kinds.sac.body') }}</x-marketing.feature-card>
            </div>
        </div>
    </section>

    <section class="px-6 py-12">
        <div class="mx-auto grid max-w-6xl gap-12 lg:grid-cols-2">
            <div>
                <h2 class="mb-4 text-3xl font-extrabold tracking-tight">{{ __($t.'.digits.heading') }}</h2>
                <p class="mb-8 text-gray-400">{{ __($t.'.digits.intro') }}</p>
                <div class="overflow-hidden rounded-2xl border border-white/5">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-white/[0.04] text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __($t.'.digits.columns.turnover') }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __($t.'.digits.columns.digits') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach (__($t.'.digits.rows') as $row)
                                <tr>
                                    <td class="px-4 py-4 align-top font-semibold whitespace-nowrap text-gray-200">{{ $row['turnover'] }}</td>
                                    <td class="px-4 py-4 align-top text-gray-400">{{ $row['digits'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-4 text-sm text-gray-500">{{ __($t.'.digits.note') }}</p>
            </div>

            <div>
                <h2 class="mb-8 text-3xl font-extrabold tracking-tight">{{ __($t.'.examples.heading') }}</h2>
                <div class="overflow-hidden rounded-2xl border border-white/5">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-white/[0.04] text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __($t.'.examples.columns.code') }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __($t.'.examples.columns.service') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach (__($t.'.examples.rows') as $row)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-brand-300">{{ $row['code'] }}</td>
                                    <td class="px-4 py-3 text-gray-300">{{ $row['service'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-4 text-sm text-gray-500">{{ __($t.'.examples.note') }}</p>
            </div>
        </div>
    </section>

    <section class="px-6 py-24">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-14 text-center text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __($t.'.in_app.heading') }}</h2>
            <div class="grid gap-6 md:grid-cols-3">
                <x-marketing.feature-card icon="list-checks" :title="__($t.'.in_app.field.title')">{{ __($t.'.in_app.field.body') }}</x-marketing.feature-card>
                <x-marketing.feature-card icon="sparkles" :title="__($t.'.in_app.ai.title')">{{ __($t.'.in_app.ai.body') }}</x-marketing.feature-card>
                <x-marketing.feature-card icon="shield-check" :title="__($t.'.in_app.control.title')" :href="route('policy.show')" :link="__($t.'.in_app.control.link')">{{ __($t.'.in_app.control.body') }}</x-marketing.feature-card>
            </div>
        </div>
    </section>

    <x-marketing.faq :heading="__($t.'.faq.heading')" :items="__($t.'.faq.items')" />

    <p class="mx-auto max-w-3xl px-6 pb-12 text-center text-xs text-gray-500">{{ __($t.'.disclaimer') }}</p>

    <x-marketing.cta-band />
</x-marketing-layout>
