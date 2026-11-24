<?php

namespace App\Mail\Templates;

use Akaunting\Money\Currency;
use Akaunting\Money\Money;
use App\Models\Customer;
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
     * The amounts are the invoice's stored totals, the same figures as its PDF
     * and `{{balance_due}}`. They used to be re-added from the line items, which
     * know nothing of TDS or TCS, so the email and the PDF of such an invoice
     * disagreed on what was owed.
     *
     * @return array<string, string>
     */
    public static function resolve(Invoice $invoice): array
    {
        $invoice->loadMissing(['customer', 'organization']);

        $currencyCode = $invoice->currency?->value ?? $invoice->currency ?? 'INR';

        $orgEmail = $invoice->organization?->emails?->first()['email'] ?? '';

        $daysOverdue = 0;
        if ($invoice->due_at && $invoice->due_at->isPast()) {
            $daysOverdue = (int) $invoice->due_at->diffInDays(now());
        }

        return [
            '{{customer_name}}' => e($invoice->customer?->name ?? 'Customer'),
            '{{invoice_number}}' => e($invoice->invoice_number ?? ''),
            '{{amount_due}}' => e((new Money((int) $invoice->total, new Currency($currencyCode)))->format()),
            '{{balance_due}}' => e((new Money($invoice->remaining_balance, new Currency($currencyCode)))->format()),
            '{{subtotal}}' => e((new Money((int) $invoice->subtotal, new Currency($currencyCode)))->format()),
            '{{tax_amount}}' => e((new Money((int) $invoice->tax, new Currency($currencyCode)))->format()),
            '{{currency}}' => e($currencyCode),
            '{{due_date}}' => e($invoice->due_at?->format('M d, Y') ?? ''),
            '{{issue_date}}' => e($invoice->issued_at?->format('M d, Y') ?? ''),
            '{{organization_name}}' => e($invoice->organization?->company_name ?? $invoice->organization?->name ?? ''),
            '{{organization_email}}' => e($orgEmail),
            '{{view_url}}' => e($invoice->shareUrl()),
            '{{days_overdue}}' => (string) $daysOverdue,
            '{{status}}' => e(ucfirst($invoice->status?->value ?? 'draft')),
        ];
    }

    /**
     * Variables for an email about a customer rather than one document. The
     * document variables resolve to nothing, so a stray one is not sent as-is.
     *
     * @return array<string, string>
     */
    public static function resolveForCustomer(Customer $customer): array
    {
        $customer->loadMissing('organization');
        $organization = $customer->organization;

        return array_merge(
            array_fill_keys(array_keys(DefaultEmailTemplates::availableVariables()), ''),
            [
                '{{customer_name}}' => e($customer->name),
                '{{organization_name}}' => e($organization?->company_name ?? $organization?->name ?? ''),
                '{{organization_email}}' => e($organization?->emails?->first()['email'] ?? ''),
            ],
        );
    }

    /**
     * Replace all {{variables}} in a string with resolved values.
     */
    public static function render(string $template, array $variables): string
    {
        return str_replace(array_keys($variables), array_values($variables), $template);
    }
}
