<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('documents.headers.invoice') }} {{ $invoice->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            margin: 0.5in;
            size: A4;
        }
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.4;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body class="bg-white text-gray-900">
    <div class="max-w-full mx-auto">
        <!-- Invoice Document -->
        <div class="bg-white">
            <!-- Header -->
            @php
                $organization = $invoice->organizationLocation->locatable;
                $logoUrl = $organization->logo_url;
            @endphp
            <div class="bg-blue-600 text-white px-6 py-4 mb-6">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        @if($logoUrl)
                            <div class="bg-white p-2 rounded">
                                <img src="{{ $logoUrl }}" alt="{{ $organization->name }}" class="h-12 max-w-24 object-contain">
                            </div>
                        @endif
                        <div>
                            <h1 class="text-3xl font-bold">{{ __('documents.headers.invoice_upper') }}</h1>
                            <p class="text-blue-100 text-lg">{{ $invoice->invoice_number }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-blue-100">{{ __('documents.fields.status') }}</div>
                        <span class="inline-block px-3 py-1 rounded-full text-sm font-medium {{ $invoice->status->color() === 'green' ? 'bg-green-500' : ($invoice->status->color() === 'blue' ? 'bg-yellow-500' : 'bg-gray-500') }}">
                            {{ $invoice->status->label() }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Company & Customer Info -->
            <div class="px-6 mb-6">
                <div class="grid grid-cols-2 gap-8">
                    <!-- From -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ __('documents.headers.from') }}</h3>
                        <div class="text-gray-700">
                            <p class="font-medium text-lg">{{ $organization->name }}</p>
                            <p class="text-sm mb-2">{{ $invoice->organizationLocation->name }}</p>
                            <div class="text-sm space-y-1">
                                <p>{{ $invoice->organizationLocation->address_line_1 }}</p>
                                @if($invoice->organizationLocation->address_line_2)
                                    <p>{{ $invoice->organizationLocation->address_line_2 }}</p>
                                @endif
                                <p>{{ $invoice->organizationLocation->city }}, {{ $invoice->organizationLocation->state }} {{ $invoice->organizationLocation->postal_code }}</p>
                                <p>{{ $invoice->organizationLocation->country }}</p>
                                @if($invoice->organizationLocation->gstin)
                                    <p class="mt-2"><span class="font-medium">{{ __('documents.fields.gstin') }}</span> {{ $invoice->organizationLocation->gstin }}</p>
                                @endif
                                @if($invoice->organizationLocation->locatable->emails && !$invoice->organizationLocation->locatable->emails->isEmpty())
                                    @php
                                        $orgEmails = $invoice->organizationLocation->locatable->emails;
                                        $firstOrgEmail = method_exists($orgEmails, 'getFirstEmail') ? $orgEmails->getFirstEmail() : $orgEmails->first();
                                    @endphp
                                    <p><span class="font-medium">{{ __('documents.fields.email') }}</span> {{ $firstOrgEmail }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- To -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ __('documents.headers.to') }}</h3>
                        <div class="text-gray-700">
                            <p class="font-medium text-lg">{{ $invoice->customerLocation->locatable->name }}</p>
                            <p class="text-sm mb-2">{{ $invoice->customerLocation->name }}</p>
                            <div class="text-sm space-y-1">
                                <p>{{ $invoice->customerLocation->address_line_1 }}</p>
                                @if($invoice->customerLocation->address_line_2)
                                    <p>{{ $invoice->customerLocation->address_line_2 }}</p>
                                @endif
                                <p>{{ $invoice->customerLocation->city }}, {{ $invoice->customerLocation->state }} {{ $invoice->customerLocation->postal_code }}</p>
                                <p>{{ $invoice->customerLocation->country }}</p>
                                @if($invoice->customerLocation->gstin)
                                    <p class="mt-2"><span class="font-medium">{{ __('documents.fields.gstin') }}</span> {{ $invoice->customerLocation->gstin }}</p>
                                @endif
                                @if($invoice->customerLocation->locatable->emails && !$invoice->customerLocation->locatable->emails->isEmpty())
                                    @php
                                        $custEmails = $invoice->customerLocation->locatable->emails;
                                        $firstCustEmail = method_exists($custEmails, 'getFirstEmail') ? $custEmails->getFirstEmail() : $custEmails->first();
                                    @endphp
                                    <p><span class="font-medium">{{ __('documents.fields.email') }}</span> {{ $firstCustEmail }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invoice Details -->
                <div class="mt-8 grid grid-cols-3 gap-6">
                    @if($invoice->issued_at)
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 uppercase">{{ __('documents.fields.issue_date') }}</h4>
                            <p class="mt-1 text-sm text-gray-900 font-medium">{{ $invoice->issued_at->format('F j, Y') }}</p>
                        </div>
                    @endif
                    @if($invoice->due_at)
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 uppercase">{{ __('documents.fields.due_date') }}</h4>
                            <p class="mt-1 text-sm text-gray-900 font-medium">{{ $invoice->due_at->format('F j, Y') }}</p>
                        </div>
                    @endif
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 uppercase">{{ __('documents.financial.total_amount') }}</h4>
                        <p class="mt-1 text-lg font-bold text-gray-900">{{ $invoice->formatted_total }}</p>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="px-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('documents.table.items') }}</h3>
                <table class="w-full border-collapse border border-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="border border-gray-300 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('documents.table.description') }}</th>
                            <th class="border border-gray-300 px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('documents.table.qty') }}</th>
                            <th class="border border-gray-300 px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('documents.table.unit_price') }}</th>
                            <th class="border border-gray-300 px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('documents.table.tax_percent') }}</th>
                            <th class="border border-gray-300 px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('documents.table.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach($invoice->items as $item)
                            <tr>
                                <td class="border border-gray-300 px-4 py-3 text-sm text-gray-900">
                                    <div>{{ $item->description }}</div>
                                    @if($item->sac_code)
                                        <div class="text-xs text-gray-500 mt-1">SAC: {{ $item->sac_code }}</div>
                                    @endif
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-sm text-gray-900 text-right">{{ $item->quantity }}</td>
                                <td class="border border-gray-300 px-4 py-3 text-sm text-gray-900 text-right">{{ $item->formatted_unit_price }}</td>
                                <td class="border border-gray-300 px-4 py-3 text-sm text-gray-900 text-right">{{ $item->formatted_tax_rate }}%</td>
                                <td class="border border-gray-300 px-4 py-3 text-sm text-gray-900 text-right">{{ $item->formatted_line_total }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Totals -->
                <div class="mt-6 flex justify-end">
                    <div class="w-80">
                        <div class="bg-gray-50 border border-gray-300 p-4">
                            <div class="flex justify-between py-2">
                                <span class="text-sm text-gray-600">{{ __('documents.financial.subtotal') }}</span>
                                <span class="text-sm font-medium text-gray-900">{{ $invoice->formatted_subtotal }}</span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-sm text-gray-600">{{ __('documents.financial.tax') }}</span>
                                <span class="text-sm font-medium text-gray-900">{{ $invoice->formatted_tax }}</span>
                            </div>
                            <div class="border-t border-gray-300 pt-2 mt-2 flex justify-between">
                                <span class="text-lg font-bold text-gray-900">{{ __('documents.financial.total') }}</span>
                                <span class="text-lg font-bold text-gray-900">{{ $invoice->formatted_total }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Notes -->
            @if($invoice->notes)
                <div class="px-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Notes</h3>
                    <div class="bg-gray-50 border border-gray-300 rounded p-4">
                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $invoice->notes }}</p>
                    </div>
                </div>
            @endif

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-200 mt-8">
                <div class="text-center text-sm text-gray-500">
                    <p>{{ __('messages.footer.computer_generated_invoice') }}</p>
                    <p class="mt-1">{{ __('messages.footer.generated_on', ['date' => now()->format('F j, Y \a\t g:i A')]) }}</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>