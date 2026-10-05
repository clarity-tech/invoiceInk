<?php

namespace App\Mcp\Tools;

use App\Enums\InvoiceStatus;
use App\Mcp\Concerns\ResolvesActingOrganization;
use App\Models\Invoice;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Description(
    'Search invoices and estimates for the current organization. Filter by type, status, customer '
    .'name, issue-date range, or overdue-only, and sort by date or amount. Returns a summary row per '
    .'document; use get-invoice for line items and payment history.'
)]
#[Name('list-invoices')]
class ListInvoicesTool extends Tool
{
    use ResolvesActingOrganization;

    public function handle(Request $request): Response|ResponseFactory
    {
        $organization = $this->actingOrganization($request);

        if ($organization instanceof Response) {
            return $organization;
        }

        $validated = $request->validate([
            'type' => 'in:invoice,estimate,all',
            'status' => 'in:draft,sent,accepted,partially_paid,paid,void',
            'customer' => 'string|max:255',
            'overdue' => 'boolean',
            'issued_from' => 'date',
            'issued_to' => 'date|after_or_equal:issued_from',
            'sort' => 'in:newest,oldest,largest,smallest',
            'limit' => 'integer|min:1|max:100',
        ], [
            'type.in' => 'type must be one of "invoice", "estimate", or "all".',
            'status.in' => 'status must be one of: draft, sent, accepted, partially_paid, paid, void.',
            'issued_to.after_or_equal' => 'issued_to must fall on or after issued_from.',
            'limit.max' => 'limit cannot exceed 100. Narrow the filters instead of asking for more rows.',
        ]);

        $type = $validated['type'] ?? 'invoice';
        $limit = (int) ($validated['limit'] ?? 25);

        // Scope explicitly rather than trusting the global OrganizationScope,
        // which does not apply when the caller is not authenticated.
        $query = Invoice::query()
            ->forOrganization($organization->id)
            ->with('customer');

        match ($type) {
            'invoice' => $query->invoices(),
            'estimate' => $query->estimates(),
            default => $query,
        };

        if (isset($validated['status'])) {
            $query->where('status', InvoiceStatus::from($validated['status']));
        }

        if ($validated['overdue'] ?? false) {
            $query->overdue();
        }

        if (isset($validated['customer'])) {
            $name = $validated['customer'];
            $query->whereHas(
                'customer',
                fn ($customer) => $customer->withoutGlobalScopes()
                    ->where('organization_id', $organization->id)
                    ->where('name', 'like', '%'.$name.'%')
            );
        }

        if (isset($validated['issued_from'])) {
            $query->whereDate('issued_at', '>=', $validated['issued_from']);
        }

        if (isset($validated['issued_to'])) {
            $query->whereDate('issued_at', '<=', $validated['issued_to']);
        }

        match ($validated['sort'] ?? 'newest') {
            'oldest' => $query->orderBy('issued_at')->orderBy('id'),
            'largest' => $query->orderByDesc('total'),
            'smallest' => $query->orderBy('total'),
            default => $query->orderByDesc('issued_at')->orderByDesc('id'),
        };

        $total = (clone $query)->count();
        $invoices = $query->limit($limit)->get();

        return Response::structured([
            'organization' => $organization->name,
            'matching_count' => $total,
            'returned_count' => $invoices->count(),
            'truncated' => $total > $invoices->count(),
            'invoices' => $invoices->map(fn (Invoice $invoice): array => [
                'invoice_number' => $invoice->invoice_number,
                'ulid' => $invoice->ulid,
                'type' => $invoice->type,
                'status' => $invoice->status?->value,
                'customer' => $invoice->customer?->name,
                'subject' => $invoice->subject,
                'currency' => $invoice->currency?->value,
                'total_cents' => $invoice->total,
                'total' => $invoice->formatted_total,
                'amount_paid_cents' => $invoice->amount_paid,
                'balance_cents' => $invoice->remaining_balance,
                'balance' => $invoice->formatted_remaining_balance,
                'issued_at' => $invoice->issued_at?->toDateString(),
                'due_at' => $invoice->due_at?->toDateString(),
                'is_overdue' => $invoice->isInvoice()
                    && $invoice->due_at?->isPast()
                    && ! $invoice->isFullyPaid()
                    && $invoice->status !== InvoiceStatus::VOID,
            ])->all(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->enum(['invoice', 'estimate', 'all'])
                ->description('Which documents to return. Defaults to "invoice"; estimates are excluded unless asked for.')
                ->default('invoice'),

            'status' => $schema->string()
                ->enum(['draft', 'sent', 'accepted', 'partially_paid', 'paid', 'void'])
                ->description('Only return documents in this status.'),

            'customer' => $schema->string()
                ->description('Partial customer name to match, case-insensitive.'),

            'overdue' => $schema->boolean()
                ->description('Only return documents past their due date that are not fully paid or void.'),

            'issued_from' => $schema->string()
                ->description('Earliest issue date, inclusive (YYYY-MM-DD).'),

            'issued_to' => $schema->string()
                ->description('Latest issue date, inclusive (YYYY-MM-DD).'),

            'sort' => $schema->string()
                ->enum(['newest', 'oldest', 'largest', 'smallest'])
                ->description('Ordering. "largest"/"smallest" sort by total, which is only meaningful within a single currency.')
                ->default('newest'),

            'limit' => $schema->integer()
                ->min(1)
                ->max(100)
                ->description('Maximum rows to return, 1-100. Defaults to 25.')
                ->default(25),
        ];
    }
}
