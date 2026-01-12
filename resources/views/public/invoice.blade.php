<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('documents.headers.invoice') }} {{ $invoice->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
        }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        {{-- Action Buttons --}}
        <div class="mb-6 no-print flex space-x-3">
            <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-900 text-white font-semibold py-3 px-6 rounded-lg shadow-md transition duration-200">
                {{ __('actions.buttons.print_invoice') }}
            </button>
            <a href="{{ route('invoices.pdf', $invoice->ulid) }}" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold py-3 px-6 rounded-lg shadow-md transition duration-200 inline-block">
                {{ __('actions.buttons.download_pdf') }}
            </a>
        </div>

        @php
            $organization = $invoice->organization;
            $logoUrl = $organization->logo_url;
            $orgLocation = $invoice->organizationLocation;
            $custLocation = $invoice->customerLocation;
            $isGstInvoice = !empty($orgLocation->gstin);
            $headerTitle = $isGstInvoice ? __('documents.headers.tax_invoice_upper') : __('documents.headers.invoice_upper');
        @endphp

        {{-- Invoice Document --}}
        <div class="bg-white shadow-xl rounded-lg overflow-hidden p-8">
            {{-- A. Header --}}
            <div class="mb-6">
                <div class="flex justify-between items-start">
                    {{-- Left: Logo and Company Info --}}
                    <div class="flex-1">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $organization->name }}" class="h-14 mb-3 object-contain" style="max-width: 180px;">
                        @endif
                        <h2 class="text-lg font-bold text-gray-900">{{ $organization->company_name ?? $organization->name }}</h2>
                        <div class="text-sm text-gray-600 mt-1">
                            <p>{{ $orgLocation->address_line_1 }}@if($orgLocation->address_line_2), {{ $orgLocation->address_line_2 }}@endif</p>
                            <p>{{ $orgLocation->city }} {{ $orgLocation->state }} {{ $orgLocation->postal_code }}</p>
                            <p>{{ $orgLocation->country }}</p>
                        </div>
                        @if($orgLocation->gstin)
                            <p class="text-sm text-gray-700 mt-1">{{ __('documents.fields.gstin') }} {{ $orgLocation->gstin }}</p>
                        @endif
                    </div>

                    {{-- Right: Document Title + Balance Due --}}
                    <div class="text-right">
                        <h1 class="text-3xl font-bold text-brand-700">{{ $headerTitle }}</h1>
                        <p class="text-sm text-gray-600 mt-1"># {{ $invoice->invoice_number }}</p>

                        <div class="mt-4">
                            <p class="text-sm font-semibold text-gray-600">{{ __('documents.financial.balance_due') }}</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $invoice->formatted_total }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- B. Bill To + Metadata --}}
            <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                {{-- Bill To --}}
                <div>
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('documents.headers.bill_to') }}</h3>
                    <h4 class="text-sm font-bold text-gray-900">{{ $custLocation->locatable->name }}</h4>
                    <div class="text-sm text-gray-600 mt-1">
                        <p>{{ $custLocation->address_line_1 }}</p>
                        @if($custLocation->address_line_2)
                            <p>{{ $custLocation->address_line_2 }}</p>
                        @endif
                        <p>{{ $custLocation->city }}</p>
                        <p>{{ $custLocation->postal_code }} {{ $custLocation->state }}</p>
                        <p>{{ $custLocation->country }}</p>
                    </div>
                    @if($custLocation->gstin)
                        <p class="text-sm text-gray-700 mt-1">{{ __('documents.fields.gstin') }} {{ $custLocation->gstin }}</p>
                    @endif
                    @if($custLocation->state)
                        <p class="text-sm text-gray-600 mt-1">{{ __('documents.fields.place_of_supply') }}: {{ $custLocation->state }}@if($custLocation->gstin) ({{ substr($custLocation->gstin, 0, 2) }})@endif</p>
                    @endif
                </div>

                {{-- Invoice Details (right-aligned key:value) --}}
                <div>
                    <table class="ml-auto text-sm">
                        @if($invoice->issued_at)
                            <tr>
                                <td class="py-1 pr-4 text-right font-semibold text-gray-600">{{ __('documents.fields.issue_date') }} :</td>
                                <td class="py-1 text-right text-gray-900">{{ $invoice->issued_at->format('d/m/Y') }}</td>
                            </tr>
                        @endif
                        @if($invoice->due_at)
                            <tr>
                                <td class="py-1 pr-4 text-right font-semibold text-gray-600">{{ __('documents.fields.due_date') }} :</td>
                                <td class="py-1 text-right text-gray-900">{{ $invoice->due_at->format('d/m/Y') }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            {{-- C. Line Items Table --}}
            <div class="mb-6">
                @php
                    $taxColumnHeader = $invoice->tax_type ?: __('documents.table.tax_rate');
                @endphp
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-brand-700 text-white">
                                <th scope="col" class="px-3 py-2.5 text-center text-xs font-bold uppercase tracking-wider" style="width: 40px;">{{ __('documents.table.row_number') }}</th>
                                <th scope="col" class="px-3 py-2.5 text-left text-xs font-bold uppercase tracking-wider">{{ __('documents.table.item_and_description') }}</th>
                                <th scope="col" class="px-3 py-2.5 text-center text-xs font-bold uppercase tracking-wider" style="width: 60px;">{{ __('documents.table.qty') }}</th>
                                <th scope="col" class="px-3 py-2.5 text-right text-xs font-bold uppercase tracking-wider" style="width: 100px;">{{ __('documents.table.rate') }}</th>
                                <th scope="col" class="px-3 py-2.5 text-right text-xs font-bold uppercase tracking-wider" style="width: 100px;">{{ $taxColumnHeader }}</th>
                                <th scope="col" class="px-3 py-2.5 text-right text-xs font-bold uppercase tracking-wider" style="width: 100px;">{{ __('documents.table.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $index => $item)
                                <tr class="border-b border-gray-200">
                                    <td class="px-3 py-3 text-center text-sm text-gray-600">{{ $index + 1 }}</td>
                                    <td class="px-3 py-3">
                                        <div class="text-sm text-gray-900">{{ $item->description }}</div>
                                        @if($item->sac_code)
                                            <div class="text-xs text-gray-500 mt-0.5">{{ __('documents.fields.sac') }} {{ $item->sac_code }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-center text-sm text-gray-900">{{ number_format($item->quantity, 2) }}</td>
                                    <td class="px-3 py-3 text-right text-sm text-gray-900">{{ $item->formatted_unit_price }}</td>
                                    <td class="px-3 py-3 text-right text-sm text-gray-900">
                                        {{ $item->formatted_tax_amount }}
                                        @if($item->tax_rate)
                                            <div class="text-xs text-gray-500">{{ $item->formatted_tax_rate }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-right text-sm text-gray-900">{{ $item->formatted_pre_tax_line_total }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- D. Totals --}}
            <div class="mb-6 flex justify-end">
                <div class="w-72">
                    <div class="border-t border-gray-300">
                        {{-- Sub Total --}}
                        <div class="flex justify-between py-2 text-sm">
                            <span class="text-gray-600 font-semibold">{{ __('documents.financial.subtotal') }}</span>
                            <span class="text-gray-900">{{ $invoice->formatted_subtotal }}</span>
                        </div>

                        {{-- Tax Lines --}}
                        @if(!empty($invoice->tax_breakdown) && is_array($invoice->tax_breakdown))
                            @foreach($invoice->tax_breakdown as $taxName => $taxAmount)
                                @if(is_numeric($taxAmount))
                                    <div class="flex justify-between py-2 text-sm">
                                        <span class="text-gray-600 font-semibold">{{ $taxName }}</span>
                                        <span class="text-gray-900">{{ $invoice->formatMoney($taxAmount) }}</span>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            @if($invoice->tax > 0)
                                <div class="flex justify-between py-2 text-sm">
                                    <span class="text-gray-600 font-semibold">{{ $invoice->tax_type ?: __('documents.financial.tax') }}</span>
                                    <span class="text-gray-900">{{ $invoice->formatted_tax }}</span>
                                </div>
                            @endif
                        @endif

                        {{-- Total --}}
                        <div class="flex justify-between py-2 text-sm border-t border-gray-300">
                            <span class="text-gray-900 font-bold">{{ __('documents.financial.total') }}</span>
                            <span class="text-gray-900 font-bold">{{ $invoice->formatted_total }}</span>
                        </div>

                        {{-- Balance Due (highlighted) --}}
                        <div class="flex justify-between py-3 px-3 bg-gray-100 rounded mt-1">
                            <span class="text-gray-900 font-bold">{{ __('documents.financial.balance_due') }}</span>
                            <span class="text-gray-900 font-bold text-lg">{{ $invoice->formatted_total }}</span>
                        </div>
                    </div>

                    {{-- Total in Words --}}
                    <div class="mt-3 text-sm">
                        <span class="text-gray-600">{{ __('documents.financial.total_in_words') }}:</span>
                        <span class="italic font-semibold text-gray-900 ml-1">{{ $invoice->currency->amountToWords($invoice->total) }}</span>
                    </div>
                </div>
            </div>

            {{-- E. Notes --}}
            @if($invoice->notes)
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-brand-700 mb-1">{{ __('documents.fields.notes') }}</h3>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $invoice->notes }}</p>
                </div>
            @endif

            {{-- F. Bank Details --}}
            @if($organization->hasBankDetails())
                <div class="mb-6">
                    @php $bank = $organization->bank_details; @endphp
                    <p class="text-sm font-semibold text-gray-900">{{ $bank->accountName ?: ($organization->company_name ?? $organization->name) }}</p>
                    @if($bank->accountNumber)
                        <p class="text-sm text-gray-700">Account No: &nbsp;{{ $bank->accountNumber }}</p>
                    @endif
                    @if($bank->bankName)
                        <p class="text-sm text-gray-700">{{ $bank->bankName }}</p>
                    @endif
                    @if($bank->ifsc || $bank->branch)
                        <p class="text-sm text-gray-700">
                            @if($bank->ifsc)IFSC {{ $bank->ifsc }}@endif
                            @if($bank->ifsc && $bank->branch) | @endif
                            @if($bank->branch){{ $bank->branch }} Branch @endif
                        </p>
                    @endif
                    @if($bank->swift)
                        <p class="text-sm text-gray-700">SWIFT Code {{ $bank->swift }}</p>
                    @endif
                    @if($bank->pan)
                        <p class="text-sm text-gray-700">PAN {{ $bank->pan }}</p>
                    @endif
                </div>
            @endif

            {{-- G. Terms & Conditions --}}
            @if($invoice->terms)
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-1">{{ __('documents.fields.terms_and_conditions') }}</h3>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $invoice->terms }}</p>
                </div>
            @endif

            {{-- H. Footer --}}
            <div class="mt-12 pt-4 border-t border-gray-200">
                <div class="text-center">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $organization->name }}" class="h-10 mx-auto mb-2 object-contain opacity-50">
                    @endif
                    <p class="text-xs text-gray-400">{{ __('messages.footer.computer_generated_invoice') }}</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
