<?php

namespace App\Mail;

use App\Mail\Concerns\AppendsBrandSubjectTag;
use App\Mail\Concerns\BuildsInvoiceMarkup;
use App\Mail\Concerns\EmbedsBrandLogo;
use App\Models\Customer;
use App\Models\Invoice;
use App\ValueObjects\ContactCollection;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * One reminder covering several of a customer's unpaid invoices. The table of
 * invoices and the totals are built here from the invoices actually sent, never
 * from the editable body. Queued by SendCustomerReminder.
 */
class CustomerReminderMail extends Mailable
{
    use AppendsBrandSubjectTag, BuildsInvoiceMarkup, EmbedsBrandLogo, Queueable, SerializesModels;

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @param  array<int, string>  $ccEmails
     */
    public function __construct(
        public Customer $customer,
        public Collection $invoices,
        public ContactCollection $recipients,
        public string $customSubject,
        public string $customBody,
        public array $ccEmails = [],
    ) {}

    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            to: $this->recipients->getEmails(),
            replyTo: $this->resolveReplyTo(),
            subject: $this->withBrandSubjectTag($this->customSubject),
            using: [fn (Email $message) => $this->attachBrandLogo($message)],
        );

        if (! empty($this->ccEmails)) {
            $envelope->cc($this->ccEmails);
        }

        return $envelope;
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->renderHtml());
    }

    public function renderHtml(): string
    {
        $organization = $this->customer->organization;
        $expiresAt = now()->addDays(config('app.share_link_days'));
        $invoices = $this->invoices->sortBy('due_at')->values();
        $urls = $invoices->map(fn (Invoice $invoice): string => $invoice->shareUrl($expiresAt));

        $html = view('emails.customer-reminder', [
            'customBody' => $this->customBody,
            'organization' => $organization,
            'location' => $organization?->primaryLocation,
            'expiresAt' => $expiresAt,
            'rows' => $invoices->map(fn (Invoice $invoice, int $index): array => [
                'number' => $invoice->invoice_number,
                'url' => $urls[$index],
                'due' => $invoice->due_at?->format('j M Y'),
                'days_overdue' => $invoice->daysOverdue(),
                'balance' => $invoice->formatted_remaining_balance,
            ]),
            'structuredData' => $invoices
                ->map(fn (Invoice $invoice, int $index): ?array => $this->invoiceMarkup($invoice, $urls[$index]))
                ->filter()
                ->values()
                ->all(),
            // Per currency: amounts in different currencies are never added up.
            'totals' => $invoices
                ->groupBy(fn (Invoice $invoice): string => $invoice->currency->value)
                ->map(fn (Collection $group, string $code): array => [
                    'currency' => $code,
                    'amount' => $group->first()->currency->formatAmount($group->sum('remaining_balance')),
                ])
                ->values(),
        ])->render();

        return (new CssToInlineStyles)->convert($html);
    }

    /**
     * @return array<int, Address>
     */
    private function resolveReplyTo(): array
    {
        $contact = $this->customer->organization?->emails?->first();

        if (empty($contact['email'])) {
            return [];
        }

        return [new Address($contact['email'], $contact['name'] ?? null)];
    }
}
