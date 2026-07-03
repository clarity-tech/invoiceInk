<?php

namespace App\Enums;

/**
 * How a customer is treated for GST. Drives whether a supply is domestic
 * (an Indian Place of Supply applies) or an export (it does not).
 */
enum GstTreatment: string
{
    case Domestic = 'domestic';
    case Overseas = 'overseas';

    public function label(): string
    {
        return match ($this) {
            self::Domestic => 'Domestic',
            self::Overseas => 'Overseas',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $treatment) => [$treatment->value => $treatment->label()])
            ->toArray();
    }
}
