<!-- Items Section -->
<div class="bg-white shadow rounded-lg p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-800">{{ __('documents.table.line_items') }}</h2>
        <button type="button" wire:click="addItem"
                class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium py-2 px-4 rounded">
            {{ __('actions.buttons.add_item') }}
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('forms.labels.description_required_asterisk') }}
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                        {{ __('forms.labels.quantity_required_asterisk') }}
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                        {{ __('forms.labels.rate', ['currency' => $this->currencySymbol]) }}
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                        {{ __('forms.labels.tax_percent') }}
                    </th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                        {{ __('forms.labels.amount') }}
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                        {{ __('forms.labels.action') }}
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($items as $index => $item)
                    <tr>
                        <td class="px-4 py-3">
                            <input wire:model.live="items.{{ $index }}.description" type="text"
                                   placeholder="{{ __('forms.placeholders.item_description') }}"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                                   aria-describedby="error-items-{{ $index }}-description">
                            @error("items.{$index}.description")
                                <span id="error-items-{{ $index }}-description" class="text-red-600 text-xs">{{ $message }}</span>
                            @enderror

                            <!-- SAC Code Field -->
                            <div class="mt-2">
                                <input wire:model.live="items.{{ $index }}.sac_code" type="text"
                                       placeholder="{{ __('forms.labels.sac_code_placeholder') }}"
                                       class="w-40 border border-gray-300 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-brand-500">
                                @if(!empty($item['sac_code']))
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        {{ __('forms.labels.services_sac') }} {{ $item['sac_code'] }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <input wire:model.live="items.{{ $index }}.quantity" type="number" min="1"
                                   placeholder="1"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                                   aria-describedby="error-items-{{ $index }}-quantity">
                            @error("items.{$index}.quantity")
                                <span id="error-items-{{ $index }}-quantity" class="text-red-600 text-xs">{{ $message }}</span>
                            @enderror
                        </td>
                        <td class="px-4 py-3">
                            <input wire:model.live="items.{{ $index }}.unit_price" type="number" step="0.01" min="0"
                                   placeholder="0.00"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                                   aria-describedby="error-items-{{ $index }}-unit_price">
                            @error("items.{$index}.unit_price")
                                <span id="error-items-{{ $index }}-unit_price" class="text-red-600 text-xs">{{ $message }}</span>
                            @enderror
                        </td>
                        <td class="px-4 py-3">
                            <input wire:model.live="items.{{ $index }}.tax_rate" type="number" step="0.01" min="0" max="100"
                                   placeholder="0"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                                   aria-describedby="error-items-{{ $index }}-tax_rate">
                            @error("items.{$index}.tax_rate")
                                <span id="error-items-{{ $index }}-tax_rate" class="text-red-600 text-xs">{{ $message }}</span>
                            @enderror
                        </td>
                        <td class="px-4 py-3 text-right">
                            @php
                                $lineTotal = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0) * 100;
                                $lineTax = $lineTotal * (($item['tax_rate'] ?? 0) / 100);
                                $lineAmount = $lineTotal + $lineTax;
                            @endphp
                            <span class="text-sm font-medium text-gray-900">
                                {{ $this->formatAmount((int)$lineAmount) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if(count($items) > 1)
                                <button type="button" wire:click="removeItem({{ $index }})"
                                        aria-label="{{ __('actions.buttons.remove_item') }} {{ $index + 1 }}"
                                        class="text-red-500 hover:text-red-700 font-bold text-lg">
                                    ×
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Totals Section -->
    <div class="mt-6 flex justify-end">
        <div class="w-full md:w-1/3 space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ __('documents.financial.subtotal') }}</span>
                <span class="font-medium text-gray-900">{{ $this->formatAmount($subtotal) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ __('documents.financial.tax') }}</span>
                <span class="font-medium text-gray-900">{{ $this->formatAmount($tax) }}</span>
            </div>
            <div class="border-t border-gray-300 pt-2 flex justify-between">
                <span class="text-lg font-bold text-gray-900">{{ __('documents.financial.total') }}</span>
                <span class="text-lg font-bold text-brand-600">{{ $this->formatAmount($total) }}</span>
            </div>
        </div>
    </div>
</div>
