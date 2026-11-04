@php
    $t = 'marketing/gst_invoicing';
@endphp

<x-marketing-layout
    :title="__($t.'.meta.title', ['app' => config('app.name')])"
    :description="__($t.'.meta.description')"
    path="/gst-invoicing"
>
    <x-marketing.page-hero :badge="__($t.'.hero.badge')" :title="__($t.'.hero.title')" :accent="__($t.'.hero.accent')">
        {{ __($t.'.hero.intro') }}
        <x-slot:visual>
            <x-marketing.gst-invoice-mock />
        </x-slot:visual>
    </x-marketing.page-hero>

    <section class="px-6 py-24">
        <div class="mx-auto max-w-6xl">
            <div class="mb-14 text-center">
                <h2 class="mb-4 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __($t.'.split.heading') }}</h2>
                <p class="text-lg text-gray-400">{{ __($t.'.split.intro') }}</p>
            </div>
            <div class="grid gap-6 md:grid-cols-3">
                <x-marketing.feature-card icon="map-pin" :title="__($t.'.split.same_state.title')">{{ __($t.'.split.same_state.body') }}</x-marketing.feature-card>
                <x-marketing.feature-card icon="route" :title="__($t.'.split.other_state.title')">{{ __($t.'.split.other_state.body') }}</x-marketing.feature-card>
                <x-marketing.feature-card icon="id-card" :title="__($t.'.split.how.title')">{{ __($t.'.split.how.body') }}</x-marketing.feature-card>
            </div>
        </div>
    </section>

    <section class="px-6 py-24">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-14 text-center text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __($t.'.features.heading') }}</h2>
            <div class="grid gap-6 md:grid-cols-3">
                <x-marketing.feature-card icon="file-text" :title="__($t.'.features.tax_invoice.title')">{{ __($t.'.features.tax_invoice.body') }}</x-marketing.feature-card>
                <x-marketing.feature-card icon="sparkles" :title="__($t.'.features.codes.title')" :href="route('marketing.hsn-sac-codes')" :link="__($t.'.features.codes.link')">{{ __($t.'.features.codes.body') }}</x-marketing.feature-card>
                <x-marketing.feature-card icon="percent" :title="__($t.'.features.rates.title')">{{ __($t.'.features.rates.body') }}</x-marketing.feature-card>
                <x-marketing.feature-card icon="receipt" :title="__($t.'.features.source_taxes.title')">{{ __($t.'.features.source_taxes.body') }}</x-marketing.feature-card>
                <x-marketing.feature-card icon="calendar-range" :title="__($t.'.features.numbering.title')">{{ __($t.'.features.numbering.body') }}</x-marketing.feature-card>
                <x-marketing.feature-card icon="landmark" :title="__($t.'.features.details.title')">{{ __($t.'.features.details.body') }}</x-marketing.feature-card>
            </div>
        </div>
    </section>

    <section class="px-6 py-12">
        <div class="mx-auto max-w-3xl rounded-2xl border border-amber-500/20 bg-amber-500/5 p-8">
            <h2 class="mb-3 text-xl font-bold">{{ __($t.'.limits.heading') }}</h2>
            <p class="leading-relaxed text-gray-300">{{ __($t.'.limits.body') }}</p>
            <a href="{{ route('marketing.gst-invoice-format') }}" class="mt-4 inline-block font-semibold text-brand-300 transition hover:text-brand-200">
                {{ __($t.'.limits.link') }} <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </section>

    <x-marketing.faq :heading="__($t.'.faq.heading')" :items="__($t.'.faq.items')" />

    <x-marketing.cta-band />
</x-marketing-layout>
