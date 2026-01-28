<?php

namespace App\Contracts\Services;

use App\Models\InvoiceNumberingSeries;
use App\Models\Location;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;

interface InvoiceNumberingServiceInterface
{
    /**
     * @return array{invoice_number: string, series_id: int}
     */
    public function generateInvoiceNumber(Organization $organization, ?Location $location = null, ?string $seriesName = null): array;

    public function getNextNumber(InvoiceNumberingSeries $series): int;

    public function createDefaultSeries(Organization $organization): InvoiceNumberingSeries;

    public function createLocationSeries(Organization $organization, Location $location, string $name, string $prefix, string $formatPattern = '{PREFIX}{YEAR}{SEQUENCE:4}'): InvoiceNumberingSeries;

    public function validateFinancialYearSetup(InvoiceNumberingSeries $series, Organization $organization): bool;

    public function validateNumberUniqueness(string $invoiceNumber, Organization $organization): bool;

    public function getSeriesForOrganization(Organization $organization): Collection;

    public function previewNextNumber(InvoiceNumberingSeries $series): string;
}
