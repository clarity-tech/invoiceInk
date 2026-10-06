<?php

use App\Models\Invoice;
use App\Services\GstBreakdownService;

/**
 * Build a persisted invoice for a supplier with the given GSTIN, place of
 * supply and line items (tax_rate in basis points).
 *
 * @param  array<int, array<string, mixed>>  $items
 */
function gstInvoice(string $supplierGstin, ?string $placeOfSupply, array $items, array $customerLocation = []): Invoice
{
    $org = createOrganizationWithLocation([], ['tax_id' => $supplierGstin, 'country' => 'IN']);
    $customer = createCustomerWithLocation([], $customerLocation, $org);

    return createInvoiceWithItems(
        ['place_of_supply' => $placeOfSupply],
        $items,
        $org,
        $customer,
    );
}

function itemTaxTotal(Invoice $invoice): int
{
    return (int) $invoice->items->sum(fn ($item) => $item->getTaxAmount());
}

beforeEach(function () {
    $this->service = app(GstBreakdownService::class);
});

it('splits intra-state GST into equal CGST and SGST components', function () {
    $invoice = gstInvoice('27ABCDE1234F1Z5', '27', [
        ['description' => 'A', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 1800],
    ]);

    expect($this->service->build($invoice))->toBe([
        'CGST @ (9%)' => 9000,
        'SGST @ (9%)' => 9000,
    ]);
});

it('uses UTGST for an intra-union-territory supply', function () {
    // Chandigarh (04) is a UT without a legislature, so it levies UTGST.
    $invoice = gstInvoice('04ABCDE1234F1Z5', '04', [
        ['description' => 'A', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 1800],
    ]);

    expect($this->service->build($invoice))->toBe([
        'CGST @ (9%)' => 9000,
        'UTGST @ (9%)' => 9000,
    ]);
});

it('uses SGST for Delhi despite it being a union territory', function () {
    // Delhi (07) has a legislature, so it levies SGST rather than UTGST.
    $invoice = gstInvoice('07ABCDE1234F1Z5', '07', [
        ['description' => 'A', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 1800],
    ]);

    expect($this->service->build($invoice))->toBe([
        'CGST @ (9%)' => 9000,
        'SGST @ (9%)' => 9000,
    ]);
});

it('uses IGST for an inter-state supply', function () {
    $invoice = gstInvoice('27ABCDE1234F1Z5', '29', [
        ['description' => 'A', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 1800],
    ]);

    expect($this->service->build($invoice))->toBe([
        'IGST @ (18%)' => 18000,
    ]);
});

it('produces per-rate rows for mixed GST rates', function () {
    $invoice = gstInvoice('27ABCDE1234F1Z5', '27', [
        ['description' => 'A', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 1800],
        ['description' => 'B', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 500],
    ]);

    expect($this->service->build($invoice))->toBe([
        'CGST @ (9%)' => 9000,
        'SGST @ (9%)' => 9000,
        'CGST @ (2.5%)' => 2500,
        'SGST @ (2.5%)' => 2500,
    ]);
});

it('gives the odd cent to the state component so the split reconciles', function () {
    $invoice = gstInvoice('27ABCDE1234F1Z5', '27', [
        ['description' => 'A', 'quantity' => 1, 'unit_price' => 505, 'tax_rate' => 1800],
    ]);

    $breakdown = $this->service->build($invoice);

    expect($breakdown)->toBe([
        'CGST @ (9%)' => 45,
        'SGST @ (9%)' => 46,
    ]);
    expect(array_sum($breakdown))->toBe(itemTaxTotal($invoice));
});

it('falls back to the customer GSTIN when the place of supply is unset', function () {
    // Supplier Maharashtra (27), customer Karnataka (29) → inter-state.
    $invoice = gstInvoice('27ABCDE1234F1Z5', null, [
        ['description' => 'A', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 1800],
    ], ['tax_id' => '29XYZAB6789K1Z2']);

    expect($this->service->build($invoice))->toBe([
        'IGST @ (18%)' => 18000,
    ]);
});

it('treats an overseas customer as an inter-state IGST export', function () {
    // Indian supplier, customer billed in the US → export → IGST, no place of
    // supply needed.
    $invoice = gstInvoice('27ABCDE1234F1Z5', null, [
        ['description' => 'A', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 1800],
    ], ['country' => 'US', 'tax_id' => null]);

    expect($this->service->build($invoice))->toBe([
        'IGST @ (18%)' => 18000,
    ]);
});

it('applies IGST to an overseas customer whose tax id resembles a state code', function () {
    // Foreign (US) customer whose VAT number coincidentally starts with "27",
    // the code for Maharashtra. It must still be an inter-state IGST export,
    // never an intra-state CGST/SGST split.
    $invoice = gstInvoice('27ABCDE1234F1Z5', null, [
        ['description' => 'A', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 1800],
    ], ['country' => 'US', 'tax_id' => '27FOREIGN0001']);

    expect($this->service->build($invoice))->toBe([
        'IGST @ (18%)' => 18000,
    ]);
});

it('returns no breakdown when the place of supply cannot be determined', function () {
    $invoice = gstInvoice('27ABCDE1234F1Z5', null, [
        ['description' => 'A', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 1800],
    ], ['tax_id' => null]);

    expect($this->service->build($invoice))->toBe([]);
});

it('returns no breakdown for a non-Indian supplier', function () {
    $org = createOrganizationWithLocation(
        ['country_code' => 'US', 'currency' => 'USD'],
        ['tax_id' => 'US-TAX-1', 'country' => 'US'],
    );
    $customer = createCustomerWithLocation([], ['country' => 'US'], $org);

    $invoice = createInvoiceWithItems(['currency' => 'USD'], [
        ['description' => 'A', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 1800],
    ], $org, $customer);

    expect($this->service->build($invoice))->toBe([]);
});

it('returns no breakdown when the supplier has no GSTIN', function () {
    $invoice = gstInvoice('', '27', [
        ['description' => 'A', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 1800],
    ]);

    expect($this->service->build($invoice))->toBe([]);
});

it('omits zero-rated items from the breakdown', function () {
    $invoice = gstInvoice('27ABCDE1234F1Z5', '27', [
        ['description' => 'A', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 0],
    ]);

    expect($this->service->build($invoice))->toBe([]);
});
