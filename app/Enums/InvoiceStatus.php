<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case ACCEPTED = 'accepted';
    case PAID = 'paid';
    case VOID = 'void';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Sent',
            self::ACCEPTED => 'Accepted',
            self::PAID => 'Paid',
            self::VOID => 'Void',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'brand',
            self::ACCEPTED => 'yellow',
            self::PAID => 'green',
            self::VOID => 'red',
        };
    }

    public function badge(): string
    {
        $colors = [
            'gray' => 'bg-gray-100 text-gray-800',
            'brand' => 'bg-brand-100 text-brand-800',
            'yellow' => 'bg-yellow-100 text-yellow-800',
            'green' => 'bg-green-100 text-green-800',
            'red' => 'bg-red-100 text-red-800',
        ];

        $color = $this->color();

        return $colors[$color];
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($status) => [
                $status->value => $status->label(),
            ])
            ->toArray();
    }
}
