<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        @include('livewire.organization-manager.header')

        @if ($showForm)
            <div class="mb-6 bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">
                        {{ $editingId ? __('documents.headers.edit_organization') : __('documents.headers.add_new_organization') }}
                    </h2>
                </div>

                <form wire:submit="save" class="p-6 space-y-6">
                    @include('livewire.organization-manager.form-basics')

                    @include('livewire.organization-manager.form-country')

                    @include('livewire.organization-manager.form-location')

                    @include('livewire.organization-manager.form-bank-details')

                    <div class="flex justify-end space-x-3 pt-6 border-t">
                        <button type="button" wire:click="cancel" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            {{ __('actions.buttons.cancel') }}
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-brand-500 text-white rounded-md hover:bg-brand-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                wire:loading.attr="disabled"
                                wire:target="save">
                            <span wire:loading.remove wire:target="save">
                                {{ $editingId ? __('actions.buttons.update') : __('actions.buttons.create') }} {{ __('forms.labels.organization') }}
                            </span>
                            <span wire:loading wire:target="save">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ $editingId ? __('messages.system.updating') : __('messages.system.creating') }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        @endif

        @include('livewire.organization-manager.list')
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
                    <span>{{ __('messages.system.network_error') }}</span>
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
