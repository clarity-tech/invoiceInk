<?php

namespace Tests\Unit\Enums;

use App\Enums\FinancialYearType;
use Carbon\Carbon;
use Tests\TestCase;

class FinancialYearTypeTest extends TestCase
{
    public function test_has_correct_enum_values(): void
    {
        $expectedValues = [
            'april_march',
            'january_december',
            'july_june',
            'october_september',
        ];

        $actualValues = array_map(fn ($case) => $case->value, FinancialYearType::cases());

        $this->assertEquals($expectedValues, $actualValues);
    }

    public function test_label_method_returns_correct_labels(): void
    {
        $this->assertEquals('April - March', FinancialYearType::APRIL_MARCH->label());
        $this->assertEquals('January - December', FinancialYearType::JANUARY_DECEMBER->label());
        $this->assertEquals('July - June', FinancialYearType::JULY_JUNE->label());
        $this->assertEquals('October - September', FinancialYearType::OCTOBER_SEPTEMBER->label());
    }

    public function test_description_method_returns_correct_descriptions(): void
    {
        $this->assertEquals(
            'Financial year runs from April 1st to March 31st',
            FinancialYearType::APRIL_MARCH->description()
        );
        $this->assertEquals(
            'Financial year runs from January 1st to December 31st',
            FinancialYearType::JANUARY_DECEMBER->description()
        );
        $this->assertEquals(
            'Financial year runs from July 1st to June 30th',
            FinancialYearType::JULY_JUNE->description()
        );
        $this->assertEquals(
            'Financial year runs from October 1st to September 30th',
            FinancialYearType::OCTOBER_SEPTEMBER->description()
        );
    }

    public function test_get_start_month_returns_correct_months(): void
    {
        $this->assertEquals(4, FinancialYearType::APRIL_MARCH->getStartMonth());
        $this->assertEquals(1, FinancialYearType::JANUARY_DECEMBER->getStartMonth());
        $this->assertEquals(7, FinancialYearType::JULY_JUNE->getStartMonth());
        $this->assertEquals(10, FinancialYearType::OCTOBER_SEPTEMBER->getStartMonth());
    }

    public function test_get_start_day_always_returns_1(): void
    {
        foreach (FinancialYearType::cases() as $fyType) {
            $this->assertEquals(1, $fyType->getStartDay());
        }
    }

    public function test_get_end_month_returns_correct_months(): void
    {
        $this->assertEquals(3, FinancialYearType::APRIL_MARCH->getEndMonth());
        $this->assertEquals(12, FinancialYearType::JANUARY_DECEMBER->getEndMonth());
        $this->assertEquals(6, FinancialYearType::JULY_JUNE->getEndMonth());
        $this->assertEquals(9, FinancialYearType::OCTOBER_SEPTEMBER->getEndMonth());
    }

    public function test_get_end_day_returns_correct_days(): void
    {
        $this->assertEquals(31, FinancialYearType::APRIL_MARCH->getEndDay());
        $this->assertEquals(31, FinancialYearType::JANUARY_DECEMBER->getEndDay());
        $this->assertEquals(30, FinancialYearType::JULY_JUNE->getEndDay());
        $this->assertEquals(30, FinancialYearType::OCTOBER_SEPTEMBER->getEndDay());
    }

    public function test_get_financial_year_start_date_for_april_march(): void
    {
        $fyType = FinancialYearType::APRIL_MARCH;

        // Test for current year when we're after April
        Carbon::setTestNow('2024-06-15'); // June 2024
        $startDate = $fyType->getFinancialYearStartDate(2024);
        $this->assertEquals('2024-04-01', $startDate->format('Y-m-d'));

        // Test for current year when we're before April
        Carbon::setTestNow('2024-02-15'); // February 2024
        $startDate = $fyType->getFinancialYearStartDate(2024);
        $this->assertEquals('2023-04-01', $startDate->format('Y-m-d'));

        Carbon::setTestNow(); // Reset
    }

    public function test_get_financial_year_start_date_for_january_december(): void
    {
        $fyType = FinancialYearType::JANUARY_DECEMBER;

        $startDate = $fyType->getFinancialYearStartDate(2024);
        $this->assertEquals('2024-01-01', $startDate->format('Y-m-d'));
    }

    public function test_get_financial_year_start_date_for_july_june(): void
    {
        $fyType = FinancialYearType::JULY_JUNE;

        // Test when current date is after July
        Carbon::setTestNow('2024-09-15'); // September 2024
        $startDate = $fyType->getFinancialYearStartDate(2024);
        $this->assertEquals('2024-07-01', $startDate->format('Y-m-d'));

        // Test when current date is before July
        Carbon::setTestNow('2024-03-15'); // March 2024
        $startDate = $fyType->getFinancialYearStartDate(2024);
        $this->assertEquals('2023-07-01', $startDate->format('Y-m-d'));

        Carbon::setTestNow(); // Reset
    }

    public function test_get_financial_year_start_date_for_october_september(): void
    {
        $fyType = FinancialYearType::OCTOBER_SEPTEMBER;

        // Test when current date is after October
        Carbon::setTestNow('2024-12-15'); // December 2024
        $startDate = $fyType->getFinancialYearStartDate(2024);
        $this->assertEquals('2024-10-01', $startDate->format('Y-m-d'));

        // Test when current date is before October
        Carbon::setTestNow('2024-08-15'); // August 2024
        $startDate = $fyType->getFinancialYearStartDate(2024);
        $this->assertEquals('2023-10-01', $startDate->format('Y-m-d'));

        Carbon::setTestNow(); // Reset
    }

    public function test_get_financial_year_end_date_for_april_march(): void
    {
        $fyType = FinancialYearType::APRIL_MARCH;

        Carbon::setTestNow('2024-06-15'); // June 2024
        $endDate = $fyType->getFinancialYearEndDate(2024);
        $this->assertEquals('2025-03-31', $endDate->format('Y-m-d'));

        Carbon::setTestNow(); // Reset
    }

    public function test_get_financial_year_end_date_for_january_december(): void
    {
        $fyType = FinancialYearType::JANUARY_DECEMBER;

        $endDate = $fyType->getFinancialYearEndDate(2024);
        $this->assertEquals('2024-12-31', $endDate->format('Y-m-d'));
    }

    public function test_get_financial_year_end_date_for_july_june(): void
    {
        $fyType = FinancialYearType::JULY_JUNE;

        Carbon::setTestNow('2024-09-15'); // September 2024
        $endDate = $fyType->getFinancialYearEndDate(2024);
        $this->assertEquals('2025-06-30', $endDate->format('Y-m-d'));

        Carbon::setTestNow(); // Reset
    }

    public function test_get_financial_year_end_date_for_october_september(): void
    {
        $fyType = FinancialYearType::OCTOBER_SEPTEMBER;

        Carbon::setTestNow('2024-12-15'); // December 2024
        $endDate = $fyType->getFinancialYearEndDate(2024);
        $this->assertEquals('2025-09-30', $endDate->format('Y-m-d'));

        Carbon::setTestNow(); // Reset
    }

    public function test_get_current_financial_year_for_april_march(): void
    {
        $fyType = FinancialYearType::APRIL_MARCH;

        // Test when in the financial year (after April 1st)
        $date = Carbon::parse('2024-06-15');
        $currentFY = $fyType->getCurrentFinancialYear($date);
        $this->assertEquals('2024-25', $currentFY);

        // Test when before the financial year start (before April 1st)
        $date = Carbon::parse('2024-02-15');
        $currentFY = $fyType->getCurrentFinancialYear($date);
        $this->assertEquals('2023-24', $currentFY);
    }

    public function test_get_current_financial_year_for_january_december(): void
    {
        $fyType = FinancialYearType::JANUARY_DECEMBER;

        $date = Carbon::parse('2024-06-15');
        $currentFY = $fyType->getCurrentFinancialYear($date);
        $this->assertEquals('2024-24', $currentFY);
    }

    public function test_get_current_financial_year_for_july_june(): void
    {
        $fyType = FinancialYearType::JULY_JUNE;

        // Test when in the financial year (after July 1st)
        $date = Carbon::parse('2024-09-15');
        $currentFY = $fyType->getCurrentFinancialYear($date);
        $this->assertEquals('2024-25', $currentFY);

        // Test when before the financial year start (before July 1st)
        $date = Carbon::parse('2024-03-15');
        $currentFY = $fyType->getCurrentFinancialYear($date);
        $this->assertEquals('2023-24', $currentFY);
    }

    public function test_get_current_financial_year_for_october_september(): void
    {
        $fyType = FinancialYearType::OCTOBER_SEPTEMBER;

        // Test when in the financial year (after October 1st)
        $date = Carbon::parse('2024-12-15');
        $currentFY = $fyType->getCurrentFinancialYear($date);
        $this->assertEquals('2024-25', $currentFY);

        // Test when before the financial year start (before October 1st)
        $date = Carbon::parse('2024-08-15');
        $currentFY = $fyType->getCurrentFinancialYear($date);
        $this->assertEquals('2023-24', $currentFY);
    }

    public function test_get_financial_year_label_for_april_march(): void
    {
        $fyType = FinancialYearType::APRIL_MARCH;

        // Test when in the financial year (after April 1st)
        $date = Carbon::parse('2024-06-15');
        $label = $fyType->getFinancialYearLabel($date);
        $this->assertEquals('2024-2025', $label);

        // Test when before the financial year start (before April 1st)
        $date = Carbon::parse('2024-02-15');
        $label = $fyType->getFinancialYearLabel($date);
        $this->assertEquals('2023-2024', $label);
    }

    public function test_get_financial_year_label_for_january_december(): void
    {
        $fyType = FinancialYearType::JANUARY_DECEMBER;

        $date = Carbon::parse('2024-06-15');
        $label = $fyType->getFinancialYearLabel($date);
        $this->assertEquals('2024-2024', $label);
    }

    public function test_has_financial_year_changed_returns_true_when_year_changed(): void
    {
        $fyType = FinancialYearType::APRIL_MARCH;

        $lastResetDate = Carbon::parse('2023-05-15'); // FY 2023-24
        $currentDate = Carbon::parse('2024-05-15'); // FY 2024-25

        $hasChanged = $fyType->hasFinancialYearChanged($lastResetDate, $currentDate);
        $this->assertTrue($hasChanged);
    }

    public function test_has_financial_year_changed_returns_false_when_year_same(): void
    {
        $fyType = FinancialYearType::APRIL_MARCH;

        $lastResetDate = Carbon::parse('2024-05-15'); // FY 2024-25
        $currentDate = Carbon::parse('2024-08-15'); // Still FY 2024-25

        $hasChanged = $fyType->hasFinancialYearChanged($lastResetDate, $currentDate);
        $this->assertFalse($hasChanged);
    }

    public function test_has_financial_year_changed_with_different_fy_types(): void
    {
        // Test January-December FY type
        $fyType = FinancialYearType::JANUARY_DECEMBER;

        $lastResetDate = Carbon::parse('2023-06-15'); // FY 2023
        $currentDate = Carbon::parse('2024-06-15'); // FY 2024

        $hasChanged = $fyType->hasFinancialYearChanged($lastResetDate, $currentDate);
        $this->assertTrue($hasChanged);

        // Test same year
        $lastResetDate = Carbon::parse('2024-03-15'); // FY 2024
        $currentDate = Carbon::parse('2024-09-15'); // Still FY 2024

        $hasChanged = $fyType->hasFinancialYearChanged($lastResetDate, $currentDate);
        $this->assertFalse($hasChanged);
    }

    public function test_options_static_method_returns_correct_array(): void
    {
        $options = FinancialYearType::options();

        $expected = [
            'april_march' => 'April - March',
            'january_december' => 'January - December',
            'july_june' => 'July - June',
            'october_september' => 'October - September',
        ];

        $this->assertEquals($expected, $options);
    }

    public function test_financial_year_calculations_with_edge_dates(): void
    {
        $fyType = FinancialYearType::APRIL_MARCH;

        // Test exactly on the start date
        $startDate = Carbon::parse('2024-04-01');
        $currentFY = $fyType->getCurrentFinancialYear($startDate);
        $this->assertEquals('2024-25', $currentFY);

        // Test exactly on the end date
        $endDate = Carbon::parse('2024-03-31');
        $currentFY = $fyType->getCurrentFinancialYear($endDate);
        $this->assertEquals('2023-24', $currentFY);
    }

    public function test_financial_year_calculations_with_leap_year(): void
    {
        $fyType = FinancialYearType::APRIL_MARCH;

        // Test in a leap year (2024)
        $date = Carbon::parse('2024-02-29'); // Leap day
        $currentFY = $fyType->getCurrentFinancialYear($date);
        $this->assertEquals('2023-24', $currentFY);

        $endDate = $fyType->getFinancialYearEndDate(2023);
        $this->assertEquals('2024-03-31', $endDate->format('Y-m-d'));
    }

    public function test_current_financial_year_defaults_to_now(): void
    {
        $fyType = FinancialYearType::APRIL_MARCH;

        Carbon::setTestNow('2024-06-15');

        // Test without passing a date (should use now())
        $currentFY = $fyType->getCurrentFinancialYear();
        $this->assertEquals('2024-25', $currentFY);

        Carbon::setTestNow(); // Reset
    }

    public function test_get_financial_year_start_date_defaults_to_current_year(): void
    {
        $fyType = FinancialYearType::APRIL_MARCH;

        Carbon::setTestNow('2024-06-15');

        // Test without passing a year (should use current year)
        $startDate = $fyType->getFinancialYearStartDate();
        $this->assertEquals('2024-04-01', $startDate->format('Y-m-d'));

        Carbon::setTestNow(); // Reset
    }

    public function test_complex_financial_year_boundary_scenarios(): void
    {
        $fyType = FinancialYearType::OCTOBER_SEPTEMBER;

        // Test the boundary where financial year crosses calendar year
        $dateInOct = Carbon::parse('2024-10-15'); // Should be FY 2024-25
        $dateInMar = Carbon::parse('2025-03-15'); // Should still be FY 2024-25
        $dateInSep = Carbon::parse('2025-09-15'); // Should still be FY 2024-25

        $this->assertEquals('2024-25', $fyType->getCurrentFinancialYear($dateInOct));
        $this->assertEquals('2024-25', $fyType->getCurrentFinancialYear($dateInMar));
        $this->assertEquals('2024-25', $fyType->getCurrentFinancialYear($dateInSep));

        // Next day should be new FY
        $dateInNewFY = Carbon::parse('2025-10-01'); // Should be FY 2025-26
        $this->assertEquals('2025-26', $fyType->getCurrentFinancialYear($dateInNewFY));
    }
}
