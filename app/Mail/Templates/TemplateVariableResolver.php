<?php

namespace App\Mail\Templates;

use Akaunting\Money\Currency;
use Akaunting\Money\Money;
use App\Models\Invoice;

/**
 * Resolves template variables from an Invoice model.
 *
 * All values are HTML-escaped to prevent XSS when rendered
 * via {!! !!} in Blade templates.
 */
class TemplateVariableResolver
{
    /**
     * @return array<string, string>
     */
    public static function resolve(Invoice $invoice): array
    {
        $invoice->loadMissing(['customer', 'organization', 'items']);

        $currencyCode = $invoice->currency?->value ?? $invoice->currency ?? 'INR';
        $total = $invoice->items->sum(fn ($item) => $item->getLineTotal());
        $subtotal = $invoice->items->sum(fn ($item) => $item->quantity * $item->unit_price);
        $tax = $total - $subtotal;

        $orgEmail = $invoice->organization?->emails?->first()['email'] ?? '';

        $daysOverdue = 0;
        if ($invoice->due_at && $invoice->due_at->isPast()) {
            $daysOverdue = (int) $invoice->due_at->diffInDays(now());
        }

        $viewRoute = $invoice->isInvoice()
            ? route('invoices.public', $invoice->ulid)
            : route('estimates.public', $invoice->ulid);

        return [
            '{{customer_name}}' => e($invoice->customer?->name ?? 'Customer'),
            '{{invoice_number}}' => e($invoice->invoice_number ?? ''),
            '{{amount_due}}' => e((new Money($total, new Currency($currencyCode)))->format()),
            '{{subtotal}}' => e((new Money($subtotal, new Currency($currencyCode)))->format()),
            '{{tax_amount}}' => e((new Money($tax, new Currency($currencyCode)))->format()),
            '{{currency}}' => e($currencyCode),
            '{{due_date}}' => e($invoice->due_at?->format('M d, Y') ?? ''),
            '{{issue_date}}' => e($invoice->issued_at?->format('M d, Y') ?? ''),
            '{{organization_name}}' => e($invoice->organization?->company_name ?? $invoice->organization?->name ?? ''),
            '{{organization_email}}' => e($orgEmail),
            '{{view_url}}' => $viewRoute,
            '{{days_overdue}}' => (string) $daysOverdue,
            '{{status}}' => e(ucfirst($invoice->status?->value ?? 'draft')),
        ];
    }

    /**
     * Replace all {{variables}} in a string with resolved values.
     */
    public static function render(string $template, array $variables): string
    {
        return str_replace(array_keys($variables), array_values($variables), $template);
    }
}
