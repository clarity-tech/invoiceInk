<?php

namespace App\Enums;

enum ResetFrequency: string
{
    case NEVER = 'never';
    case YEARLY = 'yearly';
    case MONTHLY = 'monthly';
    case FINANCIAL_YEAR = 'financial_year';

    public function label(): string
    {
        return match ($this) {
            self::NEVER => 'Never Reset',
            self::YEARLY => 'Reset Yearly',
            self::MONTHLY => 'Reset Monthly',
            self::FINANCIAL_YEAR => 'Reset by Financial Year',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::NEVER => 'Numbers continue incrementing without reset',
            self::YEARLY => 'Numbers reset to 1 at the beginning of each year',
            self::MONTHLY => 'Numbers reset to 1 at the beginning of each month',
            self::FINANCIAL_YEAR => 'Numbers reset to 1 at the beginning of each financial year',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [
            $case->value => $case->label(),
        ])->toArray();
    }
}
