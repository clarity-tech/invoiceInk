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
