<!-- Location Information -->
<div class="border-t pt-6">
    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('forms.labels.primary_location') }}</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @include('partials.location-fields')

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
    </div>
</div>
