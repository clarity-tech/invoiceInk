<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    @if($autoEdit)
                        {{ __('documents.headers.manage_your_business') }}
                    @else
                        {{ __('actions.navigation.organizations') }}
                    @endif
                </h1>
                @if($autoEdit)
                    <div class="mt-2">
                        <a href="{{ route('organizations.index') }}" class="text-sm text-brand-600 hover:text-brand-800">
                            {{ __('actions.buttons.view_all_organizations') }}
                        </a>
                    </div>
                @endif
            </div>
            @if (!$showForm && !$autoEdit)
                <button wire:click="create" class="bg-brand-500 hover:bg-brand-700 text-white font-bold py-2 px-4 rounded">
                    {{ __('actions.buttons.add_organization') }}
                </button>
            @endif
        </div>

        @if (session()->has('message'))
            <div class="mb-4 p-4 text-green-700 bg-green-100 border border-green-300 rounded">
                {{ session('message') }}
            </div>
        @endif

        {{-- Display general errors --}}
        @if ($errors->any())
            <div class="mb-4 p-4 text-red-700 bg-red-100 border border-red-300 rounded">
                <div class="font-medium">
                    {{ __('Whoops! Something went wrong.') }}
                </div>
                
                <ul class="mt-3 list-disc list-inside text-sm text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($showForm)
            <div class="mb-6 bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">
                        {{ $editingId ? __('documents.headers.edit_organization') : __('documents.headers.add_new_organization') }}
                    </h2>
                </div>
                
                <form wire:submit="save" class="p-6 space-y-6">
                    <!-- Organization Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.organization_name_required') }}</label>
                            <input wire:model="name" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-name">
                            @error('name') <span id="error-name" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.phone') }}</label>
                            <input wire:model="phone" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-phone">
                            @error('phone') <span id="error-phone" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Logo Upload Section -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('forms.labels.organization_logo') }}</h3>
                        <div class="flex items-start space-x-6">
                            <!-- Logo Preview -->
                            <div class="flex-shrink-0">
                                @if($logo)
                                    <div class="relative">
                                        <img src="{{ $logo->temporaryUrl() }}" alt="Logo preview" class="w-32 h-32 object-contain border border-gray-300 rounded-lg bg-white p-2">
                                        <button type="button" wire:click="$set('logo', null)"
                                                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                @elseif($existingLogoUrl)
                                    <div class="relative">
                                        <img src="{{ $existingLogoUrl }}" alt="Current logo" class="w-32 h-32 object-contain border border-gray-300 rounded-lg bg-white p-2">
                                        <button type="button" wire:click="removeLogo"
                                                wire:confirm="{{ __('actions.confirmations.confirm_remove_logo') }}"
                                                aria-label="{{ __('actions.buttons.remove') }} {{ __('forms.labels.organization_logo') }}"
                                                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                @else
                                    <div class="w-32 h-32 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center bg-gray-50">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            <!-- Upload Input -->
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('forms.labels.upload_logo') }}</label>
                                <input type="file" wire:model="logo" accept="image/*"
                                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                                <p class="mt-2 text-xs text-gray-500">{{ __('forms.hints.logo_requirements') }}</p>
                                @error('logo') <span id="error-logo" class="text-red-600 text-sm">{{ $message }}</span> @enderror

                                <div wire:loading wire:target="logo" class="mt-2">
                                    <div class="flex items-center text-sm text-brand-600">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ __('messages.system.uploading') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Country and Business Configuration -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('forms.labels.business_config') }}</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.country_required') }}</label>
                                <select wire:model.live="country_code" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-country_code">
                                    <option value="">{{ __('forms.placeholders.select_country') }}</option>
                                    @foreach($this->availableCountries as $country)
                                        <option value="{{ $country['value'] }}">{{ $country['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('country_code') <span id="error-country_code" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                
                                @if($this->selectedCountryInfo)
                                    <div class="mt-2 p-3 bg-brand-50 border border-brand-200 rounded-md">
                                        <div class="text-sm text-brand-800">
                                            <div class="font-medium">{{ $this->selectedCountryInfo['tax_system']['name'] ?? 'Tax System' }}</div>
                                            @if(isset($this->selectedCountryInfo['tax_system']['rates']))
                                                <div class="text-xs mt-1">Common rates: {{ implode(', ', array_map(fn($rate) => $rate.'%', $this->selectedCountryInfo['tax_system']['rates'])) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.currency_required') }}</label>
                                <select wire:model="currency" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" @if($country_code) disabled @endif aria-describedby="error-currency">
                                    <option value="">{{ __('forms.placeholders.select_currency') }}</option>
                                    @foreach($this->availableCurrencies as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('currency') <span id="error-currency" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                
                                @if($country_code && $this->selectedCountryInfo)
                                    <div class="mt-1 text-xs text-gray-500">
                                        {{ __('forms.hints.recommended_currency', ['currency' => $this->selectedCountryInfo['default_currency']]) }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($country_code && $this->selectedCountryInfo)
                            <!-- Financial Year Configuration -->
                            <div class="mt-6 border-t pt-6">
                                <h4 class="text-md font-medium text-gray-900 mb-3">{{ __('forms.labels.financial_year_config') }}</h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.financial_year_type') }}</label>
                                        <select wire:model="financial_year_type" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-financial_year_type">
                                            <option value="">{{ __('forms.placeholders.select_financial_year') }}</option>
                                            @foreach($this->selectedCountryInfo['financial_year_options'] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('financial_year_type') <span id="error-financial_year_type" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.start_month') }}</label>
                                        <select wire:model="financial_year_start_month" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-financial_year_start_month">
                                            @for($month = 1; $month <= 12; $month++)
                                                <option value="{{ $month }}">{{ date('F', mktime(0, 0, 0, $month, 1)) }}</option>
                                            @endfor
                                        </select>
                                        @error('financial_year_start_month') <span id="error-financial_year_start_month" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.start_day') }}</label>
                                        <input wire:model="financial_year_start_day" type="number" min="1" max="31" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-financial_year_start_day">
                                        @error('financial_year_start_day') <span id="error-financial_year_start_day" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                @if($this->selectedCountryInfo['recommended_numbering'])
                                    <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-md">
                                        <div class="text-sm text-green-800">
                                            <div class="font-medium">{{ __('forms.hints.invoice_numbering_recommendation') }}</div>
                                            <div class="text-xs mt-1">{{ $this->selectedCountryInfo['recommended_numbering'] }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Email Management -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('forms.labels.email_addresses_required') }}</label>
                        <div class="space-y-2">
                            @foreach($emails as $index => $email)
                                <div class="flex items-center space-x-2">
                                    <input wire:model="emails.{{ $index }}" type="email" placeholder="{{ __('forms.placeholders.email_placeholder') }}"
                                           class="flex-1 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                                           aria-describedby="error-emails-{{ $index }}">
                                    @if(count($emails) > 1)
                                        <button type="button" wire:click="removeEmailField({{ $index }})"
                                                aria-label="{{ __('actions.buttons.remove') }} email {{ $index + 1 }}"
                                                class="text-red-500 hover:text-red-700 font-bold text-lg">×</button>
                                    @endif
                                </div>
                                @error("emails.{$index}") <span id="error-emails-{{ $index }}" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            @endforeach
                        </div>
                        <button type="button" wire:click="addEmailField" class="mt-2 text-brand-500 hover:text-brand-700 text-sm">
                            {{ __('actions.buttons.add_email') }}
                        </button>
                        @error('emails') <span id="error-emails" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Location Information -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('forms.labels.primary_location') }}</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.location_name') }}</label>
                                <input wire:model="location_name" type="text" placeholder="{{ __('forms.placeholders.location_name_optional') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-location_name">
                                @error('location_name') <span id="error-location_name" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.gstin') }}</label>
                                <input wire:model="gstin" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-gstin">
                                @error('gstin') <span id="error-gstin" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.address_line_1_required') }}</label>
                                <input wire:model="address_line_1" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-address_line_1">
                                @error('address_line_1') <span id="error-address_line_1" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.address_line_2') }}</label>
                                <input wire:model="address_line_2" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-address_line_2">
                                @error('address_line_2') <span id="error-address_line_2" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.city_required') }}</label>
                                <input wire:model="city" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-city">
                                @error('city') <span id="error-city" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.state_required') }}</label>
                                <input wire:model="state" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-state">
                                @error('state') <span id="error-state" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>

                            @if($country_code)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.location_country') }}</label>
                                    <div class="w-full border border-gray-200 rounded-md px-3 py-2 bg-gray-50 text-gray-700">
                                        @foreach($this->availableCountries as $countryData)
                                            @if($countryData['value'] === $country_code)
                                                {{ $countryData['label'] }}
                                                <span class="text-xs text-gray-500 ml-2">{{ __('forms.hints.inherited_from_org') }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                    <div class="mt-1 text-xs text-gray-600">
                                        {{ __('forms.hints.location_uses_org_country') }}
                                    </div>
                                </div>
                            @endif

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.postal_code_required') }}</label>
                                <input wire:model="postal_code" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-postal_code">
                                @error('postal_code') <span id="error-postal_code" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Bank Details -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('forms.labels.bank_details') }}</h3>
                        <p class="text-sm text-gray-500 mb-4">{{ __('forms.hints.bank_details_optional') ?? 'Optional. Bank details will appear on your invoices.' }}</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.bank_account_name') }}</label>
                                <input wire:model="bank_account_name" type="text" placeholder="{{ $name }}" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.bank_account_number') }}</label>
                                <input wire:model="bank_account_number" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.bank_name') }}</label>
                                <input wire:model="bank_name" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.bank_ifsc') }}</label>
                                <input wire:model="bank_ifsc" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.bank_branch') }}</label>
                                <input wire:model="bank_branch" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.bank_swift') }}</label>
                                <input wire:model="bank_swift" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.bank_pan') }}</label>
                                <input wire:model="bank_pan" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t">
                        <button type="button" wire:click="cancel" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            {{ __('actions.buttons.cancel') }}
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-brand-500 text-white rounded-md hover:bg-brand-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                wire:loading.attr="disabled"
                                wire:target="save">
                            <span wire:loading.remove wire:target="save">
                                {{ $editingId ? __('actions.buttons.update') : __('actions.buttons.create') }} {{ __('forms.labels.organization') }}
                            </span>
                            <span wire:loading wire:target="save">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ $editingId ? __('messages.system.updating') : __('messages.system.creating') }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Organizations List -->
        @if (!$showForm && !$autoEdit)
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('forms.labels.organization') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('forms.labels.contact') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('forms.labels.location') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('forms.labels.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($this->organizations as $organization)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $organization->displayName }}</div>
                                    @if($organization->currency)
                                        <div class="text-sm text-gray-500">{{ $organization->currency->symbol() }} {{ $organization->currency->value }}</div>
                                    @endif
                                    @if($organization->primaryLocation && $organization->primaryLocation->gstin)
                                        <div class="text-sm text-gray-500">{{ __('documents.fields.gstin') }} {{ $organization->primaryLocation->gstin }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        @if($organization->emails && !$organization->emails->isEmpty())
                                            {{ $organization->emails->getFirstEmail() }}
                                            @if($organization->emails->count() > 1)
                                                <span class="text-gray-500">(+{{ $organization->emails->count() - 1 }} more)</span>
                                            @endif
                                        @endif
                                    </div>
                                    @if($organization->phone)
                                        <div class="text-sm text-gray-500">{{ $organization->phone }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($organization->primaryLocation)
                                        <div class="text-sm text-gray-900">{{ $organization->primaryLocation->name }}</div>
                                        <div class="text-sm text-gray-500">
                                            {{ $organization->primaryLocation->city }}, {{ $organization->primaryLocation->state }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button wire:click="edit({{ $organization->id }})" class="text-brand-600 hover:text-brand-900 mr-3">{{ __('actions.buttons.edit') }}</button>
                                    <button wire:click="delete({{ $organization->id }})" 
                                            wire:confirm="{{ __('actions.confirmations.confirm_delete_organization') }}"
                                            class="text-red-600 hover:text-red-900">{{ __('actions.buttons.delete') }}</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    {{ __('messages.empty_states.no_organizations') }} <button wire:click="create" class="text-brand-500 hover:text-brand-700">{{ __('actions.buttons.create_first_organization') }}</button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                
                <div class="px-6 py-3 border-t border-gray-200">
                    {{ $this->organizations->links() }}
                </div>
            </div>
        @endif
    </div>

    @script
    <script>
        // Debug logging for Livewire events
        document.addEventListener('livewire:init', () => {
            Livewire.on('validation-errors', (errors) => {
                // Handle validation errors if needed
            });
        });

        // Form submission debugging
        document.addEventListener('livewire:navigating', () => {
            // Handle navigation start if needed
        });

        document.addEventListener('livewire:navigated', () => {
            // Handle navigation complete if needed
        });

        // Error handling for failed requests
        window.addEventListener('livewire:request-error', (event) => {
            // Show user-friendly error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50';
            errorDiv.innerHTML = `
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <span>{{ __('messages.system.network_error') }}</span>
                    <button onclick="this.parentNode.parentNode.remove()" class="ml-4 text-red-700 hover:text-red-900">×</button>
                </div>
            `;
            document.body.appendChild(errorDiv);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (errorDiv.parentNode) {
                    errorDiv.parentNode.removeChild(errorDiv);
                }
            }, 5000);
        });
    </script>
    @endscript
</div>
