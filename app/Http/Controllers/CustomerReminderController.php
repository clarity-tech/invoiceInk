<?php

namespace App\Http\Controllers;

use App\Enums\EmailDeliveryStatus;
use App\Enums\EmailTemplateType;
use App\Jobs\SendCustomerReminder;
use App\Mail\CustomerReminderMail;
use App\Models\Customer;
use App\Models\EmailDelivery;
use App\Models\Invoice;
use App\Services\CustomerReminders;
use App\ValueObjects\ContactCollection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerReminderController extends Controller
{
    /**
     * Send one reminder covering the selected unpaid invoices of a customer.
     */
    public function store(Request $request, Customer $customer, CustomerReminders $reminders): RedirectResponse
    {
        $this->authorize('view', $customer);

        $validated = $request->validate([
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*' => ['required', 'email'],
            'cc' => ['nullable', 'array'],
            'cc.*' => ['nullable', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'invoice_ids' => ['required', 'array', 'min:1'],
            'invoice_ids.*' => ['integer', 'distinct'],
        ]);

        $invoices = $reminders->outstandingInvoices($customer)
            ->whereIn('id', $validated['invoice_ids'])
            ->values();

        if ($invoices->count() !== count($validated['invoice_ids'])) {
            throw ValidationException::withMessages([
                'invoice_ids' => 'Some of the selected invoices are no longer owed. Reload the page and try again.',
            ]);
        }

        $invoices->each(fn (Invoice $invoice) => $this->authorize('send', $invoice));

        $recipients = new ContactCollection(collect($validated['recipients'])
            ->map(fn (string $email): array => ['name' => '', 'email' => trim($email)])
            ->all());

        // Filled in here, from the invoices actually sent, like the table.
        $numbers = $invoices->sortBy('due_at')->pluck('invoice_number')->implode(', ');

        $mailable = new CustomerReminderMail(
            $customer,
            $invoices,
            $recipients,
            str_replace('{{invoice_numbers}}', $numbers, $validated['subject']),
            str_replace('{{invoice_numbers}}', e($numbers), $validated['body']),
            array_map('trim', $validated['cc'] ?? []),
        );

        $batchId = (string) Str::ulid();

        $deliveries = DB::transaction(fn (): Collection => new Collection($invoices->map(
            fn (Invoice $invoice): EmailDelivery => EmailDelivery::create([
                'organization_id' => $customer->organization_id,
                'invoice_id' => $invoice->id,
                'batch_id' => $batchId,
                'mailable_type' => CustomerReminderMail::class,
                'mode' => 'platform',
                'template_type' => EmailTemplateType::CustomerReminder,
                'status' => EmailDeliveryStatus::QUEUED,
                'recipients' => $recipients->getEmails(),
                'queued_at' => now(),
            ]),
        )->all()));

        SendCustomerReminder::dispatch($deliveries, $mailable);

        return back()->with('success', 'Reminder queued for sending.');
    }
}
