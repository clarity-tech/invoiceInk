@props(['heading', 'items'])

{{-- $items: list<array{question: string, answer: string}> --}}
<section class="px-6 py-24">
    <div class="mx-auto max-w-3xl">
        <h2 class="mb-10 text-center text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $heading }}</h2>
        <div class="divide-y divide-white/5 rounded-2xl border border-white/5 bg-white/[0.02]">
            @foreach ($items as $item)
                <details class="group px-6 py-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-6 font-semibold">
                        {{ $item['question'] }}
                        <span class="text-brand-400 transition-transform group-open:rotate-45" aria-hidden="true">+</span>
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-gray-400">{{ $item['answer'] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
