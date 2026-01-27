<div class="mb-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-gray-900">{{ __('actions.navigation.customers') }}</h1>
    @if (!$showForm)
        <button wire:click="create" class="bg-brand-500 hover:bg-brand-700 text-white font-bold py-2 px-4 rounded">
            {{ __('actions.buttons.add_customer') }}
        </button>
    @endif
</div>

@if (session()->has('message'))
    <div class="mb-4 p-4 text-green-700 bg-green-100 border border-green-300 rounded">
        {{ session('message') }}
    </div>
@endif
