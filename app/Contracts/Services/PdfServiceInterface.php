<?php

namespace App\Contracts\Services;

use App\Models\Invoice;
use Symfony\Component\HttpFoundation\Response;

interface PdfServiceInterface
{
    public function generateInvoicePdf(Invoice $invoice): string;

    public function generateEstimatePdf(Invoice $estimate): string;

    public function downloadInvoicePdf(Invoice $invoice): Response;

    public function downloadEstimatePdf(Invoice $estimate): Response;
}
