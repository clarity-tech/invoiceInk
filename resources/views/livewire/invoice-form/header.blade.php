<!-- Header with Navigation -->
<div class="mb-6 flex justify-between items-center">
    <div class="flex items-center space-x-4">
        <a href="{{ route('invoices.index') }}" class="text-brand-600 hover:text-brand-900">
            {{ __('actions.buttons.back_to_invoices') }}
        </a>
        <h1 class="text-3xl font-bold text-gray-900">{{ $this->pageTitle }}</h1>
    </div>
    @if($mode === 'edit' && $invoice && $invoice->ulid)
        <div class="flex space-x-2">
            <button wire:click="openEmailModal"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                <span wire:loading.remove wire:target="openEmailModal">{{ __('actions.buttons.send_email') }}</span>
                <span wire:loading wire:target="openEmailModal">{{ __('messages.system.opening') }}</span>
            </button>
            <a href="{{ route($invoice->type === 'invoice' ? 'invoices.public' : 'estimates.public', $invoice->ulid) }}"
               target="_blank" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                {{ __('actions.buttons.view_public') }}
            </a>
            <button wire:click="downloadPdf"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                <span wire:loading.remove wire:target="downloadPdf">{{ __('actions.buttons.download_pdf') }}</span>
                <span wire:loading wire:target="downloadPdf">{{ __('messages.system.generating') }}</span>
            </button>
        </div>
    @endif
</div>

@if (session()->has('message'))
    <div class="mb-4 p-4 text-green-700 bg-green-100 border border-green-300 rounded">
        {{ session('message') }}
    </div>
@endif
