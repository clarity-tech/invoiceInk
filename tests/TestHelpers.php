<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Location;
use App\ValueObjects\EmailCollection;

function createCompanyWithLocation(array $companyAttributes = [], array $locationAttributes = []): Company
{
    $defaultLocationAttributes = [
        'name' => 'Test Office',
        'address_line_1' => '123 Test Street',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'Test Country',
        'postal_code' => '12345',
        'locatable_type' => Company::class,
        'locatable_id' => 1, // Temporary
    ];

    $location = Location::create(array_merge($defaultLocationAttributes, $locationAttributes));

    $defaultCompanyAttributes = [
        'name' => 'Test Company',
        'emails' => new EmailCollection(['test@company.com']),
        'primary_location_id' => $location->id,
    ];

    $company = Company::create(array_merge($defaultCompanyAttributes, $companyAttributes));

    // Update location with correct company ID
    $location->update(['locatable_id' => $company->id]);

    return $company->fresh(['primaryLocation']);
}

function createCustomerWithLocation(array $customerAttributes = [], array $locationAttributes = []): Customer
{
    $defaultLocationAttributes = [
        'name' => 'Customer Office',
        'address_line_1' => '456 Customer Avenue',
        'city' => 'Customer City',
        'state' => 'Customer State',
        'country' => 'Test Country',
        'postal_code' => '54321',
        'locatable_type' => Customer::class,
        'locatable_id' => 1, // Temporary
    ];

    $location = Location::create(array_merge($defaultLocationAttributes, $locationAttributes));

    $defaultCustomerAttributes = [
        'name' => 'Test Customer',
        'emails' => new EmailCollection(['test@customer.com']),
        'primary_location_id' => $location->id,
    ];

    $customer = Customer::create(array_merge($defaultCustomerAttributes, $customerAttributes));

    // Update location with correct customer ID
    $location->update(['locatable_id' => $customer->id]);

    return $customer->fresh(['primaryLocation']);
}

function createInvoiceWithItems(
    array $invoiceAttributes = [],
    ?array $items = null,
    ?Company $company = null,
    ?Customer $customer = null
): Invoice {
    if (! $company) {
        $company = createCompanyWithLocation();
    }

    if (! $customer) {
        $customer = createCustomerWithLocation();
    }

    $defaultInvoiceAttributes = [
        'type' => 'invoice',
        'company_location_id' => $company->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
        'invoice_number' => 'INV-'.time(),
        'status' => 'draft',
        'subtotal' => 10000,
        'tax' => 1800,
        'total' => 11800,
    ];

    $invoice = Invoice::create(array_merge($defaultInvoiceAttributes, $invoiceAttributes));

    // Create default items if none provided
    if ($items === null) {
        $items = [
            [
                'description' => 'Test Service',
                'quantity' => 1,
                'unit_price' => 10000,
                'tax_rate' => 18, // 18% as users would enter
            ],
        ];
    }

    // Create invoice items
    foreach ($items as $itemData) {
        InvoiceItem::create(array_merge([
            'invoice_id' => $invoice->id,
        ], $itemData));
    }

    return $invoice->fresh(['items', 'companyLocation', 'customerLocation']);
}

function createLocation(string $type, int $locatableId, array $attributes = []): Location
{
    $defaultAttributes = [
        'name' => 'Test Location',
        'address_line_1' => '123 Default Street',
        'city' => 'Default City',
        'state' => 'Default State',
        'country' => 'Default Country',
        'postal_code' => '12345',
        'locatable_type' => $type,
        'locatable_id' => $locatableId,
    ];

    return Location::create(array_merge($defaultAttributes, $attributes));
}
