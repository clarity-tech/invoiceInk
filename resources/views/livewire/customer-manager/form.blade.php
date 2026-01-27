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
                <input wire:model="name" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-name">
                @error('name') <span id="error-name" class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.phone') }}</label>
                <input wire:model="phone" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-phone">
                @error('phone') <span id="error-phone" class="text-red-600 text-sm">{{ $message }}</span> @enderror
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
                                        aria-label="{{ __('actions.buttons.remove') }} {{ __('forms.labels.contact') }} {{ $index + 1 }}"
                                        class="text-red-500 hover:text-red-700 font-bold text-lg">×</button>
                            @endif
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('forms.labels.contact_name') }}</label>
                                <input wire:model="contacts.{{ $index }}.name" type="text" placeholder="{{ __('forms.placeholders.contact_name_placeholder') }}"
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm"
                                       aria-describedby="error-contacts-{{ $index }}-name">
                                @error("contacts.{$index}.name") <span id="error-contacts-{{ $index }}-name" class="text-red-600 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('forms.labels.email_required') }}</label>
                                <input wire:model="contacts.{{ $index }}.email" type="email" placeholder="{{ __('forms.placeholders.contact_email_placeholder') }}"
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm"
                                       aria-describedby="error-contacts-{{ $index }}-email">
                                @error("contacts.{$index}.email") <span id="error-contacts-{{ $index }}-email" class="text-red-600 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="button" wire:click="addContactField" class="mt-3 text-brand-500 hover:text-brand-700 text-sm font-medium">
                {{ __('actions.buttons.add_contact') }}
            </button>
            @error('contacts') <span id="error-contacts" class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Currency Selection -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.currency_required') }}</label>
            <select wire:model="currency" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500" aria-describedby="error-currency">
                @foreach(\App\Currency::cases() as $currencyOption)
                    <option value="{{ $currencyOption->value }}">
                        {{ $currencyOption->name() }} ({{ $currencyOption->symbol() }})
                    </option>
                @endforeach
            </select>
            @error('currency') <span id="error-currency" class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        @include('livewire.customer-manager.locations')

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
