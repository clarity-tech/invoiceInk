@php
    $t = 'marketing/gst_invoice_format';
    $statusClasses = [
        'yes' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
        'partly' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
        'no' => 'border-white/10 bg-white/5 text-gray-400',
    ];
    // Clauses with a guide of their own, linked from the checklist row.
    $clauseLinks = [
        'g' => ['href' => route('marketing.hsn-sac-codes'), 'label' => __($t.'.checklist.codes_link')],
    ];
@endphp

<x-marketing-layout
    :title="__($t.'.meta.title', ['app' => config('app.name')])"
    :description="__($t.'.meta.description')"
    path="/gst-invoice-format"
    :breadcrumb="__('marketing.footer.pages.gst_invoice_format')"
>
    <x-marketing.page-hero :badge="__($t.'.hero.badge')" :title="__($t.'.hero.title')" :accent="__($t.'.hero.accent')">
        {{ __($t.'.hero.intro') }}
        <x-slot:visual>
            <x-marketing.gst-invoice-mock />
        </x-slot:visual>
    </x-marketing.page-hero>

    <section class="px-6 py-24">
        <div class="mx-auto max-w-4xl">
            <h2 class="mb-4 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __($t.'.checklist.heading') }}</h2>
            <p class="mb-10 text-gray-400">{{ __($t.'.checklist.intro') }}</p>

            <div class="overflow-hidden rounded-2xl border border-white/5">
                <table class="w-full text-left text-sm">
                    <thead class="hidden bg-white/[0.04] text-gray-400 sm:table-header-group">
                        <tr>
                            <th scope="col" class="w-16 px-4 py-3 font-semibold"><span class="sr-only">{{ __($t.'.checklist.columns.clause') }}</span></th>
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __($t.'.checklist.columns.particular') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold sm:w-2/5">{{ __($t.'.checklist.columns.invoiceink') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach (__($t.'.checklist.rows') as $row)
                            <tr class="flex flex-col gap-2 px-4 py-4 sm:table-row sm:p-0">
                                <td class="font-mono text-xs text-gray-500 sm:px-4 sm:py-4 sm:align-top">({{ $row['clause'] }})</td>
                                <td class="text-gray-200 sm:px-4 sm:py-4 sm:align-top">{{ $row['particular'] }}</td>
                                <td class="sm:px-4 sm:py-4 sm:align-top">
                                    <span class="mb-1.5 inline-block rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $statusClasses[$row['status']] }}">
                                        {{ __($t.'.checklist.status.'.$row['status']) }}
                                    </span>
                                    <span class="block text-gray-400">{{ $row['note'] }}</span>
                                    @isset($clauseLinks[$row['clause']])
                                        <a href="{{ $clauseLinks[$row['clause']]['href'] }}" class="mt-1 inline-block font-semibold text-brand-300 transition hover:text-brand-200">
                                            {{ $clauseLinks[$row['clause']]['label'] }} <span aria-hidden="true">&rarr;</span>
                                        </a>
                                    @endisset
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="px-6 py-12">
        <div class="mx-auto max-w-4xl rounded-2xl border border-white/5 bg-white/[0.02] p-8">
            <h2 class="mb-3 text-xl font-bold">{{ __($t.'.other_documents.heading') }}</h2>
            <p class="leading-relaxed text-gray-300">{{ __($t.'.other_documents.body') }}</p>
            <a href="{{ route('marketing.gst-invoicing') }}" class="mt-4 inline-block font-semibold text-brand-300 transition hover:text-brand-200">
                {{ __($t.'.other_documents.link') }} <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </section>

    <x-marketing.faq :heading="__($t.'.faq.heading')" :items="__($t.'.faq.items')" />

    <p class="mx-auto max-w-3xl px-6 pb-12 text-center text-xs text-gray-500">{{ __($t.'.disclaimer') }}</p>

    <x-marketing.cta-band />
</x-marketing-layout>
