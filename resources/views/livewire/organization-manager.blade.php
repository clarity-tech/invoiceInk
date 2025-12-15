<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    @if($autoEdit)
                        Manage Your Business
                    @else
                        Organizations
                    @endif
                </h1>
                @if($autoEdit)
                    <div class="mt-2">
                        <a href="{{ route('organizations.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
                            ← View all organizations
                        </a>
                    </div>
                @endif
            </div>
            @if (!$showForm && !$autoEdit)
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

                    <!-- Logo Upload Section -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Organization Logo</h3>
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
                                                wire:confirm="Are you sure you want to remove the logo?"
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
                                <label class="block text-sm font-medium text-gray-700 mb-2">Upload Logo</label>
                                <input type="file" wire:model="logo" accept="image/*"
                                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <p class="mt-2 text-xs text-gray-500">PNG, JPG, GIF, or SVG. Max 2MB. Recommended size: 200x200px.</p>
                                @error('logo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                                <div wire:loading wire:target="logo" class="mt-2">
                                    <div class="flex items-center text-sm text-blue-600">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Uploading...
                                    </div>
                                </div>
                            </div>
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
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                wire:loading.attr="disabled"
                                wire:target="save">
                            <span wire:loading.remove wire:target="save">
                                {{ $editingId ? 'Update' : 'Create' }} Organization
                            </span>
                            <span wire:loading wire:target="save">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ $editingId ? 'Updating...' : 'Creating...' }}
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
                    <span>Network error occurred. Please try again.</span>
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
