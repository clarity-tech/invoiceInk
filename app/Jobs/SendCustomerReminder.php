<?php

namespace App\Jobs;

use App\Enums\EmailDeliveryStatus;
use App\Mail\CustomerReminderMail;
use App\Models\EmailDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends one reminder email and records the outcome on the delivery row of every
 * invoice it covered, as SendDocumentEmail does for a single document.
 */
class SendCustomerReminder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @param  Collection<int, EmailDelivery>  $deliveries
     */
    public function __construct(
        public Collection $deliveries,
        public CustomerReminderMail $mailable,
    ) {}

    public function handle(): void
    {
        if ($this->alreadySent()) {
            return;
        }

        $this->deliveries->each->markSending($this->attempts());

        Mail::send($this->mailable);

        $this->deliveries->each->markSent();
    }

    /**
     * Whether an earlier run already sent this reminder; see
     * SendDocumentEmail::alreadySent() for why a second run happens at all.
     *
     * One email covers every delivery here, and the rows are marked sent only
     * after it has gone, so a single SENT row proves the email is away. Waiting
     * for all of them would send it again after a run that died part-way
     * through marking them. The rows such a run never reached stay SENDING,
     * which is the lesser harm than a second email to the customer.
     */
    private function alreadySent(): bool
    {
        $this->deliveries->each->refresh();

        return $this->deliveries->contains(
            fn (EmailDelivery $delivery): bool => $delivery->status === EmailDeliveryStatus::SENT,
        );
    }

    public function failed(?Throwable $exception): void
    {
        $error = $exception?->getMessage() ?? 'The email could not be sent.';

        $this->deliveries->each->markFailed($error);
    }
}
