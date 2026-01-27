<!-- Organization Information -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.organization_name_required') }}</label>
        <input wire:model="name" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-name">
        @error('name') <span id="error-name" class="text-red-600 text-sm">{{ $message }}</span> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.phone') }}</label>
        <input wire:model="phone" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-phone">
        @error('phone') <span id="error-phone" class="text-red-600 text-sm">{{ $message }}</span> @enderror
    </div>
</div>

<!-- Logo Upload Section -->
<div class="border-t pt-6">
    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('forms.labels.organization_logo') }}</h3>
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
                            wire:confirm="{{ __('actions.confirmations.confirm_remove_logo') }}"
                            aria-label="{{ __('actions.buttons.remove') }} {{ __('forms.labels.organization_logo') }}"
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
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('forms.labels.upload_logo') }}</label>
            <input type="file" wire:model="logo" accept="image/*"
                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
            <p class="mt-2 text-xs text-gray-500">{{ __('forms.hints.logo_requirements') }}</p>
            @error('logo') <span id="error-logo" class="text-red-600 text-sm">{{ $message }}</span> @enderror

            <div wire:loading wire:target="logo" class="mt-2">
                <div class="flex items-center text-sm text-brand-600">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('messages.system.uploading') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Email Management -->
<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('forms.labels.email_addresses_required') }}</label>
    <div class="space-y-2">
        @foreach($emails as $index => $email)
            <div class="flex items-center space-x-2">
                <input wire:model="emails.{{ $index }}" type="email" placeholder="{{ __('forms.placeholders.email_placeholder') }}"
                       class="flex-1 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                       aria-describedby="error-emails-{{ $index }}">
                @if(count($emails) > 1)
                    <button type="button" wire:click="removeEmailField({{ $index }})"
                            aria-label="{{ __('actions.buttons.remove') }} email {{ $index + 1 }}"
                            class="text-red-500 hover:text-red-700 font-bold text-lg">×</button>
                @endif
            </div>
            @error("emails.{$index}") <span id="error-emails-{{ $index }}" class="text-red-600 text-sm">{{ $message }}</span> @enderror
        @endforeach
    </div>
    <button type="button" wire:click="addEmailField" class="mt-2 text-brand-500 hover:text-brand-700 text-sm">
        {{ __('actions.buttons.add_email') }}
    </button>
    @error('emails') <span id="error-emails" class="text-red-600 text-sm">{{ $message }}</span> @enderror
</div>
