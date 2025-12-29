<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-900">{{ __('actions.navigation.customers') }}</h1>
            @if (!$showForm)
                <button wire:click="create" class="bg-brand-500 hover:bg-brand-700 text-white font-bold py-2 px-4 rounded">
                    {{ __('actions.buttons.add_customer') }}
                </button>
            @endif
        </div>

        @if (session()->has('message'))
            <div class="mb-4 p-4 text-green-700 bg-green-100 border border-green-300 rounded">
                {{ session('message') }}
            </div>
        @endif

        @if ($showForm)
            <div class="mb-6 bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">
                        {{ $editingId ? __('documents.headers.edit_customer') : __('documents.headers.add_new_customer') }}
                    </h2>
                </div>
                
                <form wire:submit="save" class="p-6 space-y-6">
                    <!-- Customer Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.customer_name_required') }}</label>
                            <input wire:model="name" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.phone') }}</label>
                            <input wire:model="phone" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Contact Management -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('forms.labels.contact_info_required') }}</label>
                        <div class="space-y-3">
                            @foreach($contacts as $index => $contact)
                                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-600">{{ __('forms.labels.contact') }} {{ $index + 1 }}</span>
                                        @if(count($contacts) > 1)
                                            <button type="button" wire:click="removeContactField({{ $index }})" 
                                                    class="text-red-500 hover:text-red-700 font-bold text-lg">×</button>
                                        @endif
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('forms.labels.contact_name') }}</label>
                                            <input wire:model="contacts.{{ $index }}.name" type="text" placeholder="{{ __('forms.placeholders.contact_name_placeholder') }}" 
                                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                                            @error("contacts.{$index}.name") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('forms.labels.email_required') }}</label>
                                            <input wire:model="contacts.{{ $index }}.email" type="email" placeholder="{{ __('forms.placeholders.contact_email_placeholder') }}" 
                                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                                            @error("contacts.{$index}.email") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" wire:click="addContactField" class="mt-3 text-brand-500 hover:text-brand-700 text-sm font-medium">
                            {{ __('actions.buttons.add_contact') }}
                        </button>
                        @error('contacts') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Currency Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.currency_required') }}</label>
                        <select wire:model="currency" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            @foreach(\App\Currency::cases() as $currencyOption)
                                <option value="{{ $currencyOption->value }}">
                                    {{ $currencyOption->name() }} ({{ $currencyOption->symbol() }})
                                </option>
                            @endforeach
                        </select>
                        @error('currency') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

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
                            @error('location') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div class="flex justify-end space-x-3 pt-6 border-t">
                        <button type="button" wire:click="cancel" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            {{ __('actions.buttons.cancel') }}
                        </button>
                        <button type="submit" class="px-4 py-2 bg-brand-500 text-white rounded-md hover:bg-brand-600">
                            {{ $editingId ? __('actions.buttons.update') : __('actions.buttons.create') }} {{ __('forms.labels.customer') }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Customers List -->
        @if (!$showForm)
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('forms.labels.customer') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('forms.labels.contact') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('forms.labels.location') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('forms.labels.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($this->customers as $customer)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $customer->name }}</div>
                                    @if($customer->primaryLocation && $customer->primaryLocation->gstin)
                                        <div class="text-sm text-gray-500">{{ __('forms.labels.tax_id') }}: {{ $customer->primaryLocation->gstin }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        @if($customer->emails && !$customer->emails->isEmpty())
                                            @php
                                                $firstContact = $customer->emails->first();
                                                $contactName = $firstContact['name'] ?? '';
                                                $contactEmail = $firstContact['email'] ?? '';
                                            @endphp
                                            @if($contactName)
                                                {{ $contactName }}
                                                <div class="text-xs text-gray-500">{{ $contactEmail }}</div>
                                            @else
                                                {{ $contactEmail }}
                                            @endif
                                            @if($customer->emails->count() > 1)
                                                <span class="text-gray-500 text-xs">(+{{ $customer->emails->count() - 1 }} more)</span>
                                            @endif
                                        @endif
                                    </div>
                                    @if($customer->phone)
                                        <div class="text-sm text-gray-500">{{ $customer->phone }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $locationCount = $customer->locations()->count();
                                    @endphp
                                    @if($customer->primaryLocation)
                                        <div class="text-sm text-gray-900">{{ $customer->primaryLocation->name }}</div>
                                        <div class="text-sm text-gray-500">
                                            {{ $customer->primaryLocation->city }}, {{ $customer->primaryLocation->state }}
                                        </div>
                                        @if($locationCount > 1)
                                            <div class="text-xs text-brand-600 mt-1">+{{ $locationCount - 1 }} more location{{ $locationCount > 2 ? 's' : '' }}</div>
                                        @endif
                                    @else
                                        <div class="text-sm text-gray-500 italic">{{ __('forms.hints.no_location_set') }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button wire:click="edit({{ $customer->id }})" class="text-brand-600 hover:text-brand-900 mr-3">{{ __('actions.buttons.edit') }}</button>
                                    <button wire:click="delete({{ $customer->id }})" 
                                            wire:confirm="{{ __('actions.confirmations.confirm_delete_customer') }}"
                                            class="text-red-600 hover:text-red-900">{{ __('actions.buttons.delete') }}</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    {{ __('messages.empty_states.no_customers') }} <button wire:click="create" class="text-brand-500 hover:text-brand-700">{{ __('actions.buttons.create_first_customer') }}</button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                
                <div class="px-6 py-3 border-t border-gray-200">
                    {{ $this->customers->links() }}
                </div>
            </div>
        @endif

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
                                <select wire:model="country" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                    <option value="">{{ __('forms.placeholders.select_country') }}</option>
                                    @foreach(\App\Enums\Country::cases() as $countryOption)
                                        <option value="{{ $countryOption->value }}">
                                            {{ $countryOption->flag() }} {{ $countryOption->name() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('country') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.location_name_required') }}</label>
                                <input wire:model="location_name" type="text" placeholder="{{ __('forms.placeholders.location_name_hint') }}"
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                @error('location_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.tax_id') }}</label>
                            <input wire:model="gstin" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            @error('gstin') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.address_line_1_required') }}</label>
                            <input wire:model="address_line_1" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            @error('address_line_1') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.address_line_2') }}</label>
                            <input wire:model="address_line_2" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            @error('address_line_2') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.city_required') }}</label>
                                <input wire:model="city" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                @error('city') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.state_required') }}</label>
                                <input wire:model="state" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                @error('state') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.postal_code') }}</label>
                            <input wire:model="postal_code" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            @error('postal_code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
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
    </div>
</div>
