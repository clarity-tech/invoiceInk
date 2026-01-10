<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\InvoiceTotals;
use Tests\TestCase;

class InvoiceTotalsTest extends TestCase
{
    public function test_can_create_invoice_totals_with_constructor(): void
    {
        $totals = new InvoiceTotals(1000, 180, 1180);

        $this->assertEquals(1000, $totals->subtotal);
        $this->assertEquals(180, $totals->tax);
        $this->assertEquals(1180, $totals->total);
    }

    public function test_zero_factory_method_creates_zero_totals(): void
    {
        $totals = InvoiceTotals::zero();

        $this->assertEquals(0, $totals->subtotal);
        $this->assertEquals(0, $totals->tax);
        $this->assertEquals(0, $totals->total);
    }

    public function test_to_array_returns_correct_array(): void
    {
        $totals = new InvoiceTotals(1000, 180, 1180);

        $array = $totals->toArray();

        $this->assertEquals([
            'subtotal' => 1000,
            'tax' => 180,
            'total' => 1180,
        ], $array);
    }

    public function test_format_subtotal_with_default_currency(): void
    {
        $totals = new InvoiceTotals(150000, 27000, 177000); // ₹1,500.00

        $formatted = $totals->formatSubtotal();

        $this->assertStringContainsString('1,500', $formatted);
        $this->assertStringContainsString('₹', $formatted);
    }

    public function test_format_subtotal_with_specific_currency(): void
    {
        $totals = new InvoiceTotals(150000, 27000, 177000); // $1,500.00

        $formatted = $totals->formatSubtotal('USD');

        $this->assertStringContainsString('1,500', $formatted);
        $this->assertStringContainsString('$', $formatted);
    }

    public function test_format_tax_with_default_currency(): void
    {
        $totals = new InvoiceTotals(150000, 27000, 177000); // ₹270.00 tax

        $formatted = $totals->formatTax();

        $this->assertStringContainsString('270', $formatted);
        $this->assertStringContainsString('₹', $formatted);
    }

    public function test_format_tax_with_specific_currency(): void
    {
        $totals = new InvoiceTotals(150000, 27000, 177000); // $270.00 tax

        $formatted = $totals->formatTax('USD');

        $this->assertStringContainsString('270', $formatted);
        $this->assertStringContainsString('$', $formatted);
    }

    public function test_format_total_with_default_currency(): void
    {
        $totals = new InvoiceTotals(150000, 27000, 177000); // ₹1,770.00

        $formatted = $totals->formatTotal();

        $this->assertStringContainsString('1,770', $formatted);
        $this->assertStringContainsString('₹', $formatted);
    }

    public function test_format_total_with_specific_currency(): void
    {
        $totals = new InvoiceTotals(150000, 27000, 177000); // $1,770.00

        $formatted = $totals->formatTotal('USD');

        $this->assertStringContainsString('1,770', $formatted);
        $this->assertStringContainsString('$', $formatted);
    }

    public function test_format_all_with_default_currency(): void
    {
        $totals = new InvoiceTotals(150000, 27000, 177000);

        $formatted = $totals->formatAll();

        $this->assertIsArray($formatted);
        $this->assertArrayHasKey('subtotal', $formatted);
        $this->assertArrayHasKey('tax', $formatted);
        $this->assertArrayHasKey('total', $formatted);

        $this->assertStringContainsString('1,500', $formatted['subtotal']);
        $this->assertStringContainsString('270', $formatted['tax']);
        $this->assertStringContainsString('1,770', $formatted['total']);

        // All should contain rupee symbol for default INR
        $this->assertStringContainsString('₹', $formatted['subtotal']);
        $this->assertStringContainsString('₹', $formatted['tax']);
        $this->assertStringContainsString('₹', $formatted['total']);
    }

    public function test_format_all_with_specific_currency(): void
    {
        $totals = new InvoiceTotals(150000, 27000, 177000);

        $formatted = $totals->formatAll('EUR');

        $this->assertIsArray($formatted);
        $this->assertArrayHasKey('subtotal', $formatted);
        $this->assertArrayHasKey('tax', $formatted);
        $this->assertArrayHasKey('total', $formatted);

        // All should contain euro symbol
        $this->assertStringContainsString('€', $formatted['subtotal']);
        $this->assertStringContainsString('€', $formatted['tax']);
        $this->assertStringContainsString('€', $formatted['total']);
    }

    public function test_formatting_handles_zero_values(): void
    {
        $totals = InvoiceTotals::zero();

        $this->assertStringContainsString('0', $totals->formatSubtotal());
        $this->assertStringContainsString('0', $totals->formatTax());
        $this->assertStringContainsString('0', $totals->formatTotal());

        $formatted = $totals->formatAll();
        $this->assertStringContainsString('0', $formatted['subtotal']);
        $this->assertStringContainsString('0', $formatted['tax']);
        $this->assertStringContainsString('0', $formatted['total']);
    }

    public function test_formatting_handles_large_values(): void
    {
        // ₹1,00,000.00 subtotal, ₹18,000.00 tax, ₹1,18,000.00 total (Indian grouping)
        $totals = new InvoiceTotals(10000000, 1800000, 11800000);

        $formatted = $totals->formatAll();

        $this->assertStringContainsString('1,00,000', $formatted['subtotal']);
        $this->assertStringContainsString('18,000', $formatted['tax']);
        $this->assertStringContainsString('1,18,000', $formatted['total']);
    }

    public function test_formatting_handles_small_fractional_values(): void
    {
        // ₹1.00 subtotal, ₹0.18 tax, ₹1.18 total
        $totals = new InvoiceTotals(100, 18, 118);

        $formatted = $totals->formatAll();

        $this->assertStringContainsString('1', $formatted['subtotal']);
        $this->assertStringContainsString('0.18', $formatted['tax']);
        $this->assertStringContainsString('1.18', $formatted['total']);
    }

    public function test_formatting_with_different_supported_currencies(): void
    {
        $totals = new InvoiceTotals(100000, 18000, 118000); // ₹1,000.00

        // Test various currencies
        $currencies = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'SGD', 'AED'];

        foreach ($currencies as $currency) {
            $formatted = $totals->formatSubtotal($currency);
            $this->assertIsString($formatted);
            $this->assertNotEmpty($formatted);
        }
    }

    public function test_invoice_totals_is_readonly(): void
    {
        $totals = new InvoiceTotals(1000, 180, 1180);

        // Verify that trying to modify properties should fail
        // This is a compile-time check, but we can verify the class is readonly
        $reflection = new \ReflectionClass($totals);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_formatting_preserves_precision(): void
    {
        // Test with values that could have precision issues
        $totals = new InvoiceTotals(123456, 22222, 145678);

        $formatted = $totals->formatAll('USD');

        // Should handle the precision correctly without rounding errors
        $this->assertStringContainsString('1,234.56', $formatted['subtotal']);
        $this->assertStringContainsString('222.22', $formatted['tax']);
        $this->assertStringContainsString('1,456.78', $formatted['total']);
    }

    public function test_all_format_methods_return_strings(): void
    {
        $totals = new InvoiceTotals(150000, 27000, 177000);

        $this->assertIsString($totals->formatSubtotal());
        $this->assertIsString($totals->formatTax());
        $this->assertIsString($totals->formatTotal());

        $formatAll = $totals->formatAll();
        $this->assertIsString($formatAll['subtotal']);
        $this->assertIsString($formatAll['tax']);
        $this->assertIsString($formatAll['total']);
    }

    public function test_format_methods_with_edge_case_currencies(): void
    {
        $totals = new InvoiceTotals(100000, 18000, 118000);

        // Test JPY (which has no decimal places typically)
        $jpyFormatted = $totals->formatSubtotal('JPY');
        $this->assertIsString($jpyFormatted);

        // Test AED (Emirati Dirham)
        $aedFormatted = $totals->formatSubtotal('AED');
        $this->assertIsString($aedFormatted);
        $this->assertStringContainsString('1,000', $aedFormatted); // Check for formatted amount instead
    }
}
