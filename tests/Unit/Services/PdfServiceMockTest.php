<?php

use App\Services\PdfService;

test('pdf service can be instantiated', function () {
    $pdfService = new PdfService;
    expect($pdfService)->toBeInstanceOf(PdfService::class);
});

test('pdf service has correct public methods', function () {
    $pdfService = new PdfService;
    $reflection = new ReflectionClass($pdfService);

    $publicMethods = array_filter(
        $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
        fn ($method) => ! $method->isConstructor()
    );

    $methodNames = array_map(fn ($method) => $method->getName(), $publicMethods);

    expect($methodNames)->toContain('generateInvoicePdf');
    expect($methodNames)->toContain('generateEstimatePdf');
    expect($methodNames)->toContain('downloadInvoicePdf');
    expect($methodNames)->toContain('downloadEstimatePdf');
});

test('pdf service download methods accept invoice parameter', function () {
    $pdfService = new PdfService;
    $reflection = new ReflectionClass($pdfService);

    $downloadInvoiceMethod = $reflection->getMethod('downloadInvoicePdf');
    $parameters = $downloadInvoiceMethod->getParameters();
    expect($parameters)->toHaveCount(1);
    expect($parameters[0]->getType()->getName())->toBe('App\Models\Invoice');

    $downloadEstimateMethod = $reflection->getMethod('downloadEstimatePdf');
    $parameters = $downloadEstimateMethod->getParameters();
    expect($parameters)->toHaveCount(1);
    expect($parameters[0]->getType()->getName())->toBe('App\Models\Invoice');
});

test('pdf service references correct blade templates', function () {
    $pdfService = new PdfService;
    $reflection = new ReflectionClass($pdfService);

    $source = file_get_contents($reflection->getFileName());
    expect($source)->toContain('pdf.invoice');
    expect($source)->toContain('pdf.estimate');
});
