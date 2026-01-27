<!-- Organizations List -->
@if (!$showForm && !$autoEdit)
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('forms.labels.organization') }}</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('forms.labels.contact') }}</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('forms.labels.location') }}</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('forms.labels.action') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($this->organizations as $organization)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $organization->displayName }}</div>
                            @if($organization->currency)
                                <div class="text-sm text-gray-500">{{ $organization->currency->symbol() }} {{ $organization->currency->value }}</div>
                            @endif
                            @if($organization->primaryLocation && $organization->primaryLocation->gstin)
                                <div class="text-sm text-gray-500">{{ __('documents.fields.gstin') }} {{ $organization->primaryLocation->gstin }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                @if($organization->emails && !$organization->emails->isEmpty())
                                    {{ $organization->emails->getFirstEmail() }}
                                    @if($organization->emails->count() > 1)
                                        <span class="text-gray-500">(+{{ $organization->emails->count() - 1 }} more)</span>
                                    @endif
                                @endif
                            </div>
                            @if($organization->phone)
                                <div class="text-sm text-gray-500">{{ $organization->phone }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($organization->primaryLocation)
                                <div class="text-sm text-gray-900">{{ $organization->primaryLocation->name }}</div>
                                <div class="text-sm text-gray-500">
                                    {{ $organization->primaryLocation->city }}, {{ $organization->primaryLocation->state }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="edit({{ $organization->id }})" class="text-brand-600 hover:text-brand-900 mr-3">{{ __('actions.buttons.edit') }}</button>
                            <button wire:click="delete({{ $organization->id }})"
                                    wire:confirm="{{ __('actions.confirmations.confirm_delete_organization') }}"
                                    class="text-red-600 hover:text-red-900">{{ __('actions.buttons.delete') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                            {{ __('messages.empty_states.no_organizations') }} <button wire:click="create" class="text-brand-500 hover:text-brand-700">{{ __('actions.buttons.create_first_organization') }}</button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-3 border-t border-gray-200">
            {{ $this->organizations->links() }}
        </div>
    </div>
@endif
