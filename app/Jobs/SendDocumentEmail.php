<?php

namespace App\Jobs;

use App\Enums\InvoiceStatus;
use App\Mail\DocumentMailer;
use App\Models\EmailDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends a document email and records what actually happened.
 *
 * Queueing lives here rather than on the Mailable so there is a single place
 * that owns the outcome. DocumentMailer is a plain Mailable again, which also
 * leaves room for the per-organization mailer resolution that custom SMTP and
 * custom-domain sending will need — the transport is chosen at send time, on the
 * worker, not baked into the serialized payload.
 *
 * The invoice is promoted from DRAFT to SENT only once the mail is genuinely
 * away. Previously the controller did that the moment the mailable was queued,
 * so a failed send still left the invoice reading SENT.
 */
class SendDocumentEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public EmailDelivery $delivery,
        public DocumentMailer $mailable,
    ) {}

    public function handle(): void
    {
        $this->delivery->markSending($this->attempts());

        Mail::send($this->mailable);

        $this->delivery->markSent();

        $this->promoteInvoice();
    }

    /**
     * Called once the queue has exhausted `$tries`, so this is a real failure
     * rather than a transient one. Marking failed on every intermediate attempt
     * would flash spurious errors at the customer mid-retry.
     */
    public function failed(?Throwable $exception): void
    {
        $this->delivery->markFailed(
            $exception?->getMessage() ?? 'The email could not be sent.'
        );
    }

    /**
     * A draft becomes sent once the email is away. Any other status is left
     * alone — a paid or void document does not regress to sent on a resend.
     */
    private function promoteInvoice(): void
    {
        $invoice = $this->delivery->invoice;

        if ($invoice && $invoice->status === InvoiceStatus::DRAFT) {
            $invoice->status = InvoiceStatus::SENT;
            $invoice->save();
        }
    }
}
