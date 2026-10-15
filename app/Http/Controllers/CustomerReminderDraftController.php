<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\CustomerReminders;
use App\Services\EmailDraftWriter;
use App\Services\InvoiceDraftBuilder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Exceptions\AiException;

class CustomerReminderDraftController extends Controller
{
    /**
     * Write a combined reminder for the selected unpaid invoices. It is
     * returned to the dialog for review; nothing is sent from here.
     */
    public function store(Request $request, Customer $customer, CustomerReminders $reminders, EmailDraftWriter $writer): JsonResponse
    {
        $this->authorize('view', $customer);

        abort_unless(InvoiceDraftBuilder::isAvailableFor($customer->organization), 403);

        $validated = $request->validate([
            'tone' => ['required', Rule::in(EmailDraftWriter::TONES)],
            'note' => ['nullable', 'string', 'max:500'],
            'invoice_ids' => ['required', 'array', 'min:1'],
            'invoice_ids.*' => ['integer', 'distinct'],
        ]);

        $invoices = $reminders->outstandingInvoices($customer)->whereIn('id', $validated['invoice_ids'])->values();

        if ($invoices->count() !== count($validated['invoice_ids'])) {
            throw ValidationException::withMessages([
                'invoice_ids' => 'Some of the selected invoices are no longer owed. Reload the page and try again.',
            ]);
        }

        $invoices->each(fn (Invoice $invoice) => $this->authorize('send', $invoice));

        try {
            return response()->json($writer->forCustomerReminder($customer, $invoices, $validated['tone'], $validated['note'] ?? null));
        } catch (AiException|ConnectionException|RequestException $exception) {
            report($exception);

            throw ValidationException::withMessages(['draft' => __('messages.email_draft.failed')]);
        }
    }
}
