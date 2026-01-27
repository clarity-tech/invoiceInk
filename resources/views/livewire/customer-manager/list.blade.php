<!-- Customers List -->
@if (!$showForm)
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('forms.labels.customer') }}</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('forms.labels.contact') }}</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('forms.labels.location') }}</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('forms.labels.action') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($this->customers as $customer)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $customer->name }}</div>
                            @if($customer->primaryLocation && $customer->primaryLocation->gstin)
                                <div class="text-sm text-gray-500">{{ __('forms.labels.tax_id') }}: {{ $customer->primaryLocation->gstin }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                @if($customer->emails && !$customer->emails->isEmpty())
                                    @php
                                        $firstContact = $customer->emails->first();
                                        $contactName = $firstContact['name'] ?? '';
                                        $contactEmail = $firstContact['email'] ?? '';
                                    @endphp
                                    @if($contactName)
                                        {{ $contactName }}
                                        <div class="text-xs text-gray-500">{{ $contactEmail }}</div>
                                    @else
                                        {{ $contactEmail }}
                                    @endif
                                    @if($customer->emails->count() > 1)
                                        <span class="text-gray-500 text-xs">(+{{ $customer->emails->count() - 1 }} more)</span>
                                    @endif
                                @endif
                            </div>
                            @if($customer->phone)
                                <div class="text-sm text-gray-500">{{ $customer->phone }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $locationCount = $customer->locations()->count();
                            @endphp
                            @if($customer->primaryLocation)
                                <div class="text-sm text-gray-900">{{ $customer->primaryLocation->name }}</div>
                                <div class="text-sm text-gray-500">
                                    {{ $customer->primaryLocation->city }}, {{ $customer->primaryLocation->state }}
                                </div>
                                @if($locationCount > 1)
                                    <div class="text-xs text-brand-600 mt-1">+{{ $locationCount - 1 }} more location{{ $locationCount > 2 ? 's' : '' }}</div>
                                @endif
                            @else
                                <div class="text-sm text-gray-500 italic">{{ __('forms.hints.no_location_set') }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="edit({{ $customer->id }})" class="text-brand-600 hover:text-brand-900 mr-3">{{ __('actions.buttons.edit') }}</button>
                            <button wire:click="delete({{ $customer->id }})"
                                    wire:confirm="{{ __('actions.confirmations.confirm_delete_customer') }}"
                                    class="text-red-600 hover:text-red-900">{{ __('actions.buttons.delete') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                            {{ __('messages.empty_states.no_customers') }} <button wire:click="create" class="text-brand-500 hover:text-brand-700">{{ __('actions.buttons.create_first_customer') }}</button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-3 border-t border-gray-200">
            {{ $this->customers->links() }}
        </div>
    </div>
@endif
