<?php

namespace App\Services;

use App\Enums\ResetFrequency;
use App\Models\Invoice;
use App\Models\InvoiceNumberingSeries;
use App\Models\Location;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoiceNumberingService
{
    /**
     * Generate a new invoice number for the given organization and location.
     */
    public function generateInvoiceNumber(
        Organization $organization,
        ?Location $location = null,
        ?string $seriesName = null
    ): array {
        return DB::transaction(function () use ($organization, $location, $seriesName) {
            // Find the appropriate numbering series
            $series = $this->getNumberingSeries($organization, $location, $seriesName);

            // Validate financial year setup for FY-based series
            $this->validateFinancialYearSetup($series, $organization);

            // Get the next number
            $sequenceNumber = $this->getNextNumber($series);

            // Generate the formatted invoice number
            $invoiceNumber = $this->formatInvoiceNumber($series, $sequenceNumber);

            // Validate uniqueness within organization
            $this->validateNumberUniqueness($invoiceNumber, $organization);

            // Update the series counter
            $series->incrementAndSave();

            return [
                'invoice_number' => $invoiceNumber,
                'series_id' => $series->id,
                'sequence_number' => $sequenceNumber,
            ];
        });
    }

    /**
     * Get the next sequence number for a series.
     */
    public function getNextNumber(InvoiceNumberingSeries $series): int
    {
        return $series->getNextSequenceNumber();
    }

    /**
     * Create a default numbering series for a new organization.
     */
    public function createDefaultSeries(Organization $organization): InvoiceNumberingSeries
    {
        // Check if default series already exists
        $existingDefault = InvoiceNumberingSeries::forOrganization($organization->id)
            ->default()
            ->first();

        if ($existingDefault) {
            return $existingDefault;
        }

        // Choose format pattern and reset frequency based on organization's financial year setup
        $formatPattern = '{PREFIX}{YEAR}{MONTH}{SEQUENCE:4}';
        $resetFrequency = ResetFrequency::YEARLY;

        if ($organization->financial_year_type && $organization->country_code) {
            $formatPattern = '{PREFIX}{FY}{SEQUENCE:4}';
            $resetFrequency = ResetFrequency::FINANCIAL_YEAR;
        }

        return InvoiceNumberingSeries::create([
            'organization_id' => $organization->id,
            'location_id' => null, // Organization-wide default
            'name' => 'Default Invoice Series',
            'prefix' => 'INV',
            'format_pattern' => $formatPattern,
            'current_number' => 0,
            'reset_frequency' => $resetFrequency,
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    /**
     * Create a location-specific numbering series.
     */
    public function createLocationSeries(
        Organization $organization,
        Location $location,
        string $name,
        string $prefix,
        string $formatPattern = '{PREFIX}{YEAR}{SEQUENCE:4}'
    ): InvoiceNumberingSeries {
        return InvoiceNumberingSeries::create([
            'organization_id' => $organization->id,
            'location_id' => $location->id,
            'name' => $name,
            'prefix' => $prefix,
            'format_pattern' => $formatPattern,
            'current_number' => 0,
            'reset_frequency' => ResetFrequency::YEARLY,
            'is_active' => true,
            'is_default' => false,
        ]);
    }

    /**
     * Validate financial year setup for series that require it.
     */
    public function validateFinancialYearSetup(InvoiceNumberingSeries $series, Organization $organization): bool
    {
        // Check if series uses financial year reset frequency
        if ($series->reset_frequency === ResetFrequency::FINANCIAL_YEAR) {
            if (! $organization->financial_year_type) {
                throw new InvalidArgumentException(
                    "Organization must have financial year configuration to use financial year-based numbering series '{$series->name}'."
                );
            }

            if (! $organization->country_code) {
                throw new InvalidArgumentException(
                    "Organization must have country configuration to use financial year-based numbering series '{$series->name}'."
                );
            }
        }

        // Check if series format pattern uses FY tokens
        if (str_contains($series->format_pattern, '{FY')) {
            if (! $organization->financial_year_type) {
                throw new InvalidArgumentException(
                    "Organization must have financial year configuration to use FY tokens in numbering series '{$series->name}'."
                );
            }
        }

        return true;
    }

    /**
     * Validate that the invoice number is unique within the organization.
     */
    public function validateNumberUniqueness(string $invoiceNumber, Organization $organization): bool
    {
        $exists = Invoice::where('organization_id', $organization->id)
            ->where('invoice_number', $invoiceNumber)
            ->where('type', 'invoice') // Only check invoices, not estimates
            ->exists();

        if ($exists) {
            throw new InvalidArgumentException("Invoice number '{$invoiceNumber}' already exists for this organization.");
        }

        return true;
    }

    /**
     * Get or create the appropriate numbering series.
     */
    protected function getNumberingSeries(
        Organization $organization,
        ?Location $location = null,
        ?string $seriesName = null
    ): InvoiceNumberingSeries {
        $query = InvoiceNumberingSeries::forOrganization($organization->id)
            ->active();

        // If a specific series name is requested
        if ($seriesName) {
            $series = $query->where('name', $seriesName)->first();
            if (! $series) {
                throw new InvalidArgumentException("Numbering series '{$seriesName}' not found for organization.");
            }

            return $series;
        }

        // If a location is specified, try to find a location-specific series
        if ($location) {
            $series = $query->forLocation($location->id)->first();
            if ($series) {
                return $series;
            }
        }

        // Fall back to default series for the organization
        $series = $query->default()->first();
        if ($series) {
            return $series;
        }

        // If no default series exists, create one
        return $this->createDefaultSeries($organization);
    }

    /**
     * Format the invoice number based on the series pattern.
     */
    protected function formatInvoiceNumber(InvoiceNumberingSeries $series, int $sequenceNumber): string
    {
        $pattern = $series->format_pattern;
        $now = now();

        // Get financial year information if organization has financial year setup
        $financialYearReplacements = [];
        if ($series->organization && $series->organization->financial_year_type) {
            $fyType = $series->organization->financial_year_type;
            $currentFY = $fyType->getCurrentFinancialYear($now);
            $fyStartDate = $fyType->getFinancialYearStartDate($now->year);
            $fyEndDate = $fyType->getFinancialYearEndDate($now->year);

            // If we're before the FY start date, adjust to previous year
            if ($now->lt($fyStartDate)) {
                $fyStartDate = $fyStartDate->subYear();
                $fyEndDate = $fyType->getFinancialYearEndDate($fyStartDate->year);
                $currentFY = $fyType->getCurrentFinancialYear($fyStartDate);
            }

            $financialYearReplacements = [
                '{FY}' => $fyStartDate->format('Y'),            // e.g., "2024" - Start year only
                '{FY_START}' => $fyStartDate->format('Y'),      // e.g., "2024"
                '{FY_END}' => $fyEndDate->format('Y'),          // e.g., "2025"
                '{FY_FULL}' => $currentFY,                      // e.g., "2024-25" - Full FY format
                '{FY_RANGE}' => $fyType->getFinancialYearLabel($now), // e.g., "2024-2025" - Full range
            ];
        }

        // Replace pattern tokens
        $replacements = [
            '{PREFIX}' => $series->prefix,
            '{YEAR}' => $now->year,
            '{YEAR:2}' => $now->format('y'),
            '{MONTH}' => $now->format('m'),
            '{MONTH:3}' => $now->format('M'),
            '{DAY}' => $now->format('d'),
        ];

        // Merge financial year replacements
        $replacements = array_merge($replacements, $financialYearReplacements);

        // Handle sequence number with padding
        $pattern = preg_replace_callback(
            '/\{SEQUENCE:(\d+)\}/',
            function ($matches) use ($sequenceNumber) {
                $padding = (int) $matches[1];

                return str_pad($sequenceNumber, $padding, '0', STR_PAD_LEFT);
            },
            $pattern
        );

        // Handle sequence number without padding
        $pattern = str_replace('{SEQUENCE}', $sequenceNumber, $pattern);

        // Apply all other replacements
        return str_replace(array_keys($replacements), array_values($replacements), $pattern);
    }

    /**
     * Get all active series for an organization.
     */
    public function getSeriesForOrganization(Organization $organization): \Illuminate\Database\Eloquent\Collection
    {
        return InvoiceNumberingSeries::forOrganization($organization->id)
            ->active()
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Preview what the next invoice number will be for a series.
     */
    public function previewNextNumber(InvoiceNumberingSeries $series): string
    {
        $nextSequenceNumber = $series->getNextSequenceNumber();

        return $this->formatInvoiceNumber($series, $nextSequenceNumber);
    }
}
