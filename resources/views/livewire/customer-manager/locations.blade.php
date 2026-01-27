<!-- Locations Section (only show when editing) -->
@if($editingId)
    <div class="border-t pt-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">{{ __('documents.headers.locations') }}</h3>
            <button type="button" wire:click="addLocation" class="text-brand-500 hover:text-brand-700 text-sm font-medium">
                {{ __('actions.buttons.add_location_plus') }}
            </button>
        </div>

        @if($this->currentCustomerLocations->isEmpty())
            <div class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                <p class="text-gray-500 mb-2">{{ __('forms.hints.no_locations_yet') }}</p>
                <button type="button" wire:click="addLocation" class="text-brand-500 hover:text-brand-700 text-sm font-medium">
                    {{ __('actions.buttons.add_first_location') }}
                </button>
            </div>
        @else
            <div class="space-y-3">
                @foreach($this->currentCustomerLocations as $location)
                    @php
                        $customer = \App\Models\Customer::find($editingId);
                        $isPrimary = $customer && $customer->primary_location_id === $location->id;
                    @endphp
                    <div class="border border-gray-200 rounded-lg p-4 bg-white hover:shadow-sm transition-shadow">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h4 class="font-medium text-gray-900">{{ $location->name }}</h4>
                                    @if($isPrimary)
                                        <span class="px-2 py-1 text-xs font-semibold text-brand-700 bg-brand-100 rounded">{{ __('forms.labels.primary') }}</span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-600">
                                    {{ $location->address_line_1 }}
                                    @if($location->address_line_2), {{ $location->address_line_2 }}@endif
                                </p>
                                <p class="text-sm text-gray-600">
                                    {{ $location->city }}, {{ $location->state }} {{ $location->postal_code }}
                                </p>
                                <p class="text-sm text-gray-600">{{ $location->country }}</p>
                                @if($location->gstin)
                                    <p class="text-sm text-gray-500 mt-1">{{ __('forms.labels.tax_id') }}: {{ $location->gstin }}</p>
                                @endif
                            </div>
                            <div class="flex gap-2 ml-4">
                                @if(!$isPrimary)
                                    <button type="button" wire:click="setPrimaryLocation({{ $location->id }})"
                                            class="text-gray-600 hover:text-brand-600 text-sm">
                                        {{ __('actions.buttons.set_primary') }}
                                    </button>
                                @endif
                                <button type="button" wire:click="editLocation({{ $location->id }})"
                                        class="text-brand-600 hover:text-brand-900 text-sm">
                                    {{ __('actions.buttons.edit') }}
                                </button>
                                @if($this->currentCustomerLocations->count() > 1)
                                    <button type="button" wire:click="deleteLocation({{ $location->id }})"
                                            wire:confirm="{{ __('actions.confirmations.confirm_delete_location') }}"
                                            class="text-red-600 hover:text-red-900 text-sm">
                                        {{ __('actions.buttons.delete') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        @error('location') <span id="error-location" class="text-red-600 text-sm">{{ $message }}</span> @enderror
    </div>
@endif
