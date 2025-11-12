<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-900">Organizations</h1>
            @if (!$showForm)
                <button wire:click="create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Add Organization
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
                        {{ $editingId ? 'Edit Organization' : 'Add New Organization' }}
                    </h2>
                </div>
                
                <form wire:submit="save" class="p-6 space-y-6">
                    <!-- Organization Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Organization Name *</label>
                            <input wire:model="name" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input wire:model="phone" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Country and Business Configuration -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Business Configuration</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                                <select wire:model.live="country_code" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Country</option>
                                    @foreach($this->availableCountries as $country)
                                        <option value="{{ $country['value'] }}">{{ $country['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('country_code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                
                                @if($this->selectedCountryInfo)
                                    <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                        <div class="text-sm text-blue-800">
                                            <div class="font-medium">{{ $this->selectedCountryInfo['tax_system']['name'] ?? 'Tax System' }}</div>
                                            @if(isset($this->selectedCountryInfo['tax_system']['rates']))
                                                <div class="text-xs mt-1">Common rates: {{ implode(', ', array_map(fn($rate) => $rate.'%', $this->selectedCountryInfo['tax_system']['rates'])) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Currency *</label>
                                <select wire:model="currency" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" @if($country_code) disabled @endif>
                                    <option value="">Select Currency</option>
                                    @foreach($this->availableCurrencies as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('currency') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                
                                @if($country_code && $this->selectedCountryInfo)
                                    <div class="mt-1 text-xs text-gray-500">
                                        Recommended: {{ $this->selectedCountryInfo['default_currency'] }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($country_code && $this->selectedCountryInfo)
                            <!-- Financial Year Configuration -->
                            <div class="mt-6 border-t pt-6">
                                <h4 class="text-md font-medium text-gray-900 mb-3">Financial Year Configuration</h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Financial Year Type</label>
                                        <select wire:model="financial_year_type" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">Select Financial Year</option>
                                            @foreach($this->selectedCountryInfo['financial_year_options'] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('financial_year_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Month</label>
                                        <select wire:model="financial_year_start_month" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @for($month = 1; $month <= 12; $month++)
                                                <option value="{{ $month }}">{{ date('F', mktime(0, 0, 0, $month, 1)) }}</option>
                                            @endfor
                                        </select>
                                        @error('financial_year_start_month') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Day</label>
                                        <input wire:model="financial_year_start_day" type="number" min="1" max="31" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error('financial_year_start_day') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                @if($this->selectedCountryInfo['recommended_numbering'])
                                    <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-md">
                                        <div class="text-sm text-green-800">
                                            <div class="font-medium">Invoice Numbering Recommendation</div>
                                            <div class="text-xs mt-1">{{ $this->selectedCountryInfo['recommended_numbering'] }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Email Management -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Addresses *</label>
                        <div class="space-y-2">
                            @foreach($emails as $index => $email)
                                <div class="flex items-center space-x-2">
                                    <input wire:model="emails.{{ $index }}" type="email" placeholder="email@example.com" 
                                           class="flex-1 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @if(count($emails) > 1)
                                        <button type="button" wire:click="removeEmailField({{ $index }})" 
                                                class="text-red-500 hover:text-red-700 font-bold text-lg">×</button>
                                    @endif
                                </div>
                                @error("emails.{$index}") <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            @endforeach
                        </div>
                        <button type="button" wire:click="addEmailField" class="mt-2 text-blue-500 hover:text-blue-700 text-sm">
                            + Add another email
                        </button>
                        @error('emails') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Location Information -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Primary Location</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Location Name</label>
                                <input wire:model="location_name" type="text" placeholder="Optional - defaults to organization name" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('location_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">GSTIN</label>
                                <input wire:model="gstin" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('gstin') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address Line 1 *</label>
                                <input wire:model="address_line_1" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('address_line_1') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address Line 2</label>
                                <input wire:model="address_line_2" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('address_line_2') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

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

                            @if($country_code)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Location Country</label>
                                    <div class="w-full border border-gray-200 rounded-md px-3 py-2 bg-gray-50 text-gray-700">
                                        @foreach($this->availableCountries as $countryData)
                                            @if($countryData['value'] === $country_code)
                                                {{ $countryData['label'] }}
                                                <span class="text-xs text-gray-500 ml-2">(Inherited from organization)</span>
                                            @endif
                                        @endforeach
                                    </div>
                                    <div class="mt-1 text-xs text-gray-600">
                                        Location will use the same country as the organization. Change the organization country above to update this.
                                    </div>
                                </div>
                            @endif

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code *</label>
                                <input wire:model="postal_code" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('postal_code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t">
                        <button type="button" wire:click="cancel" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                            {{ $editingId ? 'Update' : 'Create' }} Organization
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Organizations List -->
        @if (!$showForm)
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Organization</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($this->organizations as $organization)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $organization->name }}</div>
                                    @if($organization->currency)
                                        <div class="text-sm text-gray-500">{{ $organization->currency->symbol() }} {{ $organization->currency->value }}</div>
                                    @endif
                                    @if($organization->primaryLocation && $organization->primaryLocation->gstin)
                                        <div class="text-sm text-gray-500">GSTIN: {{ $organization->primaryLocation->gstin }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        @if($organization->emails && !$organization->emails->isEmpty())
                                            {{ $organization->emails->first() }}
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
                                    <button wire:click="edit({{ $organization->id }})" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                    <button wire:click="delete({{ $organization->id }})" 
                                            wire:confirm="Are you sure you want to delete this organization?"
                                            class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    No organizations found. <button wire:click="create" class="text-blue-500 hover:text-blue-700">Create your first organization</button>
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
</div>
