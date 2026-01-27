<!-- Organization Location Section -->
<div class="bg-white shadow rounded-lg p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">{{ __('documents.headers.organization_location') }}</h2>
    @if($organization_id && $this->organizationLocations->count() > 0)
        <div class="max-w-md">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.location_required') }}</label>
            <select wire:model.live="organization_location_id"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                    aria-describedby="error-organization_location_id">
                <option value="">{{ __('forms.labels.select_location') }}</option>
                @foreach($this->organizationLocations as $location)
                    <option value="{{ $location->id }}">{{ $location->name }} - {{ $location->city }}</option>
                @endforeach
            </select>
            @error('organization_location_id') <span id="error-organization_location_id" class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
    @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3 max-w-md">
            <p class="text-sm text-yellow-700">
                {{ __('forms.hints.no_org_location') }}
                <a href="/organizations" class="font-medium underline hover:text-yellow-600" target="_blank">
                    {{ __('actions.buttons.add_location') }}
                </a>
            </p>
        </div>
    @endif
</div>

<!-- Invoice Details Section -->
<div class="bg-white shadow rounded-lg p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">{{ __('documents.headers.invoice_details') }}</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.document_type') }}</label>
            <select wire:model="type"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                    {{ $mode === 'edit' ? 'disabled' : '' }}>
                <option value="invoice">{{ __('documents.types.invoice') }}</option>
                <option value="estimate">{{ __('documents.types.estimate') }}</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('forms.labels.status_required') }}</label>
            <select wire:model="status"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                    aria-describedby="error-status">
                @foreach(\App\Enums\InvoiceStatus::cases() as $statusOption)
                    <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                @endforeach
            </select>
            @error('status') <span id="error-status" class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        @if($type === 'invoice' && $organization_id && $this->availableNumberingSeries->count() > 0)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('documents.fields.numbering_series_optional') }}</label>
                <select wire:model.live="invoice_numbering_series_id"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                        aria-describedby="error-invoice_numbering_series_id">
                    <option value="">{{ __('forms.labels.auto_select_series') }}</option>
                    @foreach($this->availableNumberingSeries as $series)
                        <option value="{{ $series->id }}">
                            {{ $series->name }}
                            @if($series->location)
                                ({{ $series->location->name }})
                            @else
                                {{ __('forms.labels.organization_wide') }}
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('invoice_numbering_series_id') <span id="error-invoice_numbering_series_id" class="text-red-600 text-sm">{{ $message }}</span> @enderror
                @if($this->selectedSeriesPreview)
                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('forms.hints.next_number_preview') }} <span class="font-mono text-brand-600">{{ $this->selectedSeriesPreview }}</span>
                    </p>
                @endif
            </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('documents.fields.issue_date') }}</label>
            <input wire:model="issued_at" type="date"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                   aria-describedby="error-issued_at">
            @error('issued_at') <span id="error-issued_at" class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('documents.fields.due_date') }}</label>
            <input wire:model="due_at" type="date"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                   aria-describedby="error-due_at">
            @error('due_at') <span id="error-due_at" class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
    </div>
</div>
