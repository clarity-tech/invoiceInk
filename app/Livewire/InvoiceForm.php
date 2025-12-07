<?php

namespace App\Livewire;

use App\Livewire\Traits\InvoiceFormLogic;
use App\Mail\DocumentMailer;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\PdfService;
use App\ValueObjects\ContactCollection;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithFileUploads;

class InvoiceForm extends Component
{
    use InvoiceFormLogic, WithFileUploads;

    public string $mode = 'create'; // 'create' or 'edit'

    public ?Invoice $invoice = null;

    // File upload properties
    public array $uploadedFiles = [];

    public $newFile = null;

    // Email-related properties
    public bool $showEmailModal = false;

    public array $selectedRecipients = [];

    public array $additionalRecipients = [];

    public bool $selectAllRecipients = false;

    public array $selectedCcRecipients = [];

    public array $additionalCcRecipients = [];

    public string $emailSubject = '';

    public string $emailBody = '';

    public bool $attachPdf = true;

    public array $attachInvoiceFiles = [];

    public function mount(?Invoice $invoice, string $type = 'invoice'): void
    {
        try {
            // Determine mode and type from route - check for actual persisted invoice
            $this->mode = ($invoice && $invoice->exists) ? 'edit' : 'create';
            $this->type = $type;
            $this->invoice = $invoice;

            // Set document type from route parameter for estimates
            if (request()->routeIs('estimates.create')) {
                $this->type = 'estimate';
            }

            // Initialize form defaults
            $this->initializeFormDefaults();

            // Load existing invoice data if editing
            if ($this->mode === 'edit' && $invoice) {
                $this->loadExistingInvoice($invoice);
            }
        } catch (\Exception $e) {
            // Set minimal defaults so component doesn't crash
            $this->mode = $invoice ? 'edit' : 'create';
            $this->type = $type ?: 'invoice';
            $this->currentStep = 1;
            $this->items = [['description' => '', 'quantity' => 1, 'unit_price' => 0, 'tax_rate' => 0]];
            $this->issued_at = now()->format('Y-m-d');
            $this->due_at = now()->addDays(30)->format('Y-m-d');
        }
    }

    public function save()
    {
        // Only pass existing invoice if we're in edit mode with a persisted invoice
        $existingInvoice = ($this->mode === 'edit' && $this->invoice && $this->invoice->exists) ? $this->invoice : null;
        $invoice = $this->saveInvoice($existingInvoice);

        return redirect()->route('invoices.edit', $invoice->id);
    }

    public function cancel()
    {
        return redirect()->route('invoices.index');
    }

    public function downloadPdf()
    {
        if ($this->mode !== 'edit' || ! $this->invoice) {
            return null;
        }

        // Security check: Ensure user has access to this invoice's organization
        if (! auth()->user()->allTeams()->contains('id', $this->invoice->organization_id)) {
            abort(403, 'Unauthorized access to invoice.');
        }

        // Redirect to the PDF download route
        $routeName = $this->invoice->type === 'invoice' ? 'invoices.pdf' : 'estimates.pdf';

        return redirect()->route($routeName, ['ulid' => $this->invoice->ulid]);
    }

    public function updatedNewFile(): void
    {
        // When a new file is uploaded, add it to the uploadedFiles array
        if ($this->newFile) {
            $this->uploadedFiles[] = $this->newFile;
            $this->newFile = null; // Reset for next upload
        }
    }

    public function removeUploadedFile(int $index): void
    {
        unset($this->uploadedFiles[$index]);
        $this->uploadedFiles = array_values($this->uploadedFiles);
    }

    public function deleteAttachment(int $mediaId): void
    {
        if (! $this->invoice) {
            return;
        }

        // Security check
        if (! auth()->user()->allTeams()->contains('id', $this->invoice->organization_id)) {
            abort(403, 'Unauthorized access to invoice.');
        }

        $media = $this->invoice->getMedia('attachments')->where('id', $mediaId)->first();

        if ($media) {
            $media->delete();
            session()->flash('message', 'Attachment deleted successfully.');
        }
    }

    public function getPageTitleProperty(): string
    {
        if ($this->mode === 'edit') {
            return 'Edit '.ucfirst($this->type);
        }

        return 'Create '.ucfirst($this->type);
    }

    // Email functionality
    public function openEmailModal(): void
    {
        if ($this->mode !== 'edit' || ! $this->invoice) {
            return;
        }

        // Initialize email fields
        $organizationName = auth()->user()?->currentTeam?->company_name ?? auth()->user()?->currentTeam?->name ?? 'Your Company';
        $documentType = ucfirst($this->invoice->type);
        $this->emailSubject = "{$documentType} - {$this->invoice->invoice_number} from {$organizationName}";

        // Generate default email body
        $this->emailBody = $this->generateDefaultEmailBody();

        // Auto-select all customer emails by default for To field
        if ($this->customer_id) {
            $customer = Customer::find($this->customer_id);
            if ($customer && $customer->emails) {
                $this->selectedRecipients = $customer->emails->getEmails();
            }
        }

        // Auto-select all organization emails for Cc field
        if ($this->organization_id) {
            $organization = auth()->user()?->currentTeam;
            if ($organization && $organization->emails) {
                $this->selectedCcRecipients = $organization->emails->getEmails();
            }
        }

        // Auto-select all invoice attachments for email
        $invoiceAttachments = $this->invoice->getMedia('attachments');
        $this->attachInvoiceFiles = $invoiceAttachments->pluck('id')->toArray();

        $this->showEmailModal = true;
    }

    public function closeEmailModal(): void
    {
        $this->showEmailModal = false;
        $this->selectedRecipients = [];
        $this->additionalRecipients = [];
        $this->selectAllRecipients = false;
        $this->selectedCcRecipients = [];
        $this->additionalCcRecipients = [];
        $this->emailSubject = '';
        $this->emailBody = '';
        $this->attachPdf = true;
        $this->attachInvoiceFiles = [];
        $this->resetValidation();
    }

    public function toggleSelectAll(): void
    {
        if (! $this->customer_id) {
            return;
        }

        $customer = Customer::find($this->customer_id);
        if (! $customer || ! $customer->emails) {
            return;
        }

        // If all are currently selected, unselect all. Otherwise, select all
        if (count($this->selectedRecipients) === $customer->emails->count()) {
            $this->selectedRecipients = [];
        } else {
            $this->selectedRecipients = $customer->emails->getEmails();
        }
    }

    public function addNewEmailRecipient(): void
    {
        $this->additionalRecipients[] = '';
    }

    public function removeAdditionalRecipient(int $index): void
    {
        unset($this->additionalRecipients[$index]);
        $this->additionalRecipients = array_values($this->additionalRecipients);
    }

    public function removeRecipient(string $email): void
    {
        $this->selectedRecipients = array_values(
            array_filter($this->selectedRecipients, fn ($recipient) => $recipient !== $email)
        );
    }

    public function addEmailOnEnter(int $index, string $email): void
    {
        // Trim and validate
        $email = trim($email);

        if (empty($email)) {
            return;
        }

        // Validate email format
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError("additionalRecipients.{$index}", 'Please enter a valid email address.');

            return;
        }

        // Clear any previous errors
        $this->resetErrorBag("additionalRecipients.{$index}");

        // Set the email at this index
        $this->additionalRecipients[$index] = $email;

        // The email will now show as a chip since it's not empty
        // The Alpine.js component will clear the input field
    }

    public function addNewCcRecipient(): void
    {
        $this->additionalCcRecipients[] = '';
    }

    public function removeAdditionalCcRecipient(int $index): void
    {
        unset($this->additionalCcRecipients[$index]);
        $this->additionalCcRecipients = array_values($this->additionalCcRecipients);
    }

    public function removeCcRecipient(string $email): void
    {
        $this->selectedCcRecipients = array_values(
            array_filter($this->selectedCcRecipients, fn ($recipient) => $recipient !== $email)
        );
    }

    public function addCcEmailOnEnter(int $index, string $email): void
    {
        // Trim and validate
        $email = trim($email);

        if (empty($email)) {
            return;
        }

        // Validate email format
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError("additionalCcRecipients.{$index}", 'Please enter a valid email address.');

            return;
        }

        // Clear any previous errors
        $this->resetErrorBag("additionalCcRecipients.{$index}");

        // Set the email at this index
        $this->additionalCcRecipients[$index] = $email;
    }

    public function toggleSelectAllCc(): void
    {
        if (! $this->organization_id) {
            return;
        }

        $organization = auth()->user()?->currentTeam;
        if (! $organization || ! $organization->emails) {
            return;
        }

        // If all are currently selected, unselect all. Otherwise, select all
        if (count($this->selectedCcRecipients) === $organization->emails->count()) {
            $this->selectedCcRecipients = [];
        } else {
            $this->selectedCcRecipients = $organization->emails->getEmails();
        }
    }

    public function addDirectEmail(string $email): void
    {
        $email = trim($email);

        if (empty($email)) {
            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('directEmail', 'Please enter a valid email address.');

            return;
        }

        // Add to additionalRecipients array
        $this->additionalRecipients[] = $email;
        $this->resetErrorBag('directEmail');
    }

    public function addDirectCcEmail(string $email): void
    {
        $email = trim($email);

        if (empty($email)) {
            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('directCcEmail', 'Please enter a valid email address.');

            return;
        }

        // Add to additionalCcRecipients array
        $this->additionalCcRecipients[] = $email;
        $this->resetErrorBag('directCcEmail');
    }

    private function generateDefaultEmailBody(): string
    {
        if (! $this->invoice) {
            return '';
        }

        try {
            $documentType = $this->invoice->type;
            $documentNumber = $this->invoice->invoice_number;
            $customerName = $this->invoice->customer?->display_name ?? 'Customer';
            $organizationName = strtoupper($this->invoice->organization->company_name ?? $this->invoice->organization->name);

            // Format amount safely
            $currencyCode = $this->invoice->currency instanceof \App\Currency ? $this->invoice->currency->value : $this->invoice->currency;
            $formattedAmount = money($this->invoice->total, $currencyCode);

            // Create email body - use only tags Trix preserves
            $html = '<div><strong>'.$organizationName.'</strong></div>';
            $html .= '<div><br></div>';
            $html .= '<div><strong>'.ucfirst($documentType).' #'.$documentNumber.'</strong></div>';
            $html .= '<div><br></div>';
            $html .= '<div>Dear '.$customerName.',</div>';
            $html .= '<div><br></div>';
            $html .= '<div>Thank you for your business. Your '.strtolower($documentType).' can be viewed, printed and downloaded as PDF from the link below. You can also choose to pay it online.</div>';
            $html .= '<div><br></div>';
            $html .= '<blockquote><div><strong>'.strtoupper($documentType).' AMOUNT</strong><strong><em>'.$formattedAmount.'</em></strong></div></blockquote>';

            return $html;
        } catch (\Exception $e) {
            \Log::error('Error generating email body: '.$e->getMessage());

            return '<div>Error generating email preview. Please contact support.</div>';
        }
    }

    public function sendEmail(): void
    {
        // Validate additional recipients
        $validationRules = [
            'emailSubject' => 'required|string|max:255',
            'emailBody' => 'required|string',
            'additionalRecipients.*' => 'nullable|email',
            'additionalCcRecipients.*' => 'nullable|email',
        ];

        $this->validate($validationRules);

        if (! $this->invoice) {
            $this->addError('email', 'Invoice not found. Please refresh the page and try again.');

            return;
        }

        // Security check
        if (! auth()->user()->allTeams()->contains('id', $this->invoice->organization_id)) {
            abort(403, 'Unauthorized access to invoice.');
        }

        // Merge selected recipients with additional recipients
        $allRecipients = array_merge(
            $this->selectedRecipients,
            array_filter($this->additionalRecipients, fn ($email) => ! empty(trim($email)))
        );

        // Ensure we have at least one recipient
        if (empty($allRecipients)) {
            $this->addError('selectedRecipients', 'Please select at least one recipient or add a new email address.');

            return;
        }

        // Merge selected Cc recipients with additional Cc recipients
        $ccEmails = array_merge(
            $this->selectedCcRecipients,
            array_filter($this->additionalCcRecipients, fn ($email) => ! empty(trim($email)))
        );

        // Trim all Cc emails
        $ccEmails = array_map('trim', $ccEmails);

        // Create contact collection from all recipients
        $contacts = collect($allRecipients)->map(function ($email) {
            return ['name' => '', 'email' => trim($email)];
        })->toArray();

        $contactCollection = new ContactCollection($contacts);

        // Send email
        try {
            $mailable = new DocumentMailer(
                $this->invoice,
                $contactCollection,
                $this->emailSubject,
                $ccEmails,
                $this->emailBody
            );

            // Attach PDF if requested
            if ($this->attachPdf) {
                $pdfService = new PdfService;
                $pdfContent = $this->invoice->type === 'invoice'
                    ? $pdfService->generateInvoicePdf($this->invoice)
                    : $pdfService->generateEstimatePdf($this->invoice);

                // Save PDF to temporary file
                $tempPath = storage_path('app/temp');
                if (! file_exists($tempPath)) {
                    mkdir($tempPath, 0755, true);
                }

                $pdfFilePath = $tempPath.'/'.$this->invoice->invoice_number.'-'.time().'.pdf';
                file_put_contents($pdfFilePath, $pdfContent);

                $mailable->attach($pdfFilePath, [
                    'as' => "{$this->invoice->invoice_number}.pdf",
                    'mime' => 'application/pdf',
                ]);
            }

            // Attach selected invoice files
            if (! empty($this->attachInvoiceFiles)) {
                $selectedMedia = $this->invoice->getMedia('attachments')->whereIn('id', $this->attachInvoiceFiles);
                foreach ($selectedMedia as $media) {
                    $mailable->attach($media->getPath(), [
                        'as' => $media->file_name,
                        'mime' => $media->mime_type,
                    ]);
                }
            }

            Mail::send($mailable);

            session()->flash('message', ucfirst($this->invoice->type).' sent successfully!');
            $this->closeEmailModal();
        } catch (\Exception $e) {
            $this->addError('email', 'Failed to send email: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.invoice-form')
            ->layout('layouts.app', ['title' => $this->getPageTitleProperty()]);
    }
}
