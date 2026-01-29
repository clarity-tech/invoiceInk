<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'amount' => $this->faker->numberBetween(1000, 100000),
            'currency' => 'INR',
            'payment_date' => $this->faker->date(),
            'payment_method' => $this->faker->randomElement(['bank_transfer', 'cash', 'cheque', 'card']),
            'reference' => $this->faker->optional()->bothify('TXN-####-????'),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function forInvoice(Invoice $invoice): static
    {
        return $this->state(fn () => [
            'invoice_id' => $invoice->id,
            'currency' => $invoice->currency,
        ]);
    }

    public function fullPayment(Invoice $invoice): static
    {
        return $this->state(fn () => [
            'invoice_id' => $invoice->id,
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
        ]);
    }
}
