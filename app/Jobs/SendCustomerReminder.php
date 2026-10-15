<?php

namespace App\Jobs;

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
        $this->deliveries->each->markSending($this->attempts());

        Mail::send($this->mailable);

        $this->deliveries->each->markSent();
    }

    public function failed(?Throwable $exception): void
    {
        $error = $exception?->getMessage() ?? 'The email could not be sent.';

        $this->deliveries->each->markFailed($error);
    }
}
