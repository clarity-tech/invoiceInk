<?php

namespace App\Services;

use App\Enums\EmailTemplateType;
use App\Mail\Templates\EmailTemplateService;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;

class CustomerReminders
{
    public function __construct(private readonly EmailTemplateService $templates) {}

    /**
     * @return Collection<int, Invoice>
     */
    public function outstandingInvoices(Customer $customer): Collection
    {
        return Invoice::query()
            ->where('organization_id', $customer->organization_id)
            ->where('customer_id', $customer->id)
            ->remindable()
            ->orderBy('due_at')
            ->get();
    }

    /**
     * What the combined reminder dialog opens with, or null when fewer than
     * $minimum invoices are owed.
     *
     * @return array{invoices: array<int, array<string, mixed>>, subject: string, body: string}|null
     */
    public function draft(Customer $customer, int $minimum = 1): ?array
    {
        $invoices = $this->outstandingInvoices($customer);

        if ($invoices->count() < $minimum) {
            return null;
        }

        $template = $this->templates->renderForCustomer($customer, EmailTemplateType::CustomerReminder);

        return [
            'invoices' => $invoices->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'currency' => $invoice->currency->value,
                'remaining_balance' => $invoice->remaining_balance,
                'due_at' => $invoice->due_at,
                'days_overdue' => $invoice->daysOverdue(),
            ])->all(),
            'subject' => $template['subject'],
            'body' => $template['body'],
        ];
    }
}
