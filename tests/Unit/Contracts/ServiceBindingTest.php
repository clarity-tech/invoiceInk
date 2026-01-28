<?php

use App\Contracts\Services\InvoiceNumberingServiceInterface;
use App\Contracts\Services\PdfServiceInterface;
use App\Services\InvoiceNumberingService;
use App\Services\PdfService;

it('resolves PdfServiceInterface to PdfService', function () {
    $service = app(PdfServiceInterface::class);

    expect($service)->toBeInstanceOf(PdfService::class);
});

it('resolves InvoiceNumberingServiceInterface to InvoiceNumberingService', function () {
    $service = app(InvoiceNumberingServiceInterface::class);

    expect($service)->toBeInstanceOf(InvoiceNumberingService::class);
});
