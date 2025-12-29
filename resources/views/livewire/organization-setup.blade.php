<div>
    <div class="min-h-screen bg-gray-50 py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">{{ __('documents.headers.organization_setup') }}</h1>
                <p class="mt-2 text-lg text-gray-600">{{ __('forms.hints.setup_subtitle') }}</p>
            </div>

            <!-- Progress Bar -->
            <div class="mb-8">
                <!-- Mobile: Compact step indicator -->
                <div class="sm:hidden mb-4">
                    <p class="text-sm font-medium text-brand-600 text-center">
                        {{ __('forms.steps.step_progress', ['current' => $currentStep, 'total' => count($this->stepProgress)]) }}:
                        {{ $this->stepProgress[$currentStep]['title'] ?? '' }}
                    </p>
                    <div class="mt-2 flex gap-1">
                        @foreach($this->stepProgress as $stepNumber => $step)
                            <div class="flex-1 h-2 rounded-full {{ $stepNumber <= $currentStep ? 'bg-brand-600' : 'bg-gray-300' }} {{ $step['completed'] ? 'bg-green-600' : '' }}"></div>
                        @endforeach
                    </div>
                </div>

                <!-- Desktop: Full step indicator with labels -->
                <div class="hidden sm:flex items-center justify-between mb-2">
                    @foreach($this->stepProgress as $stepNumber => $step)
                        <div class="flex items-center {{ $stepNumber < count($this->stepProgress) ? 'flex-1' : '' }}">
                            <div class="relative">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full border-2
                                    {{ $stepNumber <= $currentStep ? 'border-brand-600 bg-brand-600 text-white' : 'border-gray-300 bg-white text-gray-500' }}
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
                                    <p class="text-sm font-medium {{ $stepNumber <= $currentStep ? 'text-brand-600' : 'text-gray-500' }}">{{ $step['title'] }}</p>
                                    <p class="text-xs text-gray-400">{{ $step['description'] }}</p>
                                </div>
                            </div>
                            @if($stepNumber < count($this->stepProgress))
                                <div class="flex-1 h-0.5 mx-4 {{ $stepNumber < $currentStep ? 'bg-brand-600' : 'bg-gray-300' }}"></div>
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
                                <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ __('documents.headers.company_information') }}</h2>
                                <p class="text-gray-600 mb-6">{{ __('forms.hints.company_info_subtitle') }}</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('forms.labels.company_name_required') }}
                                    </label>
                                    <input type="text" id="company_name" wire:model="company_name"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500"
                                           placeholder="{{ __('forms.placeholders.company_name') }}">
                                    @error('company_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="tax_number" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('forms.labels.tax_number') }}
                                    </label>
                                    <input type="text" id="tax_number" wire:model="tax_number"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500"
                                           placeholder="{{ __('forms.placeholders.tax_number') }}">
                                    @error('tax_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="registration_number" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('forms.labels.registration_number') }}
                                    </label>
                                    <input type="text" id="registration_number" wire:model="registration_number"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500"
                                           placeholder="{{ __('forms.placeholders.registration_number') }}">
                                    @error('registration_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="website" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('forms.labels.website') }}
                                    </label>
                                    <input type="url" id="website" wire:model="website"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500"
                                           placeholder="{{ __('forms.placeholders.website_url') }}">
                                    @error('website') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('forms.labels.notes') }}
                                    </label>
                                    <textarea id="notes" wire:model="notes" rows="3"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500"
                                              placeholder="{{ __('forms.placeholders.additional_notes') }}"></textarea>
                                    @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                    <!-- Step 2: Primary Location -->
                    @elseif($currentStep === 2)
                        <div class="space-y-6">
                            <div>
                                <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ __('forms.labels.primary_location') }}</h2>
                                <p class="text-gray-600 mb-6">{{ __('forms.hints.primary_location_subtitle') }}</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="location_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('forms.labels.location_name_required') }}
                                    </label>
                                    <input type="text" id="location_name" wire:model="location_name"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500"
                                           placeholder="{{ __('forms.placeholders.location_name_examples') }}">
                                    @error('location_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="gstin" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('forms.labels.gstin_tax_id') }}
                                    </label>
                                    <input type="text" id="gstin" wire:model="gstin"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500"
                                           placeholder="{{ __('forms.placeholders.location_tax_id') }}">
                                    @error('gstin') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="address_line_1" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('forms.labels.address_line_1_required') }}
                                    </label>
                                    <input type="text" id="address_line_1" wire:model="address_line_1"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500"
                                           placeholder="{{ __('forms.placeholders.street_address') }}">
                                    @error('address_line_1') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="address_line_2" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('forms.labels.address_line_2') }}
                                    </label>
                                    <input type="text" id="address_line_2" wire:model="address_line_2"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500"
                                           placeholder="{{ __('forms.placeholders.apartment_floor') }}">
                                    @error('address_line_2') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('forms.labels.city_required') }}
                                    </label>
                                    <input type="text" id="city" wire:model="city"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500"
                                           placeholder="{{ __('forms.placeholders.city_name') }}">
                                    @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="state" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('forms.labels.state_province_required') }}
                                    </label>
                                    <input type="text" id="state" wire:model="state"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500"
                                           placeholder="{{ __('forms.placeholders.state_province') }}">
                                    @error('state') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('forms.labels.postal_code_required') }}
                                    </label>
                                    <input type="text" id="postal_code" wire:model="postal_code"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500"
                                           placeholder="{{ __('forms.placeholders.zip_postal_code') }}">
                                    @error('postal_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                    <!-- Step 3: Currency & Financial Year Configuration -->
                    @elseif($currentStep === 3)
                        <div class="space-y-6">
                            <div>
                                <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ __('documents.headers.configuration') }}</h2>
                                <p class="text-gray-600 mb-6">{{ __('forms.hints.configuration_subtitle') }}</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="country_code" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('forms.labels.country_required') }}
                                    </label>
                                    <select id="country_code" wire:model.live="country_code"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500">
                                        <option value="">{{ __('forms.placeholders.select_your_country') }}</option>
                                        @foreach($this->availableCountries as $country)
                                            <option value="{{ $country['value'] }}">{{ $country['label'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('country_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('forms.labels.currency_required') }}
                                    </label>
                                    <select id="currency" wire:model="currency"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500">
                                        <option value="">{{ __('forms.placeholders.select_currency') }}</option>
                                        @foreach($this->availableCurrencies as $code => $name)
                                            <option value="{{ $code }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    @error('currency') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                @if($this->selectedCountryInfo)
                                    <div>
                                        <label for="financial_year_type" class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ __('forms.labels.financial_year') }}
                                        </label>
                                        <select id="financial_year_type" wire:model="financial_year_type" 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500">
                                            @foreach($this->selectedCountryInfo['financial_year_options'] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('financial_year_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                @endif

                                @if($this->selectedCountryInfo)
                                    <div class="md:col-span-2 bg-brand-50 p-4 rounded-md">
                                        <h4 class="text-sm font-medium text-brand-900 mb-2">{{ __('forms.labels.country_info') }}</h4>
                                        <div class="text-sm text-brand-800 space-y-1">
                                            <p><strong>{{ __('forms.labels.tax_system') }}</strong> {{ $this->selectedCountryInfo['tax_system']['name'] }}</p>
                                            <p><strong>{{ __('forms.labels.common_rates') }}</strong> {{ implode(', ', $this->selectedCountryInfo['tax_system']['rates']) }}%</p>
                                            <p><strong>{{ __('forms.labels.recommended_format') }}</strong> {{ $this->selectedCountryInfo['recommended_numbering'] }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                    <!-- Step 4: Contact Information -->
                    @elseif($currentStep === 4)
                        <div class="space-y-6">
                            <div>
                                <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ __('documents.headers.contact_details') }}</h2>
                                <p class="text-gray-600 mb-6">{{ __('forms.hints.contact_details_subtitle') }}</p>
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('forms.labels.email_addresses_required') }}
                                    </label>
                                    @foreach($emails as $index => $email)
                                        <div class="flex items-center mb-2">
                                            <input type="email" wire:model="emails.{{ $index }}" 
                                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500"
                                                   placeholder="{{ __('forms.placeholders.email_company') }}">
                                            @if($index > 0)
                                                <button type="button" wire:click="removeEmailField({{ $index }})"
                                                        aria-label="{{ __('actions.buttons.remove') }} email {{ $index + 1 }}"
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
                                            class="mt-2 text-brand-600 hover:text-brand-800 text-sm font-medium">
                                        {{ __('actions.buttons.add_email') }}
                                    </button>
                                    @error('emails') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('forms.labels.phone_number') }}
                                    </label>
                                    <input type="tel" id="phone" wire:model="phone"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500"
                                           placeholder="{{ __('forms.placeholders.phone_example') }}">
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
                                class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                            {{ __('actions.buttons.previous') }}
                        </button>
                    @else
                        <div></div>
                    @endif

                    @if($currentStep < $totalSteps)
                        <button type="button" wire:click="nextStep" 
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                            {{ __('actions.buttons.next') }}
                        </button>
                    @else
                        <button type="button"
                                x-data="{ loading: false }"
                                @click="loading = true; $wire.completeSetup()"
                                :disabled="loading"
                                class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!loading">{{ __('actions.buttons.complete_setup') }}</span>
                            <span x-show="loading">{{ __('messages.system.processing') }}</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>