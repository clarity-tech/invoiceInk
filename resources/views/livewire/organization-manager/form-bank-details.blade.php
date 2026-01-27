<!-- Bank Details -->
<div class="border-t pt-6">
    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('forms.labels.bank_details') }}</h3>
    <p class="text-sm text-gray-500 mb-4">{{ __('forms.hints.bank_details_optional') ?? 'Optional. Bank details will appear on your invoices.' }}</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.bank_account_name') }}</label>
            <input wire:model="bank_account_name" type="text" placeholder="{{ $name }}" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.bank_account_number') }}</label>
            <input wire:model="bank_account_number" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.bank_name') }}</label>
            <input wire:model="bank_name" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.bank_ifsc') }}</label>
            <input wire:model="bank_ifsc" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.bank_branch') }}</label>
            <input wire:model="bank_branch" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.bank_swift') }}</label>
            <input wire:model="bank_swift" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.bank_pan') }}</label>
            <input wire:model="bank_pan" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
    </div>
</div>
