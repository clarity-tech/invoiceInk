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
    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Action Buttons -->
        <div class="mb-6 no-print flex space-x-3">
            <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-900 text-white font-semibold py-3 px-6 rounded-lg shadow-md transition duration-200">
                {{ __('actions.buttons.print_invoice') }}
            </button>
            <a href="{{ route('invoices.pdf', $invoice->ulid) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg shadow-md transition duration-200 inline-block">
                {{ __('actions.buttons.download_pdf') }}
            </a>
        </div>

        <!-- Invoice Document -->
        @php
            $organization = $invoice->organization;
            $logoUrl = $organization->logo_url;
        @endphp
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <!-- Modern Header with Logo -->
            <div class="px-8 pt-8 pb-6 border-b-2 border-gray-200">
                <div class="flex justify-between items-start">
                    <!-- Left: Logo and Company Info -->
                    <div class="flex-1">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $organization->name }}" class="h-14 mb-4 object-contain" style="max-width: 180px;">
                        @endif
                        <h2 class="text-xl font-bold text-gray-900 mb-1">{{ $organization->name }}</h2>
                        <div class="text-sm text-gray-600 space-y-0.5">
                            <p>{{ $invoice->organizationLocation->name }}</p>
                            <p>{{ $invoice->organizationLocation->address_line_1 }}</p>
                            @if($invoice->organizationLocation->address_line_2)
                                <p>{{ $invoice->organizationLocation->address_line_2 }}</p>
                            @endif
                            <p>{{ $invoice->organizationLocation->city }}, {{ $invoice->organizationLocation->state }} {{ $invoice->organizationLocation->postal_code }}</p>
                            <p>{{ $invoice->organizationLocation->country }}</p>
                        </div>
                        <div class="mt-3 text-sm text-gray-600 space-y-0.5">
                            @if($invoice->organizationLocation->gstin)
                                <p><span class="font-semibold text-gray-700">Tax ID:</span> {{ $invoice->organizationLocation->gstin }}</p>
                            @endif
                            @if($organization->emails && !$organization->emails->isEmpty())
                                @php
                                    $orgEmails = $organization->emails;
                                    $firstOrgEmail = method_exists($orgEmails, 'getFirstEmail') ? $orgEmails->getFirstEmail() : $orgEmails->first();
                                @endphp
                                <p><span class="font-semibold text-gray-700">Email:</span> {{ $firstOrgEmail }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Right: Invoice Title and Details -->
                    <div class="text-right">
                        <h1 class="text-4xl font-bold text-gray-900 mb-2">INVOICE</h1>
                        <p class="text-lg font-semibold text-gray-700 mb-4">{{ $invoice->invoice_number }}</p>

                        <div class="inline-block px-4 py-2 rounded-lg {{ $invoice->status->color() === 'green' ? 'bg-green-100 text-green-800' : ($invoice->status->color() === 'blue' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                            <span class="text-sm font-semibold uppercase">{{ $invoice->status->label() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bill To & Invoice Info Section -->
            <div class="px-8 py-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Bill To -->
                <div>
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Bill To</h3>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-base font-bold text-gray-900 mb-1">{{ $invoice->customerLocation->locatable->name }}</h4>
                        <p class="text-sm text-gray-600 mb-2">{{ $invoice->customerLocation->name }}</p>
                        <div class="text-sm text-gray-600 space-y-0.5">
                            <p>{{ $invoice->customerLocation->address_line_1 }}</p>
                            @if($invoice->customerLocation->address_line_2)
                                <p>{{ $invoice->customerLocation->address_line_2 }}</p>
                            @endif
                            <p>{{ $invoice->customerLocation->city }}, {{ $invoice->customerLocation->state }} {{ $invoice->customerLocation->postal_code }}</p>
                            <p>{{ $invoice->customerLocation->country }}</p>
                        </div>
                        @if($invoice->customerLocation->gstin)
                            <p class="text-sm text-gray-700 mt-2"><span class="font-semibold">Tax ID:</span> {{ $invoice->customerLocation->gstin }}</p>
                        @endif
                        @if($invoice->customerLocation->locatable->emails && !$invoice->customerLocation->locatable->emails->isEmpty())
                            @php
                                $custEmails = $invoice->customerLocation->locatable->emails;
                                $firstCustEmail = method_exists($custEmails, 'getFirstEmail') ? $custEmails->getFirstEmail() : $custEmails->first();
                            @endphp
                            <p class="text-sm text-gray-700 mt-1"><span class="font-semibold">Email:</span> {{ $firstCustEmail }}</p>
                        @endif
                    </div>
                </div>

                <!-- Invoice Details -->
                <div>
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Invoice Details</h3>
                    <div class="space-y-3">
                        @if($invoice->issued_at)
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <span class="text-sm font-semibold text-gray-700">Issue Date:</span>
                                <span class="text-sm text-gray-900">{{ $invoice->issued_at->format('M d, Y') }}</span>
                            </div>
                        @endif
                        @if($invoice->due_at)
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <span class="text-sm font-semibold text-gray-700">Due Date:</span>
                                <span class="text-sm text-gray-900">{{ $invoice->due_at->format('M d, Y') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between py-3 bg-gray-50 rounded-lg px-3 mt-4">
                            <span class="text-base font-bold text-gray-900">Amount Due:</span>
                            <span class="text-xl font-bold text-gray-900">{{ $invoice->formatted_total }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Line Items Table -->
            <div class="px-8 pb-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-800 text-white">
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Description</th>
                                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider" style="width: 80px;">Qty</th>
                                <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider" style="width: 100px;">Rate</th>
                                <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider" style="width: 80px;">Tax %</th>
                                <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider" style="width: 120px;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $index => $item)
                                <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                                    <td class="px-4 py-3 border-b border-gray-200">
                                        <div class="text-sm font-medium text-gray-900">{{ $item->description }}</div>
                                        @if($item->sac_code)
                                            <div class="text-xs text-gray-500 mt-0.5">SAC: {{ $item->sac_code }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-900 border-b border-gray-200">{{ $item->quantity }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-900 border-b border-gray-200">{{ $item->formatted_unit_price }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-900 border-b border-gray-200">{{ $item->formatted_tax_rate }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900 border-b border-gray-200">{{ $item->formatted_line_total }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Totals Section -->
                <div class="mt-6 flex justify-end">
                    <div class="w-80">
                        <div class="space-y-2 mb-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium text-gray-900">{{ $invoice->formatted_subtotal }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Tax:</span>
                                <span class="font-medium text-gray-900">{{ $invoice->formatted_tax }}</span>
                            </div>
                        </div>
                        <div class="border-t-2 border-gray-300 pt-3">
                            <div class="flex justify-between items-center bg-gray-800 text-white rounded-lg px-4 py-3">
                                <span class="text-base font-bold">Total Amount:</span>
                                <span class="text-2xl font-bold">{{ $invoice->formatted_total }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                @if($invoice->notes)
                    <div class="mt-8 bg-blue-50 border-l-4 border-blue-500 rounded-r-lg p-4">
                        <h3 class="text-sm font-bold text-gray-900 mb-2">Notes</h3>
                        <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $invoice->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center no-print">
            <p class="text-xs text-gray-500">{{ __('messages.footer.computer_generated_invoice') }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ __('messages.footer.generated_on', ['date' => now()->format('M d, Y \\a\\t g:i A')]) }}</p>
        </div>
    </div>
</body>
</html>
