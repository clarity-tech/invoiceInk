<!-- Email Modal -->
@if($showEmailModal)
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50"
         x-data="{ showRecipients: false }"
         @keydown.escape.window="$wire.closeEmailModal()">
        <div class="bg-white rounded-lg shadow-2xl max-w-5xl w-full max-h-[95vh] overflow-hidden flex flex-col"
             x-trap.noscroll="true"
             role="dialog"
             aria-modal="true"
             aria-labelledby="email-modal-title">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-white">
                <h2 id="email-modal-title" class="text-xl font-semibold text-gray-900">{{ __('documents.headers.email_to', ['customer' => $invoice?->customer?->name ?? __('forms.labels.customer')]) }}</h2>
                <button wire:click="closeEmailModal"
                        aria-label="{{ __('actions.buttons.close') }}"
                        class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body - Scrollable -->
            <div class="flex-1 overflow-y-auto px-6 py-4">
                <!-- From Field -->
                <div class="mb-4">
                    <div class="flex items-center">
                        <label class="text-sm font-medium text-gray-600 w-20">{{ __('forms.labels.from') }}</label>
                        <div class="flex-1 flex items-center">
                            <span class="text-sm text-gray-500">{{ __('forms.labels.accounts') }} &lt;{{ config('mail.from.address') }}&gt;</span>
                        </div>
                    </div>
                </div>

                <!-- Send To Field with Chips -->
                <div class="mb-4">
                    <div class="flex items-start">
                        <label class="text-sm font-medium text-gray-600 w-20 pt-2">{{ __('forms.labels.send_to') }}</label>
                        <div class="flex-1" x-data="{ toEmail: '' }">
                            <div class="border border-gray-300 rounded-md p-2 min-h-[44px] flex flex-wrap gap-2 items-center focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500">
                                @php
                                    $customer = $customer_id ? \App\Models\Customer::find($customer_id) : null;
                                    $customerEmails = $customer?->emails ?? collect();
                                @endphp

                                <!-- Selected Recipients as Chips -->
                                @foreach($selectedRecipients as $email)
                                    @php
                                        $contactInfo = $customerEmails->toArray();
                                        $contact = collect($contactInfo)->firstWhere('email', $email);
                                        $displayName = $contact['name'] ?? '';
                                        $initial = $displayName ? strtoupper(substr($displayName, 0, 1)) : strtoupper(substr($email, 0, 1));
                                        $colors = ['bg-purple-100 text-purple-700', 'bg-blue-100 text-blue-700', 'bg-green-100 text-green-700', 'bg-yellow-100 text-yellow-700'];
                                        $colorClass = $colors[array_rand($colors)];
                                    @endphp
                                    <div class="inline-flex items-center gap-1 bg-gray-100 rounded-md px-2 py-1">
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-medium {{ $colorClass }}">
                                            {{ $initial }}
                                        </span>
                                        <span class="text-sm text-gray-700">
                                            @if($displayName)
                                                {{ $displayName }} &lt;{{ $email }}&gt;
                                            @else
                                                {{ $email }}
                                            @endif
                                        </span>
                                        <button type="button" wire:click="removeRecipient('{{ $email }}')"
                                                aria-label="{{ __('actions.buttons.remove') }} {{ $email }}"
                                                class="text-gray-500 hover:text-gray-700">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach

                                <!-- Additional Recipients as Chips -->
                                @foreach($additionalRecipients as $index => $email)
                                    @if(!empty(trim($email)))
                                        <div class="inline-flex items-center gap-1 bg-gray-100 rounded-md px-2 py-1">
                                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-medium bg-gray-200 text-gray-700">
                                                {{ strtoupper(substr($email, 0, 1)) }}
                                            </span>
                                            <span class="text-sm text-gray-700">{{ $email }}</span>
                                            <button type="button" wire:click="removeAdditionalRecipient({{ $index }})"
                                                    aria-label="{{ __('actions.buttons.remove') }} {{ $email }}"
                                                    class="text-gray-500 hover:text-gray-700">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                @endforeach

                                <!-- Direct Input Field -->
                                <input type="email"
                                       x-model="toEmail"
                                       @keydown.enter.prevent="if(toEmail.trim()) { $wire.addDirectEmail(toEmail).then(() => { toEmail = '' }) }"
                                       placeholder="{{ __('forms.placeholders.type_email') }}"
                                       class="flex-1 min-w-[200px] border-none outline-none focus:ring-0 text-sm p-1">
                            </div>
                            @error('selectedRecipients') <span id="error-selectedRecipients" class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Cc Field with Chips -->
                <div class="mb-4">
                    <div class="flex items-start">
                        <label class="text-sm font-medium text-gray-600 w-20 pt-2">{{ __('forms.labels.cc') }}</label>
                        <div class="flex-1" x-data="{ ccEmail: '' }">
                            <div class="border border-gray-300 rounded-md p-2 min-h-[44px] flex flex-wrap gap-2 items-center focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500">
                                @php
                                    $organization = auth()->user()?->currentTeam;
                                    $organizationEmails = $organization?->emails ?? collect();
                                @endphp

                                <!-- Selected Cc Recipients as Chips -->
                                @foreach($selectedCcRecipients as $email)
                                    @php
                                        $contactInfo = $organizationEmails->toArray();
                                        $contact = collect($contactInfo)->firstWhere('email', $email);
                                        $displayName = $contact['name'] ?? '';
                                        $initial = $displayName ? strtoupper(substr($displayName, 0, 1)) : strtoupper(substr($email, 0, 1));
                                        $colors = ['bg-purple-100 text-purple-700', 'bg-blue-100 text-blue-700', 'bg-green-100 text-green-700', 'bg-orange-100 text-orange-700'];
                                        $colorClass = $colors[array_rand($colors)];
                                    @endphp
                                    <div class="inline-flex items-center gap-1 bg-gray-100 rounded-md px-2 py-1">
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-medium {{ $colorClass }}">
                                            {{ $initial }}
                                        </span>
                                        <span class="text-sm text-gray-700">
                                            @if($displayName)
                                                {{ $displayName }} &lt;{{ $email }}&gt;
                                            @else
                                                {{ $email }}
                                            @endif
                                        </span>
                                        <button type="button" wire:click="removeCcRecipient('{{ $email }}')"
                                                aria-label="{{ __('actions.buttons.remove') }} {{ $email }}"
                                                class="text-gray-500 hover:text-gray-700">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach

                                <!-- Additional Cc Recipients as Chips -->
                                @foreach($additionalCcRecipients as $index => $email)
                                    @if(!empty(trim($email)))
                                        <div class="inline-flex items-center gap-1 bg-gray-100 rounded-md px-2 py-1">
                                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-medium bg-gray-200 text-gray-700">
                                                {{ strtoupper(substr($email, 0, 1)) }}
                                            </span>
                                            <span class="text-sm text-gray-700">{{ $email }}</span>
                                            <button type="button" wire:click="removeAdditionalCcRecipient({{ $index }})"
                                                    aria-label="{{ __('actions.buttons.remove') }} {{ $email }}"
                                                    class="text-gray-500 hover:text-gray-700">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                @endforeach

                                <!-- Direct Input Field for Cc -->
                                <input type="email"
                                       x-model="ccEmail"
                                       @keydown.enter.prevent="if(ccEmail.trim()) { $wire.addDirectCcEmail(ccEmail).then(() => { ccEmail = '' }) }"
                                       placeholder="{{ __('forms.placeholders.type_email') }}"
                                       class="flex-1 min-w-[200px] border-none outline-none focus:ring-0 text-sm p-1">
                            </div>
                            @error('selectedCcRecipients') <span id="error-selectedCcRecipients" class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Subject Field -->
                <div class="mb-4">
                    <div class="flex items-center">
                        <label class="text-sm font-medium text-gray-600 w-20">{{ __('forms.labels.subject') }}</label>
                        <input wire:model="emailSubject" type="text"
                               class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                               aria-describedby="error-emailSubject">
                    </div>
                    @error('emailSubject') <span id="error-emailSubject" class="text-red-600 text-xs mt-1 ml-20">{{ $message }}</span> @enderror
                </div>

                <!-- Email Body with Trix Editor -->
                <div class="mb-4">
                    <div wire:ignore
                         x-data="{
                            initTrix() {
                                const editor = this.$refs.trixEditor;
                                const input = this.$refs.hiddenInput;
                                if (editor && input && input.value) {
                                    editor.editor.loadHTML(input.value);
                                }
                                editor.addEventListener('trix-change', (e) => {
                                    $wire.set('emailBody', e.target.value);
                                });
                            }
                         }"
                         x-init="setTimeout(() => initTrix(), 100)">
                        <input x-ref="hiddenInput" id="email-body-{{ $invoice?->id }}" type="hidden" value="{{ $emailBody }}">
                        <trix-editor x-ref="trixEditor" input="email-body-{{ $invoice?->id }}"
                                    aria-label="{{ __('forms.labels.email_body') }}"
                                    class="trix-content border border-gray-300 rounded-md min-h-[300px]"></trix-editor>
                    </div>
                    @error('emailBody') <span id="error-emailBody" class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Attachments Section -->
                <div class="mb-4">
                    <div class="border-t pt-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ __('documents.headers.attachments') }}</h3>

                        <!-- Invoice PDF Attachment -->
                        <label class="flex items-center cursor-pointer mb-3 p-3 hover:bg-gray-50 rounded-md transition">
                            <input type="checkbox" wire:model="attachPdf"
                                   class="h-4 w-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                            <svg class="w-8 h-8 text-red-500 mx-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                            </svg>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900">{{ $invoice?->invoice_number ?? 'Invoice' }}.pdf</div>
                                <div class="text-xs text-gray-500">{{ __('forms.hints.invoice_pdf_document') }}</div>
                            </div>
                        </label>

                        <!-- Invoice File Attachments -->
                        @php
                            $invoiceAttachments = $invoice?->getMedia('attachments') ?? collect();
                        @endphp

                        @if($invoiceAttachments->count() > 0)
                            <div class="space-y-2 mt-2">
                                @foreach($invoiceAttachments as $media)
                                    <label class="flex items-center cursor-pointer p-3 hover:bg-gray-50 rounded-md transition border border-gray-200">
                                        <input type="checkbox" wire:model="attachInvoiceFiles" value="{{ $media->id }}"
                                               class="h-4 w-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                                        <svg class="w-8 h-8 text-gray-400 mx-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900">{{ $media->file_name }}</div>
                                            <div class="text-xs text-gray-500">{{ number_format($media->size / 1024, 2) }} KB</div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center bg-gray-50">
                <div class="flex gap-3">
                    <button wire:click="closeEmailModal" type="button"
                            class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-100 font-medium transition">
                        {{ __('actions.buttons.cancel') }}
                    </button>
                    <button wire:click="sendEmail" type="button"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            wire:target="sendEmail"
                            class="px-6 py-2 bg-brand-600 text-white rounded-md hover:bg-brand-700 font-medium transition shadow-sm">
                        <span wire:loading.remove wire:target="sendEmail">{{ __('actions.buttons.send') }}</span>
                        <span wire:loading wire:target="sendEmail">{{ __('messages.system.sending') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
