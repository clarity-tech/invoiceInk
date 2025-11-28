<div>
    <div class="min-h-screen bg-gray-50 py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Organization Setup</h1>
                <p class="mt-2 text-lg text-gray-600">Let's get your organization ready for invoicing</p>
            </div>

            <!-- Progress Bar -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-2">
                    @foreach($this->stepProgress as $stepNumber => $step)
                        <div class="flex items-center {{ $stepNumber < count($this->stepProgress) ? 'flex-1' : '' }}">
                            <div class="relative">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 
                                    {{ $stepNumber <= $currentStep ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 bg-white text-gray-500' }}
                                    {{ $step['completed'] ? 'bg-green-600 border-green-600' : '' }}">
                                    @if($step['completed'])
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        {{ $stepNumber }}
                                    @endif
                                </div>
                                <div class="absolute top-12 left-1/2 transform -translate-x-1/2 whitespace-nowrap">
                                    <p class="text-sm font-medium {{ $stepNumber <= $currentStep ? 'text-blue-600' : 'text-gray-500' }}">{{ $step['title'] }}</p>
                                    <p class="text-xs text-gray-400">{{ $step['description'] }}</p>
                                </div>
                            </div>
                            @if($stepNumber < count($this->stepProgress))
                                <div class="flex-1 h-0.5 mx-4 {{ $stepNumber < $currentStep ? 'bg-blue-600' : 'bg-gray-300' }}"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Main Content -->
            <div class="bg-white shadow-lg rounded-lg">
                <div class="px-6 py-8">
                    @if (session()->has('message'))
                        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md">
                            {{ session('message') }}
                        </div>
                    @endif

                    <!-- Step 1: Company Information -->
                    @if($currentStep === 1)
                        <div class="space-y-6">
                            <div>
                                <h2 class="text-2xl font-semibold text-gray-900 mb-4">Company Information</h2>
                                <p class="text-gray-600 mb-6">Tell us about your business</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Company Name *
                                    </label>
                                    <input type="text" id="company_name" wire:model="company_name" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Enter your company name">
                                    @error('company_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="tax_number" class="block text-sm font-medium text-gray-700 mb-2">
                                        Tax Number
                                    </label>
                                    <input type="text" id="tax_number" wire:model="tax_number" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Tax identification number">
                                    @error('tax_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="registration_number" class="block text-sm font-medium text-gray-700 mb-2">
                                        Registration Number
                                    </label>
                                    <input type="text" id="registration_number" wire:model="registration_number" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Business registration number">
                                    @error('registration_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="website" class="block text-sm font-medium text-gray-700 mb-2">
                                        Website
                                    </label>
                                    <input type="url" id="website" wire:model="website" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="https://yourcompany.com">
                                    @error('website') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                        Notes
                                    </label>
                                    <textarea id="notes" wire:model="notes" rows="3" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                              placeholder="Additional notes about your company"></textarea>
                                    @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                    <!-- Step 2: Primary Location -->
                    @elseif($currentStep === 2)
                        <div class="space-y-6">
                            <div>
                                <h2 class="text-2xl font-semibold text-gray-900 mb-4">Primary Location</h2>
                                <p class="text-gray-600 mb-6">Your main business address for invoices</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="location_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Location Name *
                                    </label>
                                    <input type="text" id="location_name" wire:model="location_name" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Head Office, Main Branch, etc.">
                                    @error('location_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="gstin" class="block text-sm font-medium text-gray-700 mb-2">
                                        GSTIN / Tax ID
                                    </label>
                                    <input type="text" id="gstin" wire:model="gstin" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Location-specific tax ID">
                                    @error('gstin') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="address_line_1" class="block text-sm font-medium text-gray-700 mb-2">
                                        Address Line 1 *
                                    </label>
                                    <input type="text" id="address_line_1" wire:model="address_line_1" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Street address, building name">
                                    @error('address_line_1') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="address_line_2" class="block text-sm font-medium text-gray-700 mb-2">
                                        Address Line 2
                                    </label>
                                    <input type="text" id="address_line_2" wire:model="address_line_2" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Apartment, floor, suite">
                                    @error('address_line_2') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                                        City *
                                    </label>
                                    <input type="text" id="city" wire:model="city" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="City name">
                                    @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="state" class="block text-sm font-medium text-gray-700 mb-2">
                                        State / Province *
                                    </label>
                                    <input type="text" id="state" wire:model="state" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="State or province">
                                    @error('state') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">
                                        Postal Code *
                                    </label>
                                    <input type="text" id="postal_code" wire:model="postal_code" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="ZIP or postal code">
                                    @error('postal_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                    <!-- Step 3: Currency & Financial Year Configuration -->
                    @elseif($currentStep === 3)
                        <div class="space-y-6">
                            <div>
                                <h2 class="text-2xl font-semibold text-gray-900 mb-4">Configuration</h2>
                                <p class="text-gray-600 mb-6">Set your currency and financial year preferences</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="country_code" class="block text-sm font-medium text-gray-700 mb-2">
                                        Country *
                                    </label>
                                    <select id="country_code" wire:model.live="country_code" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select your country</option>
                                        @foreach($this->availableCountries as $country)
                                            <option value="{{ $country['value'] }}">{{ $country['label'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('country_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">
                                        Currency *
                                    </label>
                                    <select id="currency" wire:model="currency" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select currency</option>
                                        @foreach($this->availableCurrencies as $code => $name)
                                            <option value="{{ $code }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    @error('currency') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                @if($this->selectedCountryInfo)
                                    <div>
                                        <label for="financial_year_type" class="block text-sm font-medium text-gray-700 mb-2">
                                            Financial Year
                                        </label>
                                        <select id="financial_year_type" wire:model="financial_year_type" 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                            @foreach($this->selectedCountryInfo['financial_year_options'] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('financial_year_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                @endif

                                @if($this->selectedCountryInfo)
                                    <div class="md:col-span-2 bg-blue-50 p-4 rounded-md">
                                        <h4 class="text-sm font-medium text-blue-900 mb-2">Country Information</h4>
                                        <div class="text-sm text-blue-800 space-y-1">
                                            <p><strong>Tax System:</strong> {{ $this->selectedCountryInfo['tax_system']['name'] }}</p>
                                            <p><strong>Common Rates:</strong> {{ implode(', ', $this->selectedCountryInfo['tax_system']['rates']) }}%</p>
                                            <p><strong>Recommended Format:</strong> {{ $this->selectedCountryInfo['recommended_numbering'] }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                    <!-- Step 4: Contact Information -->
                    @elseif($currentStep === 4)
                        <div class="space-y-6">
                            <div>
                                <h2 class="text-2xl font-semibold text-gray-900 mb-4">Contact Details</h2>
                                <p class="text-gray-600 mb-6">How can customers reach your organization?</p>
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Email Addresses *
                                    </label>
                                    @foreach($emails as $index => $email)
                                        <div class="flex items-center mb-2">
                                            <input type="email" wire:model="emails.{{ $index }}" 
                                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="email@company.com">
                                            @if($index > 0)
                                                <button type="button" wire:click="removeEmailField({{ $index }})" 
                                                        class="ml-2 text-red-600 hover:text-red-800">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                        @error("emails.{$index}") <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                                    @endforeach
                                    
                                    <button type="button" wire:click="addEmailField" 
                                            class="mt-2 text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        + Add another email
                                    </button>
                                    @error('emails') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        Phone Number
                                    </label>
                                    <input type="tel" id="phone" wire:model="phone" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="+1 (555) 123-4567">
                                    @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Navigation Buttons -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
                    @if($currentStep > 1)
                        <button type="button" wire:click="previousStep" 
                                class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Previous
                        </button>
                    @else
                        <div></div>
                    @endif

                    @if($currentStep < $totalSteps)
                        <button type="button" wire:click="nextStep" 
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Next
                        </button>
                    @else
                        <button type="button"
                                x-data="{ loading: false }"
                                @click="loading = true; $wire.completeSetup()"
                                :disabled="loading"
                                class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!loading">Complete Setup</span>
                            <span x-show="loading">Processing...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>