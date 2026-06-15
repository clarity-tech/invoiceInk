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
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class DocumentMailer extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{disk: string, path: string, name: string, mime: string}>  $extraAttachments
     */
    public function __construct(
        public Invoice $invoice,
        public ContactCollection $recipients,
        public ?string $customSubject = null,
        public array $ccEmails = [],
        public ?string $customBody = null,
        public bool $attachPdf = false,
        public array $extraAttachments = [],
        public bool $includeViewOnlineButton = true,
    ) {}

    public function envelope(): Envelope
    {
        $type = $this->invoice->isInvoice() ? 'Invoice' : 'Estimate';
        $subject = $this->customSubject ?? "{$type} #{$this->invoice->invoice_number}";

        $envelope = new Envelope(
            to: $this->recipients->getEmails(),
            replyTo: $this->resolveReplyTo(),
            subject: $subject,
        );

        if (! empty($this->ccEmails)) {
            $envelope->cc($this->ccEmails);
        }

        return $envelope;
    }

    /**
     * Set the organization's primary contact as the Reply-To address so that
     * customer replies reach the business rather than the SES "from" sender.
     *
     * @return array<int, Address>
     */
    private function resolveReplyTo(): array
    {
        $contact = $this->invoice->organization?->emails?->first();

        if (empty($contact['email'])) {
            return [];
        }

        return [new Address($contact['email'], $contact['name'] ?? null)];
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->renderHtml());
    }

    /**
     * Render the email template and inline its CSS.
     *
     * Email clients such as Gmail strip <style> blocks and <head> styles, so
     * the rendered HTML is passed through a CSS inliner that rewrites the
     * stylesheet rules as inline style="" attributes on each element.
     */
    public function renderHtml(): string
    {
        $body = $this->customBody ?? $this->resolveDefaultBody();

        $html = view('emails.custom-document', [
            'customBody' => $body,
            'invoice' => $this->invoice,
            'viewUrl' => $this->getPublicViewUrl(),
            'includeViewOnlineButton' => $this->includeViewOnlineButton,
        ])->render();

        return (new CssToInlineStyles)->convert($html);
    }

    public function attachments(): array
    {
        $attachments = [];

        if ($this->attachPdf) {
            $pdfService = app(PdfServiceInterface::class);
            $invoice = $this->invoice;

            $pdf = $invoice->isInvoice()
                ? $pdfService->generateInvoicePdf($invoice)
                : $pdfService->generateEstimatePdf($invoice);

            $attachments[] = Attachment::fromData(fn () => $pdf, "{$invoice->invoice_number}.pdf")
                ->withMime('application/pdf');
        }

        foreach ($this->extraAttachments as $file) {
            $attachments[] = Attachment::fromStorageDisk($file['disk'], $file['path'])
                ->as($file['name'])
                ->withMime($file['mime']);
        }

        return $attachments;
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
