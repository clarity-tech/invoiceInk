<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-900">Customers</h1>
            @if (!$showForm)
                <button wire:click="create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Add Customer
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
                        {{ $editingId ? 'Edit Customer' : 'Add New Customer' }}
                    </h2>
                </div>
                
                <form wire:submit="save" class="p-6 space-y-6">
                    <!-- Customer Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name *</label>
                            <input wire:model="name" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input wire:model="phone" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Contact Management -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contact Information *</label>
                        <div class="space-y-3">
                            @foreach($contacts as $index => $contact)
                                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-600">Contact {{ $index + 1 }}</span>
                                        @if(count($contacts) > 1)
                                            <button type="button" wire:click="removeContactField({{ $index }})" 
                                                    class="text-red-500 hover:text-red-700 font-bold text-lg">×</button>
                                        @endif
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Contact Name</label>
                                            <input wire:model="contacts.{{ $index }}.name" type="text" placeholder="John Doe" 
                                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                            @error("contacts.{$index}.name") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Email Address *</label>
                                            <input wire:model="contacts.{{ $index }}.email" type="email" placeholder="john@example.com" 
                                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                            @error("contacts.{$index}.email") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" wire:click="addContactField" class="mt-3 text-blue-500 hover:text-blue-700 text-sm font-medium">
                            + Add another contact
                        </button>
                        @error('contacts') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Currency Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Currency *</label>
                        <select wire:model="currency" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                <h3 class="text-lg font-medium text-gray-900">Locations</h3>
                                <button type="button" wire:click="addLocation" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                                    + Add Location
                                </button>
                            </div>

                            @if($this->currentCustomerLocations->isEmpty())
                                <div class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                                    <p class="text-gray-500 mb-2">No locations added yet</p>
                                    <button type="button" wire:click="addLocation" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                                        Add your first location
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
                                                            <span class="px-2 py-1 text-xs font-semibold text-blue-700 bg-blue-100 rounded">Primary</span>
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
                                                        <p class="text-sm text-gray-500 mt-1">Tax Id: {{ $location->gstin }}</p>
                                                    @endif
                                                </div>
                                                <div class="flex gap-2 ml-4">
                                                    @if(!$isPrimary)
                                                        <button type="button" wire:click="setPrimaryLocation({{ $location->id }})"
                                                                class="text-gray-600 hover:text-blue-600 text-sm">
                                                            Set as Primary
                                                        </button>
                                                    @endif
                                                    <button type="button" wire:click="editLocation({{ $location->id }})"
                                                            class="text-blue-600 hover:text-blue-900 text-sm">
                                                        Edit
                                                    </button>
                                                    @if($this->currentCustomerLocations->count() > 1)
                                                        <button type="button" wire:click="deleteLocation({{ $location->id }})"
                                                                wire:confirm="Are you sure you want to delete this location?"
                                                                class="text-red-600 hover:text-red-900 text-sm">
                                                            Delete
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
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                            {{ $editingId ? 'Update' : 'Create' }} Customer
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($this->customers as $customer)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $customer->name }}</div>
                                    @if($customer->primaryLocation && $customer->primaryLocation->gstin)
                                        <div class="text-sm text-gray-500">Tax Id: {{ $customer->primaryLocation->gstin }}</div>
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
                                            <div class="text-xs text-blue-600 mt-1">+{{ $locationCount - 1 }} more location{{ $locationCount > 2 ? 's' : '' }}</div>
                                        @endif
                                    @else
                                        <div class="text-sm text-gray-500 italic">No location set</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button wire:click="edit({{ $customer->id }})" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                    <button wire:click="delete({{ $customer->id }})" 
                                            wire:confirm="Are you sure you want to delete this customer?"
                                            class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    No customers found. <button wire:click="create" class="text-blue-500 hover:text-blue-700">Create your first customer</button>
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
                            {{ $editingLocationId ? 'Edit Location' : 'Add New Location' }}
                        </h3>
                    </div>

                    <form wire:submit="saveLocation" class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                                <select wire:model="country" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Country</option>
                                    @foreach(\App\Enums\Country::cases() as $countryOption)
                                        <option value="{{ $countryOption->value }}">
                                            {{ $countryOption->flag() }} {{ $countryOption->name() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('country') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Location Name *</label>
                                <input wire:model="location_name" type="text" placeholder="e.g., Main Office, Warehouse, Branch 1"
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('location_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tax Id</label>
                            <input wire:model="gstin" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('gstin') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address Line 1 *</label>
                            <input wire:model="address_line_1" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('address_line_1') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address Line 2</label>
                            <input wire:model="address_line_2" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('address_line_2') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                                <input wire:model="city" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('city') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">State *</label>
                                <input wire:model="state" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('state') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                            <input wire:model="postal_code" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('postal_code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center">
                            <input wire:model="is_primary" type="checkbox" id="is_primary" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="is_primary" class="ml-2 block text-sm text-gray-700">
                                Set as primary location
                            </label>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4 border-t">
                            <button type="button" wire:click="cancelLocation" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                {{ $editingLocationId ? 'Update' : 'Add' }} Location
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
