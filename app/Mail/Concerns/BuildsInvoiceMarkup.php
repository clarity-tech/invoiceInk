<?php

namespace App\Mail\Concerns;

use Akaunting\Money\Currency;
use Akaunting\Money\Money;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;

/**
 * Gmail's Invoice markup (schema.org JSON-LD), emitted into the head by
 * emails.layouts.branded. Gmail shows it only for senders registered with
 * Google: https://developers.google.com/workspace/gmail/markup/reference/invoice
 */
trait BuildsInvoiceMarkup
{
    /**
     * Null for estimates and void invoices, which owe nothing.
     *
     * @return array<string, mixed>|null
     */
    protected function invoiceMarkup(Invoice $invoice, ?string $viewUrl): ?array
    {
        if (! $invoice->isInvoice() || $invoice->status === InvoiceStatus::VOID) {
            return null;
        }

        // The balance, as the email body and the reminder table show it.
        $balance = new Money($invoice->remaining_balance, new Currency($invoice->currency->value));
        $amountDue = [
            '@type' => 'PriceSpecification',
            'price' => number_format($balance->getValue(), $balance->getCurrency()->getPrecision(), '.', ''),
            'priceCurrency' => $invoice->currency->value,
        ];

        $organization = $invoice->organization;

        return array_filter([
            '@context' => 'http://schema.org',
            '@type' => 'Invoice',
            'accountId' => $invoice->invoice_number,
            'provider' => $organization ? [
                '@type' => 'Organization',
                'name' => $organization->company_name ?? $organization->name,
            ] : null,
            'customer' => $invoice->customer ? [
                '@type' => 'Organization',
                'name' => $invoice->customer->name,
            ] : null,
            'paymentDue' => $invoice->due_at?->toIso8601String(),
            'paymentStatus' => match (true) {
                $invoice->status === InvoiceStatus::PAID => 'PaymentComplete',
                $invoice->isOverdue() => 'PaymentPastDue',
                default => 'PaymentDue',
            },
            'totalPaymentDue' => $amountDue,
            'minimumPaymentDue' => $amountDue,
            'referencesOrder' => $invoice->order_number ? [
                '@type' => 'Order',
                'orderNumber' => $invoice->order_number,
            ] : null,
            'url' => $viewUrl,
            'potentialAction' => $viewUrl ? [
                '@type' => 'ViewAction',
                'url' => $viewUrl,
                'name' => __('messages.email.view_invoice_action'),
            ] : null,
        ], fn (mixed $value): bool => $value !== null);
    }
}
