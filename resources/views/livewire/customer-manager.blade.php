<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        @include('livewire.customer-manager.header')

        @if ($showForm)
            @include('livewire.customer-manager.form')
        @endif

        @include('livewire.customer-manager.list')

        @include('livewire.customer-manager.location-modal')
    </div>
</div>
