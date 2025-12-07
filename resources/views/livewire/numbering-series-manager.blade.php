<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Numbering Series Management') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Flash Messages -->
            @if (session('message'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">
                    {{ session('message') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Create/Edit Form -->
            @if ($showCreateForm)
                <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            {{ $editingId ? 'Edit Numbering Series' : 'Create New Numbering Series' }}
                        </h3>

                        <form wire:submit="save" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Organization -->
                                <div>
                                    <label for="organization_id" class="block text-sm font-medium text-gray-700">
                                        Organization *
                                    </label>
                                    <select wire:model.live="organization_id" id="organization_id" 
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Select Organization</option>
                                        @foreach ($this->organizations as $org)
                                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('organization_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Location (Optional) -->
                                <div>
                                    <label for="location_id" class="block text-sm font-medium text-gray-700">
                                        Location (Optional)
                                    </label>
                                    <select wire:model.live="location_id" id="location_id" 
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Organization-wide (All Locations)</option>
                                        @foreach ($this->organizationLocations as $location)
                                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('location_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Name -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700">
                                        Series Name *
                                    </label>
                                    <input wire:model.live="name" type="text" id="name" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                           placeholder="e.g., Default Invoice Series">
                                    @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Prefix -->
                                <div>
                                    <label for="prefix" class="block text-sm font-medium text-gray-700">
                                        Prefix *
                                    </label>
                                    <input wire:model.live="prefix" type="text" id="prefix" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                           placeholder="e.g., INV, BILL, DXB">
                                    @error('prefix') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Format Pattern -->
                                <div>
                                    <label for="format_pattern" class="block text-sm font-medium text-gray-700">
                                        Format Pattern *
                                    </label>
                                    <input wire:model.live="format_pattern" type="text" id="format_pattern"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                           placeholder="{PREFIX}{YEAR}{MONTH}{SEQUENCE:4}">
                                    @error('format_pattern') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    <p class="mt-1 text-sm text-gray-500">
                                        Available tokens: {PREFIX}, {YEAR}, {YEAR:2}, {MONTH}, {MONTH:3}, {DAY}, {SEQUENCE}, {SEQUENCE:4}, {FY}
                                    </p>
                                    <!-- <p class="mt-1 text-xs text-gray-400">
                                        FY tokens: {FY} = Start year (2024), {FY_FULL} = Short format (2024-25), {FY_RANGE} = Full format (2024-2025)
                                    </p> -->
                                </div>

                                <!-- Current Number -->
                                <div>
                                    <label for="current_number" class="block text-sm font-medium text-gray-700">
                                        Current Number *
                                    </label>
                                    <input wire:model.live="current_number" type="number" id="current_number" min="0"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('current_number') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Reset Frequency -->
                                <div>
                                    <label for="reset_frequency" class="block text-sm font-medium text-gray-700">
                                        Reset Frequency *
                                    </label>
                                    <select wire:model.live="reset_frequency" id="reset_frequency" 
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach ($this->resetFrequencyOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('reset_frequency') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Status Flags -->
                                <div class="md:col-span-2">
                                    <div class="flex items-center space-x-6">
                                        <label class="inline-flex items-center">
                                            <input wire:model="is_active" type="checkbox" 
                                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <span class="ml-2 text-sm text-gray-600">Active</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input wire:model="is_default" type="checkbox" 
                                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <span class="ml-2 text-sm text-gray-600">Default Series</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Next Number Preview -->
                            @if ($this->nextNumberPreview)
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <h4 class="text-sm font-medium text-gray-900">Next Invoice Number Preview:</h4>
                                    <p class="text-lg font-mono text-indigo-600">{{ $this->nextNumberPreview }}</p>
                                </div>
                            @endif

                            <!-- Form Actions -->
                            <div class="flex justify-end space-x-3">
                                <button type="button" wire:click="cancel" 
                                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    {{ $editingId ? 'Update' : 'Create' }} Series
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Informational Banner -->
            <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">{{ __('forms.numbering_series.auto_creation_title') }}</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>{{ __('forms.numbering_series.auto_creation_explanation') }}</p>
                            
                            @if(!$this->hasAnySeriesForCurrentOrg && $this->automaticSeriesPreview)
                                <div class="mt-4 p-3 bg-blue-100 rounded-md">
                                    <h4 class="text-sm font-medium text-blue-800 mb-2">{{ __('forms.numbering_series.automatic_preview_title') }}</h4>
                                    <div class="text-xs text-blue-700">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <span class="font-medium">Series Name:</span> {{ $this->automaticSeriesPreview['series']->name }}
                                            </div>
                                            <div>
                                                <span class="font-medium">Format:</span> <code class="bg-blue-200 px-1 rounded">{{ $this->automaticSeriesPreview['series']->format_pattern }}</code>
                                            </div>
                                            <div>
                                                <span class="font-medium">Reset:</span> {{ $this->automaticSeriesPreview['series']->reset_frequency->label() }}
                                            </div>
                                            <div>
                                                <span class="font-medium">Next Number:</span> <code class="bg-blue-200 px-1 rounded">{{ $this->automaticSeriesPreview['preview_number'] }}</code>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <div class="mt-3">
                                <p class="font-medium">{{ __('forms.numbering_series.manual_creation_benefits') }}</p>
                                <ul class="mt-1 list-disc list-inside space-y-1">
                                    <li>{{ __('forms.numbering_series.benefit_custom_prefixes') }}</li>
                                    <li>{{ __('forms.numbering_series.benefit_different_formats') }}</li>
                                    <li>{{ __('forms.numbering_series.benefit_location_specific') }}</li>
                                    <li>{{ __('forms.numbering_series.benefit_multiple_series') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Series List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Numbering Series</h3>
                        @if (!$showCreateForm)
                            <button wire:click="create" 
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                Create New Series
                            </button>
                        @endif
                    </div>

                    <!-- Series Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Organization
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Location
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Series Name
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Format
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Current #
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($this->series as $series)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $series->organization->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $series->location ? $series->location->name : 'All Locations' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $series->name }}
                                            @if ($series->is_default)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 ml-2">
                                                    Default
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                            {{ $series->format_pattern }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $series->current_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $series->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $series->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button wire:click="edit({{ $series->id }})" 
                                                        class="text-indigo-600 hover:text-indigo-900">
                                                    Edit
                                                </button>
                                                <button wire:click="toggleActive({{ $series->id }})" 
                                                        class="text-yellow-600 hover:text-yellow-900">
                                                    {{ $series->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                                @if (!$series->is_default)
                                                    <button wire:click="setAsDefault({{ $series->id }})" 
                                                            class="text-blue-600 hover:text-blue-900">
                                                        Set Default
                                                    </button>
                                                @endif
                                                <button wire:click="delete({{ $series->id }})" 
                                                        onclick="return confirm('Are you sure? This action cannot be undone.')"
                                                        class="text-red-600 hover:text-red-900">
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-12">
                                            <div class="text-center">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('forms.numbering_series.no_series_empty_title') }}</h3>
                                                <p class="mt-1 text-sm text-gray-500">{{ __('forms.numbering_series.no_series_empty_subtitle') }}</p>
                                                <div class="mt-4">
                                                    <p class="text-sm text-gray-600 mb-2">{{ __('forms.numbering_series.create_custom_series') }}</p>
                                                    @if (!$showCreateForm)
                                                        <button wire:click="create" 
                                                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                            <svg class="-ml-1 mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                                            </svg>
                                                            Create Custom Series
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $this->series->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>