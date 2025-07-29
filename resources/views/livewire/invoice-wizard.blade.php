<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        @if ($showInvoices)
            <!-- Header -->
            <div class="mb-6 flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-900">Invoices & Estimates</h1>
                <div class="space-x-2">
                    <button wire:click="create; type = 'estimate'" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Create Estimate
                    </button>
                    <button wire:click="create; type = 'invoice'" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Create Invoice
                    </button>
                </div>
            </div>

            @if (session()->has('message'))
                <div class="mb-4 p-4 text-green-700 bg-green-100 border border-green-300 rounded">
                    {{ session('message') }}
                </div>
            @endif

            <!-- Invoices List -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company → Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($this->invoices as $invoice)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $invoice->type === 'invoice' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                            {{ strtoupper($invoice->type) }}
                                        </span>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $invoice->invoice_number }}</div>
                                            @if($invoice->issued_at)
                                                <div class="text-sm text-gray-500">{{ $invoice->issued_at->format('M d, Y') }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ $invoice->companyLocation->locatable->name ?? 'N/A' }} 
                                        → {{ $invoice->customerLocation->locatable->name ?? 'N/A' }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $invoice->companyLocation->city ?? '' }} → {{ $invoice->customerLocation->city ?? '' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">₹{{ number_format($invoice->total / 100, 2) }}</div>
                                    @if($invoice->tax > 0)
                                        <div class="text-sm text-gray-500">Tax: ₹{{ number_format($invoice->tax / 100, 2) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : 
                                           ($invoice->status === 'sent' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                        {{ ucfirst($invoice->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button wire:click="edit({{ $invoice->id }})" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                    <button wire:click="delete({{ $invoice->id }})" 
                                            wire:confirm="Are you sure you want to delete this {{ $invoice->type }}?"
                                            class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    No documents found. Create your first invoice or estimate using the buttons above.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                
                <div class="px-6 py-3 border-t border-gray-200">
                    {{ $this->invoices->links() }}
                </div>
            </div>
        @else
            <!-- Wizard Form -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-800">
                            {{ $editingId ? 'Edit' : 'Create' }} {{ ucfirst($type) }}
                        </h2>
                        
                        <!-- Step Indicator -->
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center">
                                <div class="flex items-center text-sm">
                                    <span class="w-8 h-8 rounded-full {{ $currentStep >= 1 ? 'bg-blue-500 text-white' : 'bg-gray-300' }} flex items-center justify-center">1</span>
                                    <span class="ml-2 {{ $currentStep >= 1 ? 'text-blue-600' : 'text-gray-500' }}">Details</span>
                                </div>
                                <div class="w-8 border-t-2 {{ $currentStep >= 2 ? 'border-blue-500' : 'border-gray-300' }} mx-2"></div>
                                <div class="flex items-center text-sm">
                                    <span class="w-8 h-8 rounded-full {{ $currentStep >= 2 ? 'bg-blue-500 text-white' : 'bg-gray-300' }} flex items-center justify-center">2</span>
                                    <span class="ml-2 {{ $currentStep >= 2 ? 'text-blue-600' : 'text-gray-500' }}">Items</span>
                                </div>
                                <div class="w-8 border-t-2 {{ $currentStep >= 3 ? 'border-blue-500' : 'border-gray-300' }} mx-2"></div>
                                <div class="flex items-center text-sm">
                                    <span class="w-8 h-8 rounded-full {{ $currentStep >= 3 ? 'bg-blue-500 text-white' : 'bg-gray-300' }} flex items-center justify-center">3</span>
                                    <span class="ml-2 {{ $currentStep >= 3 ? 'text-blue-600' : 'text-gray-500' }}">Review</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form wire:submit="save" class="p-6">
                    @if ($currentStep === 1)
                        <!-- Step 1: Basic Details -->
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                                    <select wire:model="type" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="invoice">Invoice</option>
                                        <option value="estimate">Estimate</option>
                                    </select>
                                </div>

                                <div></div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Company *</label>
                                    <select wire:model.live="company_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Company</option>
                                        @foreach($this->companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('company_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer *</label>
                                    <select wire:model.live="customer_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Customer</option>
                                        @foreach($this->customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('customer_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                @if($company_id && $this->companyLocations->count() > 0)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Company Location *</label>
                                        <select wire:model="company_location_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">Select Location</option>
                                            @foreach($this->companyLocations as $location)
                                                <option value="{{ $location->id }}">{{ $location->name }} - {{ $location->city }}</option>
                                            @endforeach
                                        </select>
                                        @error('company_location_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                @endif

                                @if($customer_id && $this->customerLocations->count() > 0)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Customer Location *</label>
                                        <select wire:model="customer_location_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">Select Location</option>
                                            @foreach($this->customerLocations as $location)
                                                <option value="{{ $location->id }}">{{ $location->name }} - {{ $location->city }}</option>
                                            @endforeach
                                        </select>
                                        @error('customer_location_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                @endif

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Issue Date</label>
                                    <input wire:model="issued_at" type="date" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('issued_at') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                                    <input wire:model="due_at" type="date" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('due_at') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    @elseif ($currentStep === 2)
                        <!-- Step 2: Items -->
                        <div class="space-y-6">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-medium text-gray-900">Line Items</h3>
                                <button type="button" wire:click="addItem" class="text-blue-500 hover:text-blue-700 text-sm">+ Add Item</button>
                            </div>

                            <div class="space-y-4">
                                @foreach($items as $index => $item)
                                    <div class="grid grid-cols-12 gap-4 items-end border border-gray-200 rounded-lg p-4">
                                        <div class="col-span-5">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                                            <input wire:model.live="items.{{ $index }}.description" type="text" 
                                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error("items.{$index}.description") <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Qty *</label>
                                            <input wire:model.live="items.{{ $index }}.quantity" type="number" min="1" 
                                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error("items.{$index}.quantity") <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Price (₹) *</label>
                                            <input wire:model.live="items.{{ $index }}.unit_price" type="number" step="0.01" min="0" 
                                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error("items.{$index}.unit_price") <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Tax %</label>
                                            <input wire:model.live="items.{{ $index }}.tax_rate" type="number" min="0" max="100" 
                                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error("items.{$index}.tax_rate") <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="col-span-1">
                                            @if(count($items) > 1)
                                                <button type="button" wire:click="removeItem({{ $index }})" 
                                                        class="text-red-500 hover:text-red-700 p-2">×</button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Totals Summary -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Subtotal:</span>
                                    <span class="text-sm font-medium">₹{{ number_format($subtotal / 100, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Tax:</span>
                                    <span class="text-sm font-medium">₹{{ number_format($tax / 100, 2) }}</span>
                                </div>
                                <div class="border-t pt-2 mt-2 flex justify-between">
                                    <span class="text-lg font-bold">Total:</span>
                                    <span class="text-lg font-bold">₹{{ number_format($total / 100, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @elseif ($currentStep === 3)
                        <!-- Step 3: Review -->
                        <div class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900">Review {{ ucfirst($type) }}</h3>
                            
                            <div class="bg-gray-50 rounded-lg p-6">
                                <div class="grid grid-cols-2 gap-6">
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-2">From:</h4>
                                        @if($company_id && $this->companies->where('id', $company_id)->first())
                                            <p class="text-sm text-gray-600">{{ $this->companies->where('id', $company_id)->first()->name }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-2">To:</h4>
                                        @if($customer_id && $this->customers->where('id', $customer_id)->first())
                                            <p class="text-sm text-gray-600">{{ $this->customers->where('id', $customer_id)->first()->name }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <h4 class="font-medium text-gray-900 mb-2">Items:</h4>
                                    <div class="space-y-2">
                                        @foreach($items as $item)
                                            <div class="flex justify-between text-sm">
                                                <span>{{ $item['description'] }} ({{ $item['quantity'] }}x)</span>
                                                <span>₹{{ number_format(($item['quantity'] * $item['unit_price']), 2) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mt-6 pt-4 border-t">
                                    <div class="flex justify-between font-bold">
                                        <span>Total:</span>
                                        <span>₹{{ number_format($total / 100, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between pt-6 border-t mt-6">
                        <div>
                            @if ($currentStep > 1)
                                <button type="button" wire:click="previousStep" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                    Previous
                                </button>
                            @endif
                            <button type="button" wire:click="cancel" class="ml-2 px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                        </div>

                        <div>
                            @if ($currentStep < 3)
                                <button type="button" wire:click="nextStep" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                    Next
                                </button>
                            @else
                                <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">
                                    {{ $editingId ? 'Update' : 'Create' }} {{ ucfirst($type) }}
                                </button>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>