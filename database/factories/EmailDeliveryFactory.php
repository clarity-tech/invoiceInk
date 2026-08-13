<?php

namespace Database\Factories;

use App\Enums\EmailDeliveryStatus;
use App\Mail\DocumentMailer;
use App\Models\EmailDelivery;
use App\Models\Invoice;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailDelivery>
 */
class EmailDeliveryFactory extends Factory
{
    protected $model = EmailDelivery::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'invoice_id' => Invoice::factory(),
            'mailable_type' => DocumentMailer::class,
            'mode' => 'platform',
            'status' => EmailDeliveryStatus::QUEUED,
            'recipients' => [fake()->unique()->userName().'@example.test'],
            'attempts' => 0,
            'queued_at' => now(),
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => EmailDeliveryStatus::SENT,
            'attempts' => 1,
            'sent_at' => now(),
        ]);
    }

    public function failed(string $error = 'Connection could not be established'): static
    {
        return $this->state(fn () => [
            'status' => EmailDeliveryStatus::FAILED,
            'attempts' => 3,
            'error' => $error,
            'failed_at' => now(),
        ]);
    }
}
