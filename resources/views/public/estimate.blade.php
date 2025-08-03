<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estimate {{ $estimate->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Action Buttons -->
        <div class="mb-6 no-print flex space-x-3">
            <button onclick="window.print()" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                Print Estimate
            </button>
            <a href="{{ route('estimates.pdf', $estimate->ulid) }}" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded inline-block">
                Download PDF
            </a>
        </div>

        <!-- Estimate Document -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-green-600 text-white px-6 py-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold">ESTIMATE</h1>
                        <p class="text-green-100">{{ $estimate->invoice_number }}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-green-100">Status</div>
                        <span class="inline-block px-3 py-1 rounded-full text-sm font-medium
                            {{ $estimate->status === 'accepted' ? 'bg-blue-500' : 
                               ($estimate->status === 'sent' ? 'bg-yellow-500' : 'bg-gray-500') }}">
                            {{ ucfirst($estimate->status) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Company & Customer Info -->
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- From -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">From:</h3>
                        <div class="text-gray-700">
                            <p class="font-medium">{{ $estimate->companyLocation->locatable->name }}</p>
                            <p class="text-sm">{{ $estimate->companyLocation->name }}</p>
                            <div class="mt-2 text-sm">
                                <p>{{ $estimate->companyLocation->address_line_1 }}</p>
                                @if($estimate->companyLocation->address_line_2)
                                    <p>{{ $estimate->companyLocation->address_line_2 }}</p>
                                @endif
                                <p>{{ $estimate->companyLocation->city }}, {{ $estimate->companyLocation->state }} {{ $estimate->companyLocation->postal_code }}</p>
                                <p>{{ $estimate->companyLocation->country }}</p>
                                @if($estimate->companyLocation->gstin)
                                    <p class="mt-1"><span class="font-medium">GSTIN:</span> {{ $estimate->companyLocation->gstin }}</p>
                                @endif
                            </div>
                            @if($estimate->companyLocation->locatable->emails && !$estimate->companyLocation->locatable->emails->isEmpty())
                                <div class="mt-2 text-sm">
                                    <p><span class="font-medium">Email:</span> {{ $estimate->companyLocation->locatable->emails->first() }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- To -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">To:</h3>
                        <div class="text-gray-700">
                            <p class="font-medium">{{ $estimate->customerLocation->locatable->name }}</p>
                            <p class="text-sm">{{ $estimate->customerLocation->name }}</p>
                            <div class="mt-2 text-sm">
                                <p>{{ $estimate->customerLocation->address_line_1 }}</p>
                                @if($estimate->customerLocation->address_line_2)
                                    <p>{{ $estimate->customerLocation->address_line_2 }}</p>
                                @endif
                                <p>{{ $estimate->customerLocation->city }}, {{ $estimate->customerLocation->state }} {{ $estimate->customerLocation->postal_code }}</p>
                                <p>{{ $estimate->customerLocation->country }}</p>
                                @if($estimate->customerLocation->gstin)
                                    <p class="mt-1"><span class="font-medium">GSTIN:</span> {{ $estimate->customerLocation->gstin }}</p>
                                @endif
                            </div>
                            @if($estimate->customerLocation->locatable->emails && !$estimate->customerLocation->locatable->emails->isEmpty())
                                <div class="mt-2 text-sm">
                                    <p><span class="font-medium">Email:</span> {{ $estimate->customerLocation->locatable->emails->first() }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Estimate Details -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                    @if($estimate->issued_at)
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 uppercase">Issue Date</h4>
                            <p class="mt-1 text-sm text-gray-900">{{ $estimate->issued_at->format('F j, Y') }}</p>
                        </div>
                    @endif
                    @if($estimate->due_at)
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 uppercase">Valid Until</h4>
                            <p class="mt-1 text-sm text-gray-900">{{ $estimate->due_at->format('F j, Y') }}</p>
                        </div>
                    @endif
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 uppercase">Estimated Total</h4>
                        <p class="mt-1 text-lg font-semibold text-gray-900">₹{{ number_format($estimate->total / 100, 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="px-6 pb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Items</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tax %</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($estimate->items as $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->description }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ $item->quantity }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">₹{{ number_format($item->unit_price / 100, 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($item->tax_rate, 0) }}%</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">₹{{ number_format(($item->quantity * $item->unit_price) / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="mt-6 flex justify-end">
                    <div class="w-full max-w-md">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex justify-between py-2">
                                <span class="text-sm text-gray-600">Subtotal:</span>
                                <span class="text-sm font-medium text-gray-900">₹{{ number_format($estimate->subtotal / 100, 2) }}</span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-sm text-gray-600">Tax:</span>
                                <span class="text-sm font-medium text-gray-900">₹{{ number_format($estimate->tax / 100, 2) }}</span>
                            </div>
                            <div class="border-t pt-2 mt-2 flex justify-between">
                                <span class="text-lg font-bold text-gray-900">Total:</span>
                                <span class="text-lg font-bold text-gray-900">₹{{ number_format($estimate->total / 100, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-sm text-gray-500 no-print">
            <p>This is a computer-generated estimate. Valid until the specified date.</p>
        </div>
    </div>
</body>
</html>