<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        @include('livewire.invoice-form.header')

        <!-- Single Page Form -->
        <form wire:submit="save" class="space-y-6">
            @include('livewire.invoice-form.details')

            @include('livewire.invoice-form.customer-address')

            @include('livewire.invoice-form.items')

            @include('livewire.invoice-form.attachments')

            <!-- Action Buttons -->
            <div class="flex justify-between items-center pt-4">
                <button type="button" wire:click="cancel"
                        class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 font-medium">
                    {{ __('actions.buttons.cancel') }}
                </button>
                <button type="submit"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        wire:target="save"
                        class="px-6 py-2 bg-brand-600 text-white rounded-md hover:bg-brand-700 font-medium">
                    <span wire:loading.remove wire:target="save">{{ $mode === 'edit' ? __('actions.buttons.update') : __('actions.buttons.create') }} {{ ucfirst($type) }}</span>
                    <span wire:loading wire:target="save">{{ __('messages.system.saving') }}</span>
                </button>
            </div>
        </form>

        @include('livewire.invoice-form.email-modal')
    </div>
</div>
