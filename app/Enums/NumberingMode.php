<?php

namespace App\Enums;

use App\Exceptions\InvoiceNumberUnavailable;
use App\Services\InvoiceCreator;

/**
 * How {@see InvoiceCreator} behaves when it cannot do exactly what it was
 * asked: forgive it, or refuse.
 *
 * The invoice form has always forgiven. An inactive pinned series, a pattern
 * needing financial year setup the organization lacks, or any other numbering
 * failure silently gives a dated INV-YYYY-MM-NNNN number so the save goes
 * through, and the person saving sees the result and can fix it.
 *
 * Nobody sees an invoice the system issues by itself. A number in another
 * format is then a permanent gap in the series the organization files its GST
 * returns against, and a reference to another organization's customer leaks
 * data. Strict refuses both instead of guessing.
 */
enum NumberingMode
{
    /**
     * The invoice form. Pinned series are matched by name, any numbering
     * failure falls back to a dated number, and the references are trusted
     * because the form's validation has already checked them.
     */
    case Lenient;

    /**
     * Unattended callers. A pinned series is used by id and must be the
     * organization's and active, or {@see InvoiceNumberUnavailable} is
     * thrown with nothing written. The customer, its locations and the
     * organization location must belong to the organization and not be
     * archived. Invoices only: estimates have no series to be strict about.
     */
    case Strict;
}
