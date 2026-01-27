<!-- Location Modal -->
@if($showLocationModal)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-800">
                    {{ $editingLocationId ? __('documents.headers.edit_location') : __('documents.headers.add_new_location') }}
                </h3>
            </div>

            <form wire:submit="saveLocation" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.country_required') }}</label>
                        <select wire:model="country" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-country">
                            <option value="">{{ __('forms.placeholders.select_country') }}</option>
                            @foreach(\App\Enums\Country::cases() as $countryOption)
                                <option value="{{ $countryOption->value }}">
                                    {{ $countryOption->flag() }} {{ $countryOption->name() }}
                                </option>
                            @endforeach
                        </select>
                        @error('country') <span id="error-country" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.location_name_required') }}</label>
                        <input wire:model="location_name" type="text" placeholder="{{ __('forms.placeholders.location_name_hint') }}"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                               aria-describedby="error-location_name">
                        @error('location_name') <span id="error-location_name" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.tax_id') }}</label>
                    <input wire:model="gstin" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-gstin">
                    @error('gstin') <span id="error-gstin" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.address_line_1_required') }}</label>
                    <input wire:model="address_line_1" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-address_line_1">
                    @error('address_line_1') <span id="error-address_line_1" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.address_line_2') }}</label>
                    <input wire:model="address_line_2" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-address_line_2">
                    @error('address_line_2') <span id="error-address_line_2" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
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
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.postal_code') }}</label>
                    <input wire:model="postal_code" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-postal_code">
                    @error('postal_code') <span id="error-postal_code" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center">
                    <input wire:model="is_primary" type="checkbox" id="is_primary" class="h-4 w-4 text-brand-600 focus:ring-brand-500 border-gray-300 rounded">
                    <label for="is_primary" class="ml-2 block text-sm text-gray-700">
                        {{ __('forms.labels.set_primary_location') }}
                    </label>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button type="button" wire:click="cancelLocation" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        {{ __('actions.buttons.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 bg-brand-500 text-white rounded-md hover:bg-brand-600">
                        {{ $editingLocationId ? __('actions.buttons.update') : __('actions.buttons.add') }} {{ __('forms.labels.location') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
