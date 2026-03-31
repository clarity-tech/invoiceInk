<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Location;
use App\Services\PdfService;
use App\ValueObjects\EmailCollection;

test('can generate PDF for invoice', function () {
    // Create company location
    $companyLocation = Location::create([
        'name' => 'Test Office',
        'address_line_1' => '123 Test St',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'Test Country',
        'postal_code' => '12345',
        'locatable_type' => Company::class,
        'locatable_id' => 999,
    ]);

    // Create customer location
    $customerLocation = Location::create([
        'name' => 'Customer Office',
        'address_line_1' => '456 Customer Ave',
        'city' => 'Customer City',
        'state' => 'Customer State',
        'country' => 'Customer Country',
        'postal_code' => '67890',
        'locatable_type' => Customer::class,
        'locatable_id' => 999,
    ]);

    // Create company
    $company = Company::create([
        'name' => 'Test Company',
        'emails' => new EmailCollection(['test@company.com']),
        'primary_location_id' => $companyLocation->id,
    ]);

    // Create customer
    $customer = Customer::create([
        'name' => 'Test Customer',
        'emails' => new EmailCollection(['test@customer.com']),
        'primary_location_id' => $customerLocation->id,
    ]);

    // Update locations with correct IDs
    $companyLocation->update(['locatable_id' => $company->id]);
    $customerLocation->update(['locatable_id' => $customer->id]);

    // Create invoice
    $invoice = Invoice::create([
        'type' => 'invoice',
        'company_location_id' => $companyLocation->id,
        'customer_location_id' => $customerLocation->id,
        'invoice_number' => 'TEST-001',
        'status' => 'draft',
        'subtotal' => 1000,
        'tax' => 180,
        'total' => 1180,
    ]);

    // Create invoice item
    InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Test Service',
        'quantity' => 1,
        'unit_price' => 1000,
        'tax_rate' => 18,
    ]);

    $pdfService = new PdfService();
    
    // This should not throw an exception
    $pdfContent = $pdfService->generateInvoicePdf($invoice);
    
    // PDF content should be a string and start with PDF header
    expect($pdfContent)->toBeString();
    expect(substr($pdfContent, 0, 4))->toBe('%PDF');
})->skip('Requires Puppeteer to be properly configured');

test('can generate download response for invoice', function () {
    // Create minimal test data
    $companyLocation = Location::create([
        'name' => 'Test Office',
        'address_line_1' => '123 Test St',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'Test Country',
        'postal_code' => '12345',
        'locatable_type' => Company::class,
        'locatable_id' => 999,
    ]);

    $customerLocation = Location::create([
        'name' => 'Customer Office',
        'address_line_1' => '456 Customer Ave',
        'city' => 'Customer City',
        'state' => 'Customer State',
        'country' => 'Customer Country',
        'postal_code' => '67890',
        'locatable_type' => Customer::class,
        'locatable_id' => 999,
    ]);

    $company = Company::create([
        'name' => 'Test Company',
        'emails' => new EmailCollection(['test@company.com']),
        'primary_location_id' => $companyLocation->id,
    ]);

    $customer = Customer::create([
        'name' => 'Test Customer',
        'emails' => new EmailCollection(['test@customer.com']),
        'primary_location_id' => $customerLocation->id,
    ]);

    $companyLocation->update(['locatable_id' => $company->id]);
    $customerLocation->update(['locatable_id' => $customer->id]);

    $invoice = Invoice::create([
        'type' => 'invoice',
        'company_location_id' => $companyLocation->id,
        'customer_location_id' => $customerLocation->id,
        'invoice_number' => 'TEST-002',
        'status' => 'draft',
        'subtotal' => 1000,
        'tax' => 180,
        'total' => 1180,
    ]);

    InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Test Service',
        'quantity' => 1,
        'unit_price' => 1000,
        'tax_rate' => 18,
    ]);

    // Test that the service can create a download response
    $pdfService = new PdfService();
    
    // This will create a mock PDF if Puppeteer is not available
    expect(function () use ($pdfService, $invoice) {
        return $pdfService->downloadInvoicePdf($invoice);
    })->not->toThrow();
})->skip('Requires Puppeteer to be properly configured');