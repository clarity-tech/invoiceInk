<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-900">{{ __('actions.navigation.invoices') }} & {{ __('actions.navigation.estimates') }}</h1>
            <div class="space-x-2">
                <a href="{{ route('estimates.create') }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    {{ __('actions.buttons.create_estimate') }}
                </a>
                <a href="{{ route('invoices.create') }}" class="bg-brand-500 hover:bg-brand-700 text-white font-bold py-2 px-4 rounded">
                    {{ __('actions.buttons.create_invoice') }}
                </a>
            </div>
        </div>

        @if (session()->has('message'))
            <div class="mb-4 p-4 text-green-700 bg-green-100 border border-green-300 rounded">
                {{ session('message') }}
            </div>
        @endif

        <!-- Invoices List -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('documents.fields.document_type') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('documents.fields.organization') }} → {{ __('documents.fields.customer') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('documents.table.amount') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('documents.fields.status') }}</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('actions.table_actions.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($this->invoices as $invoice)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $invoice->type === 'invoice' ? 'bg-brand-100 text-brand-800' : 'bg-green-100 text-green-800' }}">
                                        {{ strtoupper($invoice->type) }}
                                    </span>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">{{ $invoice->invoice_number }}</div>
                                        @if($invoice->issued_at)
                                            <div class="text-sm text-gray-500">{{ $invoice->issued_at->format('M d, Y') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ $invoice->organizationLocation->locatable->name ?? 'N/A' }} 
                                    → {{ $invoice->customerLocation->locatable->name ?? 'N/A' }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $invoice->organizationLocation->city ?? '' }} → {{ $invoice->customerLocation->city ?? '' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $invoice->formatted_total }}</div>
                                @if($invoice->tax > 0)
                                    <div class="text-sm text-gray-500">{{ __('documents.financial.tax') }} {{ $invoice->formatted_tax }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $invoice->status->badge() }}">
                                    {{ $invoice->status->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route($invoice->type === 'invoice' ? 'invoices.public' : 'estimates.public', $invoice->ulid) }}"
                                   target="_blank" class="text-green-600 hover:text-green-900 mr-3">{{ __('actions.buttons.view') }}</a>
                                <a href="{{ route($invoice->type === 'invoice' ? 'invoices.pdf' : 'estimates.pdf', $invoice->ulid) }}"
                                   class="text-red-600 hover:text-red-900 mr-3">{{ __('actions.buttons.download_pdf') }}</a>
                                @if($invoice->type === 'estimate')
                                    <button wire:click="convertToInvoice({{ $invoice->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="convertToInvoice({{ $invoice->id }})"
                                            class="text-purple-600 hover:text-purple-900 mr-3">{{ __('actions.buttons.convert_to_invoice') }}</button>
                                @endif
                                <button wire:click="duplicate({{ $invoice->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="duplicate({{ $invoice->id }})"
                                        class="text-gray-600 hover:text-gray-900 mr-3">{{ __('actions.buttons.duplicate') }}</button>
                                <a href="{{ route('invoices.edit', $invoice) }}" class="text-brand-600 hover:text-brand-900 mr-3">{{ __('actions.buttons.edit') }}</a>
                                <button wire:click="delete({{ $invoice->id }})"
                                        wire:confirm="{{ __('forms.messages.confirm_delete', ['type' => $invoice->type]) }}"
                                        class="text-red-600 hover:text-red-900">{{ __('actions.buttons.delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                {{ __('forms.messages.no_documents_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            
            <div class="px-6 py-3 border-t border-gray-200">
                {{ $this->invoices->links() }}
            </div>
        </div>
    </div>
</div>