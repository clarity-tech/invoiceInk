<?php

namespace App\Enums;

use Carbon\Carbon;

enum FinancialYearType: string
{
    case APRIL_MARCH = 'april_march';
    case JANUARY_DECEMBER = 'january_december';
    case JULY_JUNE = 'july_june';
    case OCTOBER_SEPTEMBER = 'october_september';

    public function label(): string
    {
        return match ($this) {
            self::APRIL_MARCH => 'April - March',
            self::JANUARY_DECEMBER => 'January - December',
            self::JULY_JUNE => 'July - June',
            self::OCTOBER_SEPTEMBER => 'October - September',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::APRIL_MARCH => 'Financial year runs from April 1st to March 31st',
            self::JANUARY_DECEMBER => 'Financial year runs from January 1st to December 31st',
            self::JULY_JUNE => 'Financial year runs from July 1st to June 30th',
            self::OCTOBER_SEPTEMBER => 'Financial year runs from October 1st to September 30th',
        };
    }

    public function getStartMonth(): int
    {
        return match ($this) {
            self::APRIL_MARCH => 4,
            self::JANUARY_DECEMBER => 1,
            self::JULY_JUNE => 7,
            self::OCTOBER_SEPTEMBER => 10,
        };
    }

    public function getStartDay(): int
    {
        return 1; // All financial years start on the 1st
    }

    public function getEndMonth(): int
    {
        return match ($this) {
            self::APRIL_MARCH => 3,
            self::JANUARY_DECEMBER => 12,
            self::JULY_JUNE => 6,
            self::OCTOBER_SEPTEMBER => 9,
        };
    }

    public function getEndDay(): int
    {
        return match ($this) {
            self::APRIL_MARCH => 31,
            self::JANUARY_DECEMBER => 31,
            self::JULY_JUNE => 30,
            self::OCTOBER_SEPTEMBER => 30,
        };
    }

    public function getFinancialYearStartDate(?int $year = null): Carbon
    {
        $year = $year ?? now()->year;

        // For financial years that cross calendar years, we need to adjust
        $startDate = Carbon::create($year, $this->getStartMonth(), $this->getStartDay());

        // If we're in a financial year that has already started, and the start month
        // is after the current month, we need to go back to the previous year
        if ($this->getStartMonth() > now()->month && $year === now()->year) {
            $startDate = $startDate->subYear();
        }

        return $startDate;
    }

    public function getFinancialYearEndDate(?int $year = null): Carbon
    {
        $startDate = $this->getFinancialYearStartDate($year);

        // Calculate end date based on start date
        $endYear = $startDate->year;
        if ($this->getEndMonth() < $this->getStartMonth()) {
            $endYear = $startDate->year + 1;
        }

        return Carbon::create($endYear, $this->getEndMonth(), $this->getEndDay());
    }

    public function getCurrentFinancialYear(?Carbon $date = null): string
    {
        $date = $date ?? now();
        $startDate = $this->getFinancialYearStartDate($date->year);

        // If the date is before the financial year start, we need to go back one year
        if ($date->lt($startDate)) {
            $startDate = $startDate->subYear();
        }

        $endDate = $this->getFinancialYearEndDate($startDate->year);

        return $startDate->format('Y').'-'.substr($endDate->format('Y'), 2);
    }

    public function getFinancialYearLabel(?Carbon $date = null): string
    {
        $date = $date ?? now();
        $startDate = $this->getFinancialYearStartDate($date->year);

        // If the date is before the financial year start, we need to go back one year
        if ($date->lt($startDate)) {
            $startDate = $startDate->subYear();
        }

        $endDate = $this->getFinancialYearEndDate($startDate->year);

        return $startDate->format('Y').'-'.$endDate->format('Y');
    }

    public function hasFinancialYearChanged(Carbon $lastResetDate, ?Carbon $currentDate = null): bool
    {
        $currentDate = $currentDate ?? now();

        $lastFY = $this->getCurrentFinancialYear($lastResetDate);
        $currentFY = $this->getCurrentFinancialYear($currentDate);

        return $lastFY !== $currentFY;
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($type) => [
            $type->value => $type->label(),
        ])->toArray();
    }
}
