<?php

namespace App\Services;

use App\Ai\Agents\EmailWriter;
use App\Enums\EmailDeliveryStatus;
use App\Enums\EmailTemplateType;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Ai\Enums\Lab;

/**
 * Drafts an email with AI from the facts of what it is about. The AI returns
 * plain text; the HTML is built here, so nothing it writes is markup.
 */
class EmailDraftWriter
{
    public const TONES = ['friendly', 'formal', 'firm'];

    /**
     * @return array{subject: string, body: string}
     */
    public function forInvoice(Invoice $invoice, EmailTemplateType $purpose, string $tone, ?string $note): array
    {
        $invoice->loadMissing(['customer', 'organization']);

        $document = $invoice->typeLabel();
        $isInvoice = $invoice->isInvoice();
        $earlier = $invoice->emailDeliveries()->where('status', EmailDeliveryStatus::SENT)->get(['sent_at']);

        $facts = array_filter([
            "Purpose: {$purpose->description()}.",
            'From: '.($invoice->organization?->company_name ?? $invoice->organization?->name),
            'To: '.($invoice->customer?->name ?? 'the customer'),
            "{$document} number: {$invoice->invoice_number}",
            $invoice->subject ? "Work: {$invoice->subject}" : null,
            'Total: '.$invoice->formatted_total,
            $isInvoice ? 'Paid so far: '.$invoice->formatted_amount_paid : null,
            $isInvoice ? 'Balance due: '.$invoice->formatted_remaining_balance : null,
            $invoice->issued_at ? 'Issued: '.$invoice->issued_at->format('j M Y') : null,
            $invoice->due_at ? ($isInvoice ? 'Due: ' : 'Valid until: ').$invoice->due_at->format('j M Y') : null,
            $invoice->daysOverdue() ? "Overdue by {$invoice->daysOverdue()} days" : null,
            $earlier->isNotEmpty()
                ? "Emails already sent about this {$document}: {$earlier->count()}, the last on ".$earlier->max('sent_at')->format('j M Y')
                : null,
        ]);

        return $this->write($facts, $tone, $note);
    }

    /**
     * One reminder about several of a customer's unpaid invoices. The email
     * lists them in a table below the message, so the facts are context.
     *
     * @param  Collection<int, Invoice>  $invoices
     * @return array{subject: string, body: string}
     */
    public function forCustomerReminder(Customer $customer, Collection $invoices, string $tone, ?string $note): array
    {
        $customer->loadMissing('organization');

        $lines = $invoices->sortBy('due_at')->map(fn (Invoice $invoice): string => "{$invoice->invoice_number}: balance {$invoice->formatted_remaining_balance}"
            .($invoice->due_at ? ', due '.$invoice->due_at->format('j M Y') : '')
            .($invoice->daysOverdue() ? ", overdue by {$invoice->daysOverdue()} days" : ''));

        $totals = $invoices
            ->groupBy(fn (Invoice $invoice): string => $invoice->currency->value)
            ->map(fn (Collection $group): string => $group->first()->currency->formatAmount($group->sum('remaining_balance')));

        $facts = [
            'Purpose: one reminder covering several unpaid invoices. A table of them, with links, is added below the message, so do not list each one.',
            'From: '.($customer->organization?->company_name ?? $customer->organization?->name),
            "To: {$customer->name}",
            'Invoices: '.$lines->implode('; '),
            'Total due: '.$totals->implode(' and '),
        ];

        return $this->write($facts, $tone, $note);
    }

    /**
     * @param  array<int, string>  $facts
     * @return array{subject: string, body: string}
     */
    private function write(array $facts, string $tone, ?string $note): array
    {
        $prompt = "Facts:\n- ".implode("\n- ", $facts)."\n\nTone: {$tone}";

        if (filled($note)) {
            $prompt .= "\nThe sender's note: {$note}";
        }

        $response = (new EmailWriter)->prompt(
            $prompt,
            provider: Lab::Anthropic,
            model: config('services.email_drafts.model'),
            timeout: config('services.email_drafts.timeout'),
        );

        $paragraphs = collect((array) ($response['paragraphs'] ?? []))
            ->map(fn (mixed $paragraph): string => trim((string) $paragraph))
            ->filter();

        return [
            'subject' => Str::limit(trim((string) ($response['subject'] ?? '')), 255, ''),
            'body' => $paragraphs->map(fn (string $paragraph): string => '<p>'.nl2br(e($paragraph), false).'</p>')->implode(''),
        ];
    }
}
