<?php

namespace App\Mail;

use App\Enums\EmailTemplateType;
use App\Mail\Templates\EmailTemplateService;
use App\Models\Invoice;
use App\ValueObjects\ContactCollection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentMailer extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public ContactCollection $recipients,
        public ?string $customSubject = null,
        public array $ccEmails = [],
        public ?string $customBody = null
    ) {}

    public function envelope(): Envelope
    {
        $type = $this->invoice->isInvoice() ? 'Invoice' : 'Estimate';
        $subject = $this->customSubject ?? "{$type} #{$this->invoice->invoice_number}";

        $envelope = new Envelope(
            to: $this->recipients->getEmails(),
            subject: $subject,
        );

        // Add Cc if provided
        if (! empty($this->ccEmails)) {
            $envelope->cc($this->ccEmails);
        }

        return $envelope;
    }

    public function content(): Content
    {
        // Render via custom-document template (handles both custom and default bodies).
        // If no custom body provided, resolve the default template with variables.
        $body = $this->customBody;

        if (! $body) {
            $type = $this->invoice->isInvoice()
                ? EmailTemplateType::InvoiceInitial
                : EmailTemplateType::EstimateInitial;

            $rendered = app(EmailTemplateService::class)
                ->render($this->invoice, $type);

            $body = $rendered['body'];
        }

        return new Content(
            view: 'emails.custom-document',
            with: [
                'customBody' => $body,
                'invoice' => $this->invoice,
                'viewUrl' => $this->getPublicViewUrl(),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function getPublicViewUrl(): string
    {
        $type = $this->invoice->isInvoice() ? 'invoices' : 'estimates';

        return url("/{$type}/view/{$this->invoice->ulid}");
    }
}
