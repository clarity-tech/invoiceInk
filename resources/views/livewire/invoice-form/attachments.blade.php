<!-- Customer Notes Section -->
<div class="bg-white shadow rounded-lg p-6">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('documents.headers.customer_notes') }}</label>
        <textarea wire:model="notes" rows="4"
                  placeholder="{{ __('forms.placeholders.enter_notes') }}"
                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                  aria-describedby="error-notes"></textarea>
        @error('notes') <span id="error-notes" class="text-red-600 text-sm">{{ $message }}</span> @enderror
        <p class="text-xs text-gray-500 mt-1">{{ __('forms.hints.displayed_on_pdf') }}</p>
    </div>
</div>

<!-- Attach File(s) to Invoice Section -->
<div class="bg-white shadow rounded-lg p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">{{ __('forms.labels.attach_files') }}</h2>

    <!-- File Upload Area -->
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('forms.labels.upload_files') }}</label>
        <div class="space-y-3">
            @foreach($uploadedFiles as $index => $file)
                <div class="flex items-center gap-3 p-2 bg-gray-50 border border-gray-200 rounded-md">
                    <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm text-gray-700 flex-1">{{ is_object($file) ? $file->getClientOriginalName() : __('forms.labels.file_queued') }}</span>
                    <button type="button" wire:click="removeUploadedFile({{ $index }})"
                            aria-label="{{ __('actions.buttons.remove') }} {{ is_object($file) ? $file->getClientOriginalName() : __('forms.labels.file_queued') }}"
                            class="text-red-500 hover:text-red-700">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            @endforeach

            <div class="flex items-center gap-3">
                <input type="file" wire:model="newFile" id="file-upload-{{ uniqid() }}"
                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-1">{{ __('forms.hints.upload_one_at_a_time') }}</p>
        @error('uploadedFiles.*') <span id="error-uploadedFiles" class="text-red-600 text-sm">{{ $message }}</span> @enderror
        @error('newFile') <span id="error-newFile" class="text-red-600 text-sm">{{ $message }}</span> @enderror
    </div>

    <!-- Existing Attachments List (Edit Mode) -->
    @if($mode === 'edit' && $invoice)
        @php
            $existingAttachments = $invoice->getMedia('attachments');
        @endphp

        @if($existingAttachments->count() > 0)
            <div class="border-t border-gray-200 pt-4">
                <h3 class="text-sm font-medium text-gray-700 mb-3">{{ __('documents.headers.existing_attachments') }}</h3>
                <div class="space-y-2">
                    @foreach($existingAttachments as $media)
                        <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-md hover:bg-gray-100 transition">
                            <div class="flex items-center gap-3">
                                <!-- File Icon -->
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $media->file_name }}</div>
                                    <div class="text-xs text-gray-500">{{ number_format($media->size / 1024, 2) }} KB</div>
                                </div>
                            </div>
                            <button type="button" wire:click="deleteAttachment({{ $media->id }})"
                                    wire:confirm="{{ __('actions.confirmations.confirm_delete_attachment') }}"
                                    aria-label="{{ __('actions.buttons.delete') }} {{ $media->file_name }}"
                                    class="text-red-500 hover:text-red-700 transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>
