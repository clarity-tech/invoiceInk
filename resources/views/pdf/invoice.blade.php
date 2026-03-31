<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
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
            <div class="bg-blue-600 text-white px-6 py-4 mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold">INVOICE</h1>
                        <p class="text-blue-100 text-lg">{{ $invoice->invoice_number }}</p>
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
            <div class="px-6 mb-6">
                <div class="grid grid-cols-2 gap-8">
                    <!-- From -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">From:</h3>
                        <div class="text-gray-700">
                            <p class="font-medium text-lg">{{ $invoice->companyLocation->locatable->name }}</p>
                            <p class="text-sm mb-2">{{ $invoice->companyLocation->name }}</p>
                            <div class="text-sm space-y-1">
                                <p>{{ $invoice->companyLocation->address_line_1 }}</p>
                                @if($invoice->companyLocation->address_line_2)
                                    <p>{{ $invoice->companyLocation->address_line_2 }}</p>
                                @endif
                                <p>{{ $invoice->companyLocation->city }}, {{ $invoice->companyLocation->state }} {{ $invoice->companyLocation->postal_code }}</p>
                                <p>{{ $invoice->companyLocation->country }}</p>
                                @if($invoice->companyLocation->gstin)
                                    <p class="mt-2"><span class="font-medium">GSTIN:</span> {{ $invoice->companyLocation->gstin }}</p>
                                @endif
                                @if($invoice->companyLocation->locatable->emails && !$invoice->companyLocation->locatable->emails->isEmpty())
                                    <p><span class="font-medium">Email:</span> {{ $invoice->companyLocation->locatable->emails->first() }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- To -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">To:</h3>
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
                                    <p class="mt-2"><span class="font-medium">GSTIN:</span> {{ $invoice->customerLocation->gstin }}</p>
                                @endif
                                @if($invoice->customerLocation->locatable->emails && !$invoice->customerLocation->locatable->emails->isEmpty())
                                    <p><span class="font-medium">Email:</span> {{ $invoice->customerLocation->locatable->emails->first() }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invoice Details -->
                <div class="mt-8 grid grid-cols-3 gap-6">
                    @if($invoice->issued_at)
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 uppercase">Issue Date</h4>
                            <p class="mt-1 text-sm text-gray-900 font-medium">{{ $invoice->issued_at->format('F j, Y') }}</p>
                        </div>
                    @endif
                    @if($invoice->due_at)
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 uppercase">Due Date</h4>
                            <p class="mt-1 text-sm text-gray-900 font-medium">{{ $invoice->due_at->format('F j, Y') }}</p>
                        </div>
                    @endif
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 uppercase">Total Amount</h4>
                        <p class="mt-1 text-lg font-bold text-gray-900">₹{{ number_format($invoice->total / 100, 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="px-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Items</h3>
                <table class="w-full border-collapse border border-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="border border-gray-300 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="border border-gray-300 px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="border border-gray-300 px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                            <th class="border border-gray-300 px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tax %</th>
                            <th class="border border-gray-300 px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach($invoice->items as $item)
                            <tr>
                                <td class="border border-gray-300 px-4 py-3 text-sm text-gray-900">{{ $item->description }}</td>
                                <td class="border border-gray-300 px-4 py-3 text-sm text-gray-900 text-right">{{ $item->quantity }}</td>
                                <td class="border border-gray-300 px-4 py-3 text-sm text-gray-900 text-right">₹{{ number_format($item->unit_price / 100, 2) }}</td>
                                <td class="border border-gray-300 px-4 py-3 text-sm text-gray-900 text-right">{{ $item->tax_rate }}%</td>
                                <td class="border border-gray-300 px-4 py-3 text-sm text-gray-900 text-right">₹{{ number_format(($item->quantity * $item->unit_price) / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Totals -->
                <div class="mt-6 flex justify-end">
                    <div class="w-80">
                        <div class="bg-gray-50 border border-gray-300 p-4">
                            <div class="flex justify-between py-2">
                                <span class="text-sm text-gray-600">Subtotal:</span>
                                <span class="text-sm font-medium text-gray-900">₹{{ number_format($invoice->subtotal / 100, 2) }}</span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-sm text-gray-600">Tax:</span>
                                <span class="text-sm font-medium text-gray-900">₹{{ number_format($invoice->tax / 100, 2) }}</span>
                            </div>
                            <div class="border-t border-gray-300 pt-2 mt-2 flex justify-between">
                                <span class="text-lg font-bold text-gray-900">Total:</span>
                                <span class="text-lg font-bold text-gray-900">₹{{ number_format($invoice->total / 100, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-200 mt-8">
                <div class="text-center text-sm text-gray-500">
                    <p>This is a computer-generated invoice. No signature required.</p>
                    <p class="mt-1">Generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>