<?php

namespace App\Mail;

use App\Models\Invoice;
use App\ValueObjects\EmailCollection;
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
        public EmailCollection $recipients
    ) {}

    public function envelope(): Envelope
    {
        $type = $this->invoice->isInvoice() ? 'Invoice' : 'Estimate';
        
        return new Envelope(
            to: $this->recipients->toArray(),
            subject: "{$type} #{$this->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        $view = $this->invoice->isInvoice() ? 'emails.invoice' : 'emails.estimate';
        
        return new Content(
            view: $view,
            with: [
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
        return url("/{$type}/{$this->invoice->ulid}");
    }
}
