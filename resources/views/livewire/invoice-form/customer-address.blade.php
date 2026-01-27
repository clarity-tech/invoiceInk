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
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                        aria-describedby="error-customer_id">
                    <option value="">{{ __('forms.labels.select_customer') }}</option>
                    @foreach($this->customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
                @error('customer_id') <span id="error-customer_id" class="text-red-600 text-sm">{{ $message }}</span> @enderror

                @if($this->customers->count() === 0)
                    <div class="mt-2 p-3 bg-brand-50 border border-brand-200 rounded-md">
                        <p class="text-sm text-brand-700">
                            {{ __('forms.hints.no_customers') }}
                            <a href="{{ route('customers.index') }}" class="font-medium underline hover:text-brand-600" target="_blank">
                                {{ __('actions.buttons.create_first_customer') }}
                            </a>
                        </p>
                    </div>
                @endif
            </div>

            @if($customer_id)
                <div class="border border-gray-200 rounded-md p-4 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ __('documents.headers.billing_address') }}</h3>
                    @if($this->customerLocations->count() > 0)
                        <div class="mb-3">
                            <select wire:model.live="customer_location_id"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                                    aria-describedby="error-customer_location_id">
                                <option value="">{{ __('forms.labels.select_location') }}</option>
                                @foreach($this->customerLocations as $location)
                                    @php
                                        $customer = \App\Models\Customer::find($customer_id);
                                        $isPrimary = $customer && $customer->primary_location_id === $location->id;
                                    @endphp
                                    <option value="{{ $location->id }}">
                                        {{ $location->name }} - {{ $location->city }}{{ $isPrimary ? ' (Primary)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_location_id') <span id="error-customer_location_id" class="text-red-600 text-sm">{{ $message }}</span> @enderror
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
                                    @if($selectedCustomerLocation->gstin)
                                        <p class="mt-1 font-medium text-gray-700">
                                            <span class="text-gray-500">{{ __('documents.fields.gstin') }}</span> {{ $selectedCustomerLocation->gstin }}
                                        </p>
                                    @endif
                                </div>
                            @endif
                        @endif
                    @else
                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
                            <p class="text-sm text-yellow-700">
                                {{ __('forms.hints.no_location') }}
                                <a href="/customers" class="font-medium underline hover:text-yellow-600" target="_blank">
                                    {{ __('actions.buttons.add_location') }}
                                </a>
                            </p>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Right Column: Customer Shipping Address -->
        <div class="space-y-4">
            @if($customer_id)
                <div class="border border-gray-200 rounded-md p-4 bg-gray-50 mt-20">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ __('documents.headers.shipping_address') }}</h3>
                    @if($this->customerLocations->count() > 0)
                        <div class="mb-3">
                            <select wire:model.live="customer_shipping_location_id"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                                    aria-describedby="error-customer_shipping_location_id">
                                <option value="">{{ __('forms.labels.select_location') }}</option>
                                @foreach($this->customerLocations as $location)
                                    @php
                                        $customer = \App\Models\Customer::find($customer_id);
                                        $isPrimary = $customer && $customer->primary_location_id === $location->id;
                                    @endphp
                                    <option value="{{ $location->id }}">
                                        {{ $location->name }} - {{ $location->city }}{{ $isPrimary ? ' (Primary)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_shipping_location_id') <span id="error-customer_shipping_location_id" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        @if($customer_shipping_location_id)
                            @php
                                $selectedShippingLocation = $this->customerLocations->firstWhere('id', $customer_shipping_location_id);
                            @endphp
                            @if($selectedShippingLocation)
                                <div class="text-sm text-gray-600 space-y-1">
                                    @if($selectedShippingLocation->address_line_1)
                                        <p>{{ $selectedShippingLocation->address_line_1 }}</p>
                                    @endif
                                    @if($selectedShippingLocation->address_line_2)
                                        <p>{{ $selectedShippingLocation->address_line_2 }}</p>
                                    @endif
                                    <p>
                                        @if($selectedShippingLocation->city)
                                            {{ $selectedShippingLocation->city }}
                                        @endif
                                        @if($selectedShippingLocation->state)
                                            , {{ $selectedShippingLocation->state }}
                                        @endif
                                        @if($selectedShippingLocation->zip_code)
                                            - {{ $selectedShippingLocation->zip_code }}
                                        @endif
                                    </p>
                                    @if($selectedShippingLocation->country)
                                        <p>{{ $selectedShippingLocation->country }}</p>
                                    @endif
                                    @if($selectedShippingLocation->gstin)
                                        <p class="mt-1 font-medium text-gray-700">
                                            <span class="text-gray-500">{{ __('documents.fields.gstin') }}</span> {{ $selectedShippingLocation->gstin }}
                                        </p>
                                    @endif
                                </div>
                            @endif
                        @endif
                    @else
                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
                            <p class="text-sm text-yellow-700">
                                {{ __('forms.hints.no_location') }}
                                <a href="/customers" class="font-medium underline hover:text-yellow-600" target="_blank">
                                    {{ __('actions.buttons.add_location') }}
                                </a>
                            </p>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
