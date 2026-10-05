<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesActingOrganization;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Description(
    'Retrieve one invoice or estimate in full: line items, tax breakdown, payment history, and '
    .'balance. Look it up by invoice number (e.g. "INV-26001") or by ULID.'
)]
#[Name('get-invoice')]
class GetInvoiceTool extends Tool
{
    use ResolvesActingOrganization;

    public function handle(Request $request): Response|ResponseFactory
    {
        $organization = $this->actingOrganization($request);

        if ($organization instanceof Response) {
            return $organization;
        }

        $validated = $request->validate([
            'identifier' => 'required|string|max:64',
        ], [
            'identifier.required' => 'Provide an invoice number (e.g. "INV-26001") or a ULID.',
        ]);

        $identifier = trim($validated['identifier']);

        $invoice = Invoice::query()
            ->forOrganization($organization->id)
            ->with(['customer', 'items', 'payments', 'organizationLocation', 'customerLocation'])
            ->where(fn ($query) => $query
                ->where('invoice_number', $identifier)
                ->orWhere('ulid', $identifier))
            ->first();

        if (! $invoice instanceof Invoice) {
            return Response::error(
                "No invoice or estimate matching \"{$identifier}\" exists in {$organization->name}. "
                .'It may belong to another organization, or the number may be mistyped — use '
                .'list-invoices to search.'
            );
        }

        return Response::structured([
            'invoice_number' => $invoice->invoice_number,
            'ulid' => $invoice->ulid,
            'type' => $invoice->type,
            'status' => $invoice->status?->value,
            'subject' => $invoice->subject,
            'order_number' => $invoice->order_number,
            'customer' => [
                'name' => $invoice->customer?->name,
                'emails' => $invoice->customer?->emails?->toArray(),
                'gst_treatment' => $invoice->customer?->gst_treatment?->value,
            ],
            'currency' => $invoice->currency?->value,
            'issued_at' => $invoice->issued_at?->toDateString(),
            'due_at' => $invoice->due_at?->toDateString(),
            'payment_terms' => $invoice->paymentTermsLabel(),
            'place_of_supply' => $invoice->placeOfSupplyLabel(),
            'totals' => [
                'subtotal_cents' => $invoice->subtotal,
                'subtotal' => $invoice->formatted_subtotal,
                'tax_cents' => $invoice->tax,
                'tax' => $invoice->formatted_tax,
                'total_cents' => $invoice->total,
                'total' => $invoice->formatted_total,
                'amount_paid_cents' => $invoice->amount_paid,
                'amount_paid' => $invoice->formatted_amount_paid,
                'balance_cents' => $invoice->remaining_balance,
                'balance' => $invoice->formatted_remaining_balance,
                'tds' => $invoice->hasTds() ? [
                    'label' => $invoice->tdsLabel(),
                    'rate_basis_points' => $invoice->tds_rate,
                    'amount_cents' => $invoice->tds_amount,
                ] : null,
                'tcs' => $invoice->hasTcs() ? [
                    'label' => $invoice->tcsLabel(),
                    'rate_basis_points' => $invoice->tcs_rate,
                    'amount_cents' => $invoice->tcs_amount,
                ] : null,
            ],
            'tax_breakdown' => $invoice->tax_breakdown,
            // Items and payments carry no organization scope of their own, so
            // they are reached only through this already-scoped invoice.
            'items' => $invoice->items->map(fn (InvoiceItem $item): array => [
                'description' => $item->description,
                'sac_code' => $item->sac_code,
                'quantity' => $item->quantity,
                'unit_price_cents' => $item->unit_price,
                'unit_price' => $item->formatted_unit_price,
                'tax_rate_basis_points' => $item->tax_rate,
                'line_total_cents' => $item->getLineTotal(),
                'line_total' => $item->formatted_line_total,
                'tax_amount_cents' => $item->getTaxAmount(),
            ])->all(),
            'payments' => $invoice->payments->map(fn (Payment $payment): array => [
                'amount_cents' => $payment->amount,
                'amount' => $payment->formatted_amount,
                'payment_date' => $payment->payment_date?->toDateString(),
                'payment_method' => $payment->payment_method,
                'reference' => $payment->reference,
            ])->all(),
            'notes' => $invoice->notes,
            'terms' => $invoice->terms,
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'identifier' => $schema->string()
                ->description('The invoice number (e.g. "INV-26001") or the document ULID.')
                ->required(),
        ];
    }
}
