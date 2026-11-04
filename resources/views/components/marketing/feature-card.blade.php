@props(['title', 'icon', 'href' => null, 'link' => null])

<div class="relative rounded-2xl border border-white/5 bg-white/[0.02] p-8 transition-all hover:border-brand-500/20 hover:bg-white/[0.04]">
    <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-brand-500/10">
        <svg class="h-6 w-6 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            {!! \App\Support\MarketingIcons::path($icon) !!}
        </svg>
    </div>
    <h3 class="mb-2 text-lg font-bold">{{ $title }}</h3>
    <p class="text-sm leading-relaxed text-gray-400">{{ $slot }}</p>
    @if ($href)
        <a href="{{ $href }}" class="mt-4 inline-block text-sm font-semibold text-brand-300 transition hover:text-brand-200">
            {{ $link }} <span aria-hidden="true">&rarr;</span>
        </a>
    @endif
</div>
