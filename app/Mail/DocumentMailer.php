<?php

namespace App\Mail;

use App\Contracts\Services\PdfServiceInterface;
use App\Enums\EmailTemplateType;
use App\Mail\Templates\EmailTemplateService;
use App\Models\Invoice;
use App\ValueObjects\ContactCollection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
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
        public ?string $customBody = null,
        public bool $attachPdf = false,
    ) {}

    public function envelope(): Envelope
    {
        $type = $this->invoice->isInvoice() ? 'Invoice' : 'Estimate';
        $subject = $this->customSubject ?? "{$type} #{$this->invoice->invoice_number}";

        $envelope = new Envelope(
            to: $this->recipients->getEmails(),
            subject: $subject,
        );

        if (! empty($this->ccEmails)) {
            $envelope->cc($this->ccEmails);
        }

        return $envelope;
    }

    public function content(): Content
    {
        $body = $this->customBody ?? $this->resolveDefaultBody();

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
        if (! $this->attachPdf) {
            return [];
        }

        $pdfService = app(PdfServiceInterface::class);
        $invoice = $this->invoice;

        $pdf = $invoice->isInvoice()
            ? $pdfService->generateInvoicePdf($invoice)
            : $pdfService->generateEstimatePdf($invoice);

        return [
            Attachment::fromData(fn () => $pdf, "{$invoice->invoice_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }

    private function resolveDefaultBody(): string
    {
        $type = $this->invoice->isInvoice()
            ? EmailTemplateType::InvoiceInitial
            : EmailTemplateType::EstimateInitial;

        return app(EmailTemplateService::class)
            ->render($this->invoice, $type)['body'];
    }

    private function getPublicViewUrl(): string
    {
        $routeName = $this->invoice->isInvoice() ? 'invoices.public' : 'estimates.public';

        return route($routeName, $this->invoice->ulid);
    }
}
