<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <!-- Header with Navigation -->
        <div class="mb-6 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="{{ route('invoices.index') }}" class="text-blue-600 hover:text-blue-900">
                    ← Back to Invoices
                </a>
                <h1 class="text-3xl font-bold text-gray-900">{{ $this->pageTitle }}</h1>
            </div>
            @if($mode === 'edit' && $invoice && $invoice->ulid)
                <div class="flex space-x-2">
                    <button wire:click="openEmailModal" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                        Send Email
                    </button>
                    <a href="{{ route($invoice->type === 'invoice' ? 'invoices.public' : 'estimates.public', $invoice->ulid) }}"
                       target="_blank" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        View Public
                    </a>
                    <button wire:click="downloadPdf" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                        Download PDF
                    </button>
                </div>
            @endif
        </div>

        @if (session()->has('message'))
            <div class="mb-4 p-4 text-green-700 bg-green-100 border border-green-300 rounded">
                {{ session('message') }}
            </div>
        @endif

        <!-- Single Page Form -->
        <form wire:submit="save" class="space-y-6">
            <!-- Customer & Address Section -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column: Customer & Billing Address -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('forms.labels.customer_required') }}
                            </label>
                            <select wire:model.live="customer_id"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">{{ __('forms.labels.select_customer') }}</option>
                                @foreach($this->customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                            @error('customer_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                            @if($this->customers->count() === 0)
                                <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                    <p class="text-sm text-blue-700">
                                        No customers found.
                                        <a href="{{ route('customers.index') }}" class="font-medium underline hover:text-blue-600" target="_blank">
                                            Create your first customer →
                                        </a>
                                    </p>
                                </div>
                            @endif
                        </div>

                        @if($customer_id)
                            <div class="border border-gray-200 rounded-md p-4 bg-gray-50">
                                <h3 class="text-sm font-semibold text-gray-700 mb-2">BILLING ADDRESS</h3>
                                @if($this->customerLocations->count() > 0)
                                    <div class="mb-3">
                                        <select wire:model.live="customer_location_id"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">Select Location</option>
                                            @foreach($this->customerLocations as $location)
                                                <option value="{{ $location->id }}">{{ $location->name }} - {{ $location->city }}</option>
                                            @endforeach
                                        </select>
                                        @error('customer_location_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    @if($customer_location_id)
                                        @php
                                            $selectedCustomerLocation = $this->customerLocations->firstWhere('id', $customer_location_id);
                                        @endphp
                                        @if($selectedCustomerLocation)
                                            <div class="text-sm text-gray-600 space-y-1">
                                                @if($selectedCustomerLocation->address_line_1)
                                                    <p>{{ $selectedCustomerLocation->address_line_1 }}</p>
                                                @endif
                                                @if($selectedCustomerLocation->address_line_2)
                                                    <p>{{ $selectedCustomerLocation->address_line_2 }}</p>
                                                @endif
                                                <p>
                                                    @if($selectedCustomerLocation->city)
                                                        {{ $selectedCustomerLocation->city }}
                                                    @endif
                                                    @if($selectedCustomerLocation->state)
                                                        , {{ $selectedCustomerLocation->state }}
                                                    @endif
                                                    @if($selectedCustomerLocation->zip_code)
                                                        - {{ $selectedCustomerLocation->zip_code }}
                                                    @endif
                                                </p>
                                                @if($selectedCustomerLocation->country)
                                                    <p>{{ $selectedCustomerLocation->country }}</p>
                                                @endif
                                            </div>
                                        @endif
                                    @endif
                                @else
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
                                        <p class="text-sm text-yellow-700">
                                            No location found.
                                            <a href="/customers" class="font-medium underline hover:text-yellow-600" target="_blank">
                                                Add location →
                                            </a>
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Right Column: Organization & Shipping Address -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Organization</label>
                            <div class="w-full border border-gray-300 bg-gray-50 rounded-md px-3 py-2">
                                @if(auth()->check() && auth()->user()->currentTeam)
                                    <span class="text-gray-900 font-medium">{{ auth()->user()->currentTeam->name }}</span>
                                    @if(auth()->user()->currentTeam->company_name && auth()->user()->currentTeam->company_name !== auth()->user()->currentTeam->name)
                                        <span class="text-gray-500 text-sm"> ({{ auth()->user()->currentTeam->company_name }})</span>
                                    @endif
                                @else
                                    <span class="text-gray-500">No organization selected</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Use the team switcher in the navigation to change organizations</p>
                        </div>

                        @if($organization_id)
                            <div class="border border-gray-200 rounded-md p-4 bg-gray-50">
                                <h3 class="text-sm font-semibold text-gray-700 mb-2">SHIPPING ADDRESS</h3>
                                @if($this->organizationLocations->count() > 0)
                                    <div class="mb-3">
                                        <select wire:model.live="organization_location_id"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">Select Location</option>
                                            @foreach($this->organizationLocations as $location)
                                                <option value="{{ $location->id }}">{{ $location->name }} - {{ $location->city }}</option>
                                            @endforeach
                                        </select>
                                        @error('organization_location_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    @if($organization_location_id)
                                        @php
                                            $selectedOrgLocation = $this->organizationLocations->firstWhere('id', $organization_location_id);
                                        @endphp
                                        @if($selectedOrgLocation)
                                            <div class="text-sm text-gray-600 space-y-1">
                                                @if($selectedOrgLocation->address_line_1)
                                                    <p>{{ $selectedOrgLocation->address_line_1 }}</p>
                                                @endif
                                                @if($selectedOrgLocation->address_line_2)
                                                    <p>{{ $selectedOrgLocation->address_line_2 }}</p>
                                                @endif
                                                <p>
                                                    @if($selectedOrgLocation->city)
                                                        {{ $selectedOrgLocation->city }}
                                                    @endif
                                                    @if($selectedOrgLocation->state)
                                                        , {{ $selectedOrgLocation->state }}
                                                    @endif
                                                    @if($selectedOrgLocation->zip_code)
                                                        - {{ $selectedOrgLocation->zip_code }}
                                                    @endif
                                                </p>
                                                @if($selectedOrgLocation->country)
                                                    <p>{{ $selectedOrgLocation->country }}</p>
                                                @endif
                                            </div>
                                        @endif
                                    @endif
                                @else
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
                                        <p class="text-sm text-yellow-700">
                                            No location found.
                                            <a href="/organizations" class="font-medium underline hover:text-yellow-600" target="_blank">
                                                Add location →
                                            </a>
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Invoice Details Section -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Invoice Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                        <select wire:model="type"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                {{ $mode === 'edit' ? 'disabled' : '' }}>
                            <option value="invoice">Invoice</option>
                            <option value="estimate">Estimate</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                        <select wire:model="status"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach(\App\Enums\InvoiceStatus::cases() as $statusOption)
                                <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                            @endforeach
                        </select>
                        @error('status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    @if($type === 'invoice' && $organization_id && $this->availableNumberingSeries->count() > 0)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Numbering Series (Optional)</label>
                            <select wire:model.live="invoice_numbering_series_id"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Auto-select best series</option>
                                @foreach($this->availableNumberingSeries as $series)
                                    <option value="{{ $series->id }}">
                                        {{ $series->name }}
                                        @if($series->location)
                                            ({{ $series->location->name }})
                                        @else
                                            (Organization-wide)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('invoice_numbering_series_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            @if($this->selectedSeriesPreview)
                                <p class="mt-1 text-sm text-gray-600">
                                    Next: <span class="font-mono text-indigo-600">{{ $this->selectedSeriesPreview }}</span>
                                </p>
                            @endif
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('documents.fields.issue_date') }}</label>
                        <input wire:model="issued_at" type="date"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('issued_at') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('documents.fields.due_date') }}</label>
                        <input wire:model="due_at" type="date"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('due_at') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Items Section -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Line Items</h2>
                    <button type="button" wire:click="addItem"
                            class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium py-2 px-4 rounded">
                        + Add Item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Description *
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                                    Quantity *
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                    Rate ({{ $this->currencySymbol }}) *
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                                    Tax %
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                    Amount
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($items as $index => $item)
                                <tr>
                                    <td class="px-4 py-3">
                                        <input wire:model.live="items.{{ $index }}.description" type="text"
                                               placeholder="Item description"
                                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error("items.{$index}.description")
                                            <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror

                                        <!-- SAC Code Field -->
                                        <div class="mt-2">
                                            <input wire:model.live="items.{{ $index }}.sac_code" type="text"
                                                   placeholder="SAC Code (e.g., 998314)"
                                                   class="w-40 border border-gray-300 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            @if(!empty($item['sac_code']))
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    services SAC: {{ $item['sac_code'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input wire:model.live="items.{{ $index }}.quantity" type="number" min="1"
                                               placeholder="1"
                                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error("items.{$index}.quantity")
                                            <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </td>
                                    <td class="px-4 py-3">
                                        <input wire:model.live="items.{{ $index }}.unit_price" type="number" step="0.01" min="0"
                                               placeholder="0.00"
                                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error("items.{$index}.unit_price")
                                            <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </td>
                                    <td class="px-4 py-3">
                                        <input wire:model.live="items.{{ $index }}.tax_rate" type="number" step="0.01" min="0" max="100"
                                               placeholder="0"
                                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error("items.{$index}.tax_rate")
                                            <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @php
                                            $lineTotal = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0) * 100;
                                            $lineTax = $lineTotal * (($item['tax_rate'] ?? 0) / 100);
                                            $lineAmount = $lineTotal + $lineTax;
                                        @endphp
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ $this->formatAmount((int)$lineAmount) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if(count($items) > 1)
                                            <button type="button" wire:click="removeItem({{ $index }})"
                                                    class="text-red-500 hover:text-red-700 font-bold text-lg">
                                                ×
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Totals Section -->
                <div class="mt-6 flex justify-end">
                    <div class="w-full md:w-1/3 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-medium text-gray-900">{{ $this->formatAmount($subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Tax:</span>
                            <span class="font-medium text-gray-900">{{ $this->formatAmount($tax) }}</span>
                        </div>
                        <div class="border-t border-gray-300 pt-2 flex justify-between">
                            <span class="text-lg font-bold text-gray-900">Total:</span>
                            <span class="text-lg font-bold text-blue-600">{{ $this->formatAmount($total) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Notes Section -->
            <div class="bg-white shadow rounded-lg p-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Customer Notes</label>
                    <textarea wire:model="notes" rows="4"
                              placeholder="Enter any notes or terms and conditions for this invoice..."
                              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    <p class="text-xs text-gray-500 mt-1">This will be displayed on the invoice PDF</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between items-center pt-4">
                <button type="button" wire:click="cancel"
                        class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 font-medium">
                    Cancel
                </button>
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium">
                    {{ $mode === 'edit' ? 'Update' : 'Create' }} {{ ucfirst($type) }}
                </button>
            </div>
        </form>

        <!-- Email Modal -->
        @if($showEmailModal)
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50" x-data="{ showRecipients: false }">
                <div class="bg-white rounded-lg shadow-2xl max-w-5xl w-full max-h-[95vh] overflow-hidden flex flex-col">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-white">
                        <h2 class="text-xl font-semibold text-gray-900">Email To {{ $invoice?->customer?->name ?? 'Customer' }}</h2>
                        <button wire:click="closeEmailModal" class="text-gray-400 hover:text-gray-600 transition">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body - Scrollable -->
                    <div class="flex-1 overflow-y-auto px-6 py-4">
                        <!-- From Field -->
                        <div class="mb-4">
                            <div class="flex items-center">
                                <label class="text-sm font-medium text-gray-600 w-20">From</label>
                                <div class="flex-1 flex items-center">
                                    <span class="text-sm text-gray-500">Accounts &lt;{{ config('mail.from.address') }}&gt;</span>
                                </div>
                            </div>
                        </div>

                        <!-- Send To Field with Chips -->
                        <div class="mb-4">
                            <div class="flex items-start">
                                <label class="text-sm font-medium text-gray-600 w-20 pt-2">Send To</label>
                                <div class="flex-1" x-data="{ toEmail: '' }">
                                    <div class="border border-gray-300 rounded-md p-2 min-h-[44px] flex flex-wrap gap-2 items-center focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500">
                                        @php
                                            $customer = $customer_id ? \App\Models\Customer::find($customer_id) : null;
                                            $customerEmails = $customer?->emails ?? collect();
                                        @endphp

                                        <!-- Selected Recipients as Chips -->
                                        @foreach($selectedRecipients as $email)
                                            @php
                                                $contactInfo = $customerEmails->toArray();
                                                $contact = collect($contactInfo)->firstWhere('email', $email);
                                                $displayName = $contact['name'] ?? '';
                                                $initial = $displayName ? strtoupper(substr($displayName, 0, 1)) : strtoupper(substr($email, 0, 1));
                                                $colors = ['bg-purple-100 text-purple-700', 'bg-blue-100 text-blue-700', 'bg-green-100 text-green-700', 'bg-yellow-100 text-yellow-700'];
                                                $colorClass = $colors[array_rand($colors)];
                                            @endphp
                                            <div class="inline-flex items-center gap-1 bg-gray-100 rounded-md px-2 py-1">
                                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-medium {{ $colorClass }}">
                                                    {{ $initial }}
                                                </span>
                                                <span class="text-sm text-gray-700">
                                                    @if($displayName)
                                                        {{ $displayName }} &lt;{{ $email }}&gt;
                                                    @else
                                                        {{ $email }}
                                                    @endif
                                                </span>
                                                <button type="button" wire:click="removeRecipient('{{ $email }}')" class="text-gray-500 hover:text-gray-700">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endforeach

                                        <!-- Additional Recipients as Chips -->
                                        @foreach($additionalRecipients as $index => $email)
                                            @if(!empty(trim($email)))
                                                <div class="inline-flex items-center gap-1 bg-gray-100 rounded-md px-2 py-1">
                                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-medium bg-gray-200 text-gray-700">
                                                        {{ strtoupper(substr($email, 0, 1)) }}
                                                    </span>
                                                    <span class="text-sm text-gray-700">{{ $email }}</span>
                                                    <button type="button" wire:click="removeAdditionalRecipient({{ $index }})" class="text-gray-500 hover:text-gray-700">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            @endif
                                        @endforeach

                                        <!-- Direct Input Field -->
                                        <input type="email"
                                               x-model="toEmail"
                                               @keydown.enter.prevent="if(toEmail.trim()) { $wire.addDirectEmail(toEmail).then(() => { toEmail = '' }) }"
                                               placeholder="Type email and press Enter"
                                               class="flex-1 min-w-[200px] border-none outline-none focus:ring-0 text-sm p-1">
                                    </div>
                                    @error('selectedRecipients') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <a href="#" class="ml-2 text-sm text-blue-600 hover:text-blue-700">Bcc</a>
                            </div>
                        </div>

                        <!-- Cc Field with Chips -->
                        <div class="mb-4">
                            <div class="flex items-start">
                                <label class="text-sm font-medium text-gray-600 w-20 pt-2">Cc</label>
                                <div class="flex-1" x-data="{ ccEmail: '' }">
                                    <div class="border border-gray-300 rounded-md p-2 min-h-[44px] flex flex-wrap gap-2 items-center focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500">
                                        @php
                                            $organization = auth()->user()?->currentTeam;
                                            $organizationEmails = $organization?->emails ?? collect();
                                        @endphp

                                        <!-- Selected Cc Recipients as Chips -->
                                        @foreach($selectedCcRecipients as $email)
                                            @php
                                                $contactInfo = $organizationEmails->toArray();
                                                $contact = collect($contactInfo)->firstWhere('email', $email);
                                                $displayName = $contact['name'] ?? '';
                                                $initial = $displayName ? strtoupper(substr($displayName, 0, 1)) : strtoupper(substr($email, 0, 1));
                                                $colors = ['bg-purple-100 text-purple-700', 'bg-blue-100 text-blue-700', 'bg-green-100 text-green-700', 'bg-orange-100 text-orange-700'];
                                                $colorClass = $colors[array_rand($colors)];
                                            @endphp
                                            <div class="inline-flex items-center gap-1 bg-gray-100 rounded-md px-2 py-1">
                                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-medium {{ $colorClass }}">
                                                    {{ $initial }}
                                                </span>
                                                <span class="text-sm text-gray-700">
                                                    @if($displayName)
                                                        {{ $displayName }} &lt;{{ $email }}&gt;
                                                    @else
                                                        {{ $email }}
                                                    @endif
                                                </span>
                                                <button type="button" wire:click="removeCcRecipient('{{ $email }}')" class="text-gray-500 hover:text-gray-700">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endforeach

                                        <!-- Additional Cc Recipients as Chips -->
                                        @foreach($additionalCcRecipients as $index => $email)
                                            @if(!empty(trim($email)))
                                                <div class="inline-flex items-center gap-1 bg-gray-100 rounded-md px-2 py-1">
                                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-medium bg-gray-200 text-gray-700">
                                                        {{ strtoupper(substr($email, 0, 1)) }}
                                                    </span>
                                                    <span class="text-sm text-gray-700">{{ $email }}</span>
                                                    <button type="button" wire:click="removeAdditionalCcRecipient({{ $index }})" class="text-gray-500 hover:text-gray-700">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            @endif
                                        @endforeach

                                        <!-- Direct Input Field for Cc -->
                                        <input type="email"
                                               x-model="ccEmail"
                                               @keydown.enter.prevent="if(ccEmail.trim()) { $wire.addDirectCcEmail(ccEmail).then(() => { ccEmail = '' }) }"
                                               placeholder="Type email and press Enter"
                                               class="flex-1 min-w-[200px] border-none outline-none focus:ring-0 text-sm p-1">
                                    </div>
                                    @error('selectedCcRecipients') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Subject Field -->
                        <div class="mb-4">
                            <div class="flex items-center">
                                <label class="text-sm font-medium text-gray-600 w-20">Subject</label>
                                <input wire:model="emailSubject" type="text"
                                       class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            @error('emailSubject') <span class="text-red-500 text-xs mt-1 ml-20">{{ $message }}</span> @enderror
                        </div>

                        <!-- Email Body with Trix Editor -->
                        <div class="mb-4">
                            <div wire:ignore
                                 x-data="{
                                    initTrix() {
                                        const editor = this.$refs.trixEditor;
                                        const input = this.$refs.hiddenInput;
                                        if (editor && input && input.value) {
                                            editor.editor.loadHTML(input.value);
                                        }
                                        editor.addEventListener('trix-change', (e) => {
                                            $wire.set('emailBody', e.target.value);
                                        });
                                    }
                                 }"
                                 x-init="setTimeout(() => initTrix(), 100)">
                                <input x-ref="hiddenInput" id="email-body-{{ $invoice?->id }}" type="hidden" value="{{ $emailBody }}">
                                <trix-editor x-ref="trixEditor" input="email-body-{{ $invoice?->id }}"
                                            class="trix-content border border-gray-300 rounded-md min-h-[300px]"></trix-editor>
                            </div>
                            @error('emailBody') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- Attachments Section -->
                        <div class="mb-4">
                            <div class="border-t pt-4">
                                <label class="flex items-center cursor-pointer mb-2">
                                    <input type="checkbox" wire:model="attachPdf"
                                           class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Attach invoice PDF</span>
                                </label>

                                @if($attachPdf)
                                    <div class="flex items-center gap-2 p-3 bg-gray-50 border border-gray-200 rounded-md">
                                        <svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900">{{ $invoice?->invoice_number ?? 'Invoice' }}</div>
                                            <div class="text-xs text-gray-500">PDF Document</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center bg-gray-50">
                        <button type="button" class="text-sm text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                            </svg>
                            Attachments
                        </button>
                        <div class="flex gap-3">
                            <button wire:click="closeEmailModal" type="button"
                                    class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-100 font-medium transition">
                                Cancel
                            </button>
                            <button wire:click="sendEmail" type="button"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium transition shadow-sm">
                                Send
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
