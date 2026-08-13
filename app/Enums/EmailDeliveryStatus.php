<?php

namespace App\Enums;

enum EmailDeliveryStatus: string
{
    case QUEUED = 'queued';
    case SENDING = 'sending';
    case SENT = 'sent';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::QUEUED => 'Queued',
            self::SENDING => 'Sending',
            self::SENT => 'Sent',
            self::FAILED => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::QUEUED, self::SENDING => 'gray',
            self::SENT => 'green',
            self::FAILED => 'red',
        };
    }

    public function badge(): string
    {
        return match ($this->color()) {
            'green' => 'bg-green-100 text-green-800',
            'red' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Whether the delivery has reached a terminal state. A queued or sending
     * delivery is still expected to change without any user action.
     */
    public function isSettled(): bool
    {
        return $this === self::SENT || $this === self::FAILED;
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
            ->toArray();
    }
}
