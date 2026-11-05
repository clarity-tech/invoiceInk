<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    /**
     * Statuses that cannot accept payments.
     */
    private const UNPAYABLE_STATUSES = [
        InvoiceStatus::VOID,
        InvoiceStatus::DRAFT,
    ];

    /**
     * @param  array{amount: int, tds_amount?: int, tds_section?: string, payment_date: string, payment_method?: string, reference?: string, notes?: string}  $data
     */
    public function recordPayment(Invoice $invoice, array $data): Payment
    {
        $this->validatePayable($invoice);
        $this->validateAmount($data['amount'], $data['tds_amount'] ?? 0);

        return DB::transaction(function () use ($invoice, $data) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $data['amount'],
                'tds_amount' => $data['tds_amount'] ?? 0,
                'tds_section' => ($data['tds_amount'] ?? 0) > 0 ? ($data['tds_section'] ?? null) : null,
                'currency' => $invoice->currency,
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'] ?? null,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->updateInvoicePaymentStatus($invoice);

            return $payment;
        });
    }

    public function deletePayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $invoice = $payment->invoice;
            $payment->delete();
            $this->updateInvoicePaymentStatus($invoice->fresh());
        });
    }

    public function updateInvoicePaymentStatus(Invoice $invoice): void
    {
        $totalPaid = $invoice->settledByPayments();
        $invoice->amount_paid = $totalPaid;

        // Don't modify status of voided invoices
        if ($invoice->status === InvoiceStatus::VOID) {
            $invoice->save();

            return;
        }

        if ($totalPaid >= $invoice->total) {
            $invoice->status = InvoiceStatus::PAID;
        } elseif ($totalPaid > 0) {
            $invoice->status = InvoiceStatus::PARTIALLY_PAID;
        } elseif ($totalPaid === 0 && in_array($invoice->status, [InvoiceStatus::PAID, InvoiceStatus::PARTIALLY_PAID])) {
            // Revert to SENT when all payments are removed
            $invoice->status = InvoiceStatus::SENT;
        }

        $invoice->save();
    }

    private function validatePayable(Invoice $invoice): void
    {
        if ($invoice->type !== 'invoice') {
            throw new InvalidArgumentException('Payments can only be recorded on invoices, not estimates.');
        }

        if (in_array($invoice->status, self::UNPAYABLE_STATUSES)) {
            throw new InvalidArgumentException("Cannot record payment on a {$invoice->status->label()} invoice.");
        }
    }

    /**
     * A payment can be TDS alone, recorded when the certificate arrives after
     * the cash, so only the two together must be positive.
     */
    private function validateAmount(int $amount, int $tdsAmount): void
    {
        if ($amount < 0 || $tdsAmount < 0 || $amount + $tdsAmount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }
    }
}
