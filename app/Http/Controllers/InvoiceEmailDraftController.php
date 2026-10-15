<?php

namespace App\Http\Controllers;

use App\Enums\EmailTemplateType;
use App\Models\Invoice;
use App\Services\EmailDraftWriter;
use App\Services\InvoiceDraftBuilder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Exceptions\AiException;

class InvoiceEmailDraftController extends Controller
{
    /**
     * Write the subject and body of an email about one invoice or estimate.
     * It is returned to the dialog for review; nothing is sent from here.
     */
    public function store(Request $request, Invoice $invoice, EmailDraftWriter $writer): JsonResponse
    {
        $this->authorize('send', $invoice);

        abort_unless(InvoiceDraftBuilder::isAvailableFor($invoice->organization), 403);

        $validated = $request->validate([
            'tone' => ['required', Rule::in(EmailDraftWriter::TONES)],
            'template_type' => ['nullable', 'string', 'max:40'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $purpose = EmailTemplateType::tryFrom($validated['template_type'] ?? '')
            ?? ($invoice->isInvoice() ? EmailTemplateType::InvoiceInitial : EmailTemplateType::EstimateInitial);

        try {
            return response()->json($writer->forInvoice($invoice, $purpose, $validated['tone'], $validated['note'] ?? null));
        } catch (AiException|ConnectionException|RequestException $exception) {
            report($exception);

            throw ValidationException::withMessages(['draft' => __('messages.email_draft.failed')]);
        }
    }
}
