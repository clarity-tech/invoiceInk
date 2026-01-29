<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * @param  array{amount: int, payment_date: string, payment_method?: string, reference?: string, notes?: string}  $data
     */
    public function recordPayment(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $data['amount'],
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
        $totalPaid = $invoice->payments()->sum('amount');
        $invoice->amount_paid = $totalPaid;

        if ($totalPaid >= $invoice->total) {
            $invoice->status = InvoiceStatus::PAID;
        } elseif ($totalPaid > 0) {
            $invoice->status = InvoiceStatus::PARTIALLY_PAID;
        }

        $invoice->save();
    }
}
