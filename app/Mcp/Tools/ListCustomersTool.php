<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesActingOrganization;
use App\Models\Customer;
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
    'List or search the current organization\'s customers by name or email address, with their '
    .'invoicing totals. Use this to resolve a business name before filtering invoices by customer.'
)]
#[Name('list-customers')]
class ListCustomersTool extends Tool
{
    use ResolvesActingOrganization;

    public function handle(Request $request): Response|ResponseFactory
    {
        $organization = $this->actingOrganization($request);

        if ($organization instanceof Response) {
            return $organization;
        }

        $validated = $request->validate([
            'search' => 'string|max:255',
            'limit' => 'integer|min:1|max:100',
        ], [
            'limit.max' => 'limit cannot exceed 100. Use a narrower search term instead.',
        ]);

        $limit = (int) ($validated['limit'] ?? 25);

        $query = Customer::query()->forOrganization($organization->id);

        if (isset($validated['search'])) {
            $term = '%'.$validated['search'].'%';
            $query->where(fn ($customer) => $customer
                ->where('name', 'like', $term)
                ->orWhere('emails', 'like', $term));
        }

        $total = (clone $query)->count();

        // Customer has no invoices() relation, so the count is a correlated
        // subquery. Global scopes are dropped and organization_id pinned
        // explicitly, because this runs whether or not a user is authenticated.
        $customers = $query
            ->addSelect(['invoice_count' => Invoice::query()
                ->withoutGlobalScopes()
                ->selectRaw('count(*)')
                ->whereColumn('invoices.customer_id', 'customers.id')
                ->where('invoices.organization_id', $organization->id)
                ->where('invoices.type', 'invoice')
                ->whereNull('invoices.deleted_at'),
            ])
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return Response::structured([
            'organization' => $organization->name,
            'matching_count' => $total,
            'returned_count' => $customers->count(),
            'truncated' => $total > $customers->count(),
            'customers' => $customers->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'emails' => $customer->emails?->toArray(),
                'phone' => $customer->formatted_phone,
                'currency' => $customer->currency?->value,
                'gst_treatment' => $customer->gst_treatment?->value,
                'invoice_count' => (int) $customer->invoice_count,
            ])->all(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('Partial name or email address to match, case-insensitive.'),

            'limit' => $schema->integer()
                ->min(1)
                ->max(100)
                ->description('Maximum rows to return, 1-100. Defaults to 25.')
                ->default(25),
        ];
    }
}
