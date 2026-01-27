{{-- Shared location address fields used by OrganizationManager and CustomerManager --}}
<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.location_name') }}</label>
    <input wire:model="location_name" type="text" placeholder="{{ __('forms.placeholders.location_name_optional') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-location_name">
    @error('location_name') <span id="error-location_name" class="text-red-600 text-sm">{{ $message }}</span> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.gstin') }}</label>
    <input wire:model="gstin" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-gstin">
    @error('gstin') <span id="error-gstin" class="text-red-600 text-sm">{{ $message }}</span> @enderror
</div>

<div class="{{ $fullWidth ?? false ? '' : 'md:col-span-2' }}">
    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.address_line_1_required') }}</label>
    <input wire:model="address_line_1" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-address_line_1">
    @error('address_line_1') <span id="error-address_line_1" class="text-red-600 text-sm">{{ $message }}</span> @enderror
</div>

<div class="{{ $fullWidth ?? false ? '' : 'md:col-span-2' }}">
    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.address_line_2') }}</label>
    <input wire:model="address_line_2" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-address_line_2">
    @error('address_line_2') <span id="error-address_line_2" class="text-red-600 text-sm">{{ $message }}</span> @enderror
</div>

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

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.postal_code_required') }}</label>
    <input wire:model="postal_code" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-postal_code">
    @error('postal_code') <span id="error-postal_code" class="text-red-600 text-sm">{{ $message }}</span> @enderror
</div>
