<?php

use App\Currency;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->invoice = createInvoiceWithItems([
        'status' => InvoiceStatus::SENT,
        'total' => 100000, // 1000.00
        'amount_paid' => 0,
    ]);
});

it('belongs to an invoice', function () {
    $payment = Payment::factory()->forInvoice($this->invoice)->create();

    expect($payment->invoice)->toBeInstanceOf(Invoice::class)
        ->and($payment->invoice->id)->toBe($this->invoice->id);
});

it('invoice has many payments', function () {
    Payment::factory()->forInvoice($this->invoice)->count(3)->create();

    expect($this->invoice->fresh()->payments)->toHaveCount(3);
});

it('calculates remaining balance', function () {
    $this->invoice->update(['amount_paid' => 40000]);

    expect($this->invoice->fresh()->remaining_balance)->toBe(60000);
});

it('remaining balance cannot be negative', function () {
    $this->invoice->update(['amount_paid' => 150000]);

    expect($this->invoice->fresh()->remaining_balance)->toBe(0);
});

it('determines fully paid status', function () {
    $this->invoice->update(['amount_paid' => 100000]);

    expect($this->invoice->fresh()->isFullyPaid())->toBeTrue();
});

it('determines fully paid when overpaid', function () {
    $this->invoice->update(['amount_paid' => 120000]);

    expect($this->invoice->fresh()->isFullyPaid())->toBeTrue();
});

it('determines partially paid status', function () {
    $this->invoice->update(['amount_paid' => 50000]);

    expect($this->invoice->fresh()->isPartiallyPaid())->toBeTrue();
});

it('is not partially paid when fully paid', function () {
    $this->invoice->update(['amount_paid' => 100000]);

    expect($this->invoice->fresh()->isPartiallyPaid())->toBeFalse();
});

it('is not partially paid when unpaid', function () {
    expect($this->invoice->isPartiallyPaid())->toBeFalse();
});

it('calculates payment percentage', function () {
    $this->invoice->update(['amount_paid' => 25000]);

    expect($this->invoice->fresh()->payment_percentage)->toBe(25.0);
});

it('payment percentage is 100 when total is zero', function () {
    $invoice = createInvoiceWithItems(['total' => 0, 'amount_paid' => 0]);

    expect($invoice->payment_percentage)->toBe(100.0);
});

it('formats amount paid', function () {
    $this->invoice->update(['amount_paid' => 50000]);

    expect($this->invoice->fresh()->formatted_amount_paid)->toBeString()
        ->and($this->invoice->fresh()->formatted_amount_paid)->toContain('500');
});

it('formats remaining balance', function () {
    $this->invoice->update(['amount_paid' => 40000]);

    expect($this->invoice->fresh()->formatted_remaining_balance)->toBeString()
        ->and($this->invoice->fresh()->formatted_remaining_balance)->toContain('600');
});

describe('casts and formatting', function () {
    it('casts currency to Currency enum', function () {
        $payment = Payment::factory()->forInvoice($this->invoice)->create();

        expect($payment->currency)->toBeInstanceOf(Currency::class);
    });

    it('casts payment_date to Carbon date', function () {
        $payment = Payment::factory()->forInvoice($this->invoice)->create([
            'payment_date' => '2026-03-17',
        ]);

        expect($payment->payment_date)->toBeInstanceOf(Carbon::class)
            ->and($payment->payment_date->toDateString())->toBe('2026-03-17');
    });

    it('formatMoney formats an integer amount using the payment currency', function () {
        $payment = Payment::factory()->forInvoice($this->invoice)->create([
            'amount' => 50000,
        ]);

        $formatted = $payment->formatMoney(50000);

        expect($formatted)->toBeString()
            ->and($formatted)->toContain('500');
    });

    it('formatted_amount accessor returns formatted payment amount', function () {
        $payment = Payment::factory()->forInvoice($this->invoice)->create([
            'amount' => 75000,
        ]);

        expect($payment->formatted_amount)->toBeString()
            ->and($payment->formatted_amount)->toContain('750');
    });
});

describe('PaymentService', function () {
    it('records payment and updates invoice amount_paid and status', function () {
        $service = new PaymentService;

        $payment = $service->recordPayment($this->invoice, [
            'amount' => 50000,
            'payment_date' => '2026-03-17',
            'payment_method' => 'bank_transfer',
            'reference' => 'TXN-001',
        ]);

        expect($payment)->toBeInstanceOf(Payment::class)
            ->and($payment->amount)->toBe(50000)
            ->and($payment->currency->value)->toBe($this->invoice->currency->value);

        $invoice = $this->invoice->fresh();
        expect($invoice->amount_paid)->toBe(50000)
            ->and($invoice->status)->toBe(InvoiceStatus::PARTIALLY_PAID);
    });

    it('marks invoice as paid when fully paid', function () {
        $service = new PaymentService;

        $service->recordPayment($this->invoice, [
            'amount' => 100000,
            'payment_date' => '2026-03-17',
        ]);

        $invoice = $this->invoice->fresh();
        expect($invoice->amount_paid)->toBe(100000)
            ->and($invoice->status)->toBe(InvoiceStatus::PAID);
    });

    it('sums multiple partial payments correctly', function () {
        $service = new PaymentService;

        $service->recordPayment($this->invoice, [
            'amount' => 30000,
            'payment_date' => '2026-03-15',
        ]);

        $service->recordPayment($this->invoice->fresh(), [
            'amount' => 30000,
            'payment_date' => '2026-03-16',
        ]);

        $invoice = $this->invoice->fresh();
        expect($invoice->amount_paid)->toBe(60000)
            ->and($invoice->status)->toBe(InvoiceStatus::PARTIALLY_PAID);
    });

    it('multiple payments totaling full amount marks as paid', function () {
        $service = new PaymentService;

        $service->recordPayment($this->invoice, [
            'amount' => 60000,
            'payment_date' => '2026-03-15',
        ]);

        $service->recordPayment($this->invoice->fresh(), [
            'amount' => 40000,
            'payment_date' => '2026-03-16',
        ]);

        expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::PAID);
    });

    it('deletes payment and updates invoice amount_paid', function () {
        $service = new PaymentService;

        $payment1 = $service->recordPayment($this->invoice, [
            'amount' => 60000,
            'payment_date' => '2026-03-15',
        ]);

        $service->recordPayment($this->invoice->fresh(), [
            'amount' => 20000,
            'payment_date' => '2026-03-16',
        ]);

        $service->deletePayment($payment1);

        $invoice = $this->invoice->fresh();
        expect($invoice->amount_paid)->toBe(20000)
            ->and($invoice->payments)->toHaveCount(1);
    });

    it('handles overpayment', function () {
        $service = new PaymentService;

        $service->recordPayment($this->invoice, [
            'amount' => 120000,
            'payment_date' => '2026-03-17',
        ]);

        $invoice = $this->invoice->fresh();
        expect($invoice->amount_paid)->toBe(120000)
            ->and($invoice->status)->toBe(InvoiceStatus::PAID)
            ->and($invoice->remaining_balance)->toBe(0);
    });

    it('rejects payment on void invoice', function () {
        $this->invoice->update(['status' => InvoiceStatus::VOID]);

        $service = new PaymentService;

        expect(fn () => $service->recordPayment($this->invoice->fresh(), [
            'amount' => 10000,
            'payment_date' => '2026-03-17',
        ]))->toThrow(InvalidArgumentException::class, 'Void');
    });

    it('rejects payment on draft invoice', function () {
        $this->invoice->update(['status' => InvoiceStatus::DRAFT]);

        $service = new PaymentService;

        expect(fn () => $service->recordPayment($this->invoice->fresh(), [
            'amount' => 10000,
            'payment_date' => '2026-03-17',
        ]))->toThrow(InvalidArgumentException::class, 'Draft');
    });

    it('rejects payment on estimates', function () {
        $estimate = createInvoiceWithItems([
            'type' => 'estimate',
            'status' => InvoiceStatus::SENT,
            'total' => 100000,
        ]);

        $service = new PaymentService;

        expect(fn () => $service->recordPayment($estimate, [
            'amount' => 10000,
            'payment_date' => '2026-03-17',
        ]))->toThrow(InvalidArgumentException::class, 'estimates');
    });

    it('rejects zero amount payment', function () {
        $service = new PaymentService;

        expect(fn () => $service->recordPayment($this->invoice, [
            'amount' => 0,
            'payment_date' => '2026-03-17',
        ]))->toThrow(InvalidArgumentException::class, 'greater than zero');
    });

    it('rejects negative amount payment', function () {
        $service = new PaymentService;

        expect(fn () => $service->recordPayment($this->invoice, [
            'amount' => -5000,
            'payment_date' => '2026-03-17',
        ]))->toThrow(InvalidArgumentException::class, 'greater than zero');
    });

    it('reverts status to sent when all payments deleted', function () {
        $service = new PaymentService;

        $payment = $service->recordPayment($this->invoice, [
            'amount' => 50000,
            'payment_date' => '2026-03-17',
        ]);

        expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::PARTIALLY_PAID);

        $service->deletePayment($payment);

        expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::SENT);
    });
});
