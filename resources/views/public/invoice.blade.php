<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
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
            <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Print Invoice
            </button>
            <a href="{{ route('invoices.pdf', $invoice->ulid) }}" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded inline-block">
                Download PDF
            </a>
        </div>

        <!-- Invoice Document -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-blue-600 text-white px-6 py-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold">INVOICE</h1>
                        <p class="text-blue-100">{{ $invoice->invoice_number }}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-blue-100">Status</div>
                        <span class="inline-block px-3 py-1 rounded-full text-sm font-medium
                            {{ $invoice->status === 'paid' ? 'bg-green-500' : 
                               ($invoice->status === 'sent' ? 'bg-yellow-500' : 'bg-gray-500') }}">
                            {{ ucfirst($invoice->status) }}
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
                            <p class="font-medium">{{ $invoice->companyLocation->locatable->name }}</p>
                            <p class="text-sm">{{ $invoice->companyLocation->name }}</p>
                            <div class="mt-2 text-sm">
                                <p>{{ $invoice->companyLocation->address_line_1 }}</p>
                                @if($invoice->companyLocation->address_line_2)
                                    <p>{{ $invoice->companyLocation->address_line_2 }}</p>
                                @endif
                                <p>{{ $invoice->companyLocation->city }}, {{ $invoice->companyLocation->state }} {{ $invoice->companyLocation->postal_code }}</p>
                                <p>{{ $invoice->companyLocation->country }}</p>
                                @if($invoice->companyLocation->gstin)
                                    <p class="mt-1"><span class="font-medium">GSTIN:</span> {{ $invoice->companyLocation->gstin }}</p>
                                @endif
                            </div>
                            @if($invoice->companyLocation->locatable->emails && !$invoice->companyLocation->locatable->emails->isEmpty())
                                <div class="mt-2 text-sm">
                                    <p><span class="font-medium">Email:</span> {{ $invoice->companyLocation->locatable->emails->first() }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- To -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">To:</h3>
                        <div class="text-gray-700">
                            <p class="font-medium">{{ $invoice->customerLocation->locatable->name }}</p>
                            <p class="text-sm">{{ $invoice->customerLocation->name }}</p>
                            <div class="mt-2 text-sm">
                                <p>{{ $invoice->customerLocation->address_line_1 }}</p>
                                @if($invoice->customerLocation->address_line_2)
                                    <p>{{ $invoice->customerLocation->address_line_2 }}</p>
                                @endif
                                <p>{{ $invoice->customerLocation->city }}, {{ $invoice->customerLocation->state }} {{ $invoice->customerLocation->postal_code }}</p>
                                <p>{{ $invoice->customerLocation->country }}</p>
                                @if($invoice->customerLocation->gstin)
                                    <p class="mt-1"><span class="font-medium">GSTIN:</span> {{ $invoice->customerLocation->gstin }}</p>
                                @endif
                            </div>
                            @if($invoice->customerLocation->locatable->emails && !$invoice->customerLocation->locatable->emails->isEmpty())
                                <div class="mt-2 text-sm">
                                    <p><span class="font-medium">Email:</span> {{ $invoice->customerLocation->locatable->emails->first() }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Invoice Details -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                    @if($invoice->issued_at)
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 uppercase">Issue Date</h4>
                            <p class="mt-1 text-sm text-gray-900">{{ $invoice->issued_at->format('F j, Y') }}</p>
                        </div>
                    @endif
                    @if($invoice->due_at)
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 uppercase">Due Date</h4>
                            <p class="mt-1 text-sm text-gray-900">{{ $invoice->due_at->format('F j, Y') }}</p>
                        </div>
                    @endif
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 uppercase">Total Amount</h4>
                        <p class="mt-1 text-lg font-semibold text-gray-900">₹{{ number_format($invoice->total / 100, 2) }}</p>
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
                            @foreach($invoice->items as $item)
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
                                <span class="text-sm font-medium text-gray-900">₹{{ number_format($invoice->subtotal / 100, 2) }}</span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-sm text-gray-600">Tax:</span>
                                <span class="text-sm font-medium text-gray-900">₹{{ number_format($invoice->tax / 100, 2) }}</span>
                            </div>
                            <div class="border-t pt-2 mt-2 flex justify-between">
                                <span class="text-lg font-bold text-gray-900">Total:</span>
                                <span class="text-lg font-bold text-gray-900">₹{{ number_format($invoice->total / 100, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-sm text-gray-500 no-print">
            <p>This is a computer-generated invoice. No signature required.</p>
        </div>
    </div>
</body>
</html>