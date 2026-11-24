<?php

use App\Models\ExchangeRate;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\Http;

/*
 * How store() turns a submitted form into an invoice: which number it gets,
 * what happens when that number cannot come from a series, which dates and
 * amounts are stored. These pin the behaviour that invoice creation shares
 * with the recurring generator, so moving the code cannot quietly change it.
 * Siblings in InvoiceFormControllerTest already cover the GST split, overseas
 * place of supply, TDS/TCS without GST, recipient dedupe and the Save & Send
 * redirect.
 */

beforeEach(function () {
    // 10:00 UTC, so the dated fallback numbers below never drift.
    $this->travelTo(now()->setDate(2026, 5, 28)->setTime(10, 0));

    Http::preventStrayRequests();

    $this->organization = createOrganizationWithLocation();
    $this->actingAs($this->organization->owner);
    $this->customer = createCustomerWithLocation([], [], $this->organization);

    $this->payload = fn (array $overrides = []): array => array_merge([
        'type' => 'invoice',
        'organization_id' => $this->organization->id,
        'customer_id' => $this->customer->id,
        'organization_location_id' => $this->organization->primaryLocation->id,
        'customer_location_id' => $this->customer->primaryLocation->id,
        'customer_shipping_location_id' => $this->customer->primaryLocation->id,
        'status' => 'draft',
        'issued_at' => '2026-05-28',
        'due_at' => '2026-06-27',
        'items' => [
            ['description' => 'Consulting', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 0],
        ],
    ], $overrides);
});

describe('numbering', function () {
    it('numbers from the pinned series when it is active', function () {
        $series = createNumberingSeries([
            'name' => 'Retainers',
            'prefix' => 'RET',
            'format_pattern' => '{PREFIX}-{SEQUENCE:4}',
            'current_number' => 4,
        ], $this->organization);

        $this->post(route('invoices.store'), ($this->payload)([
            'invoice_numbering_series_id' => $series->id,
        ]))->assertSessionHasNoErrors();

        $invoice = Invoice::sole();

        expect($invoice->invoice_number)->toBe('RET-0005')
            ->and($invoice->invoice_numbering_series_id)->toBe($series->id)
            ->and($series->fresh()->current_number)->toBe(5);
    });

    it('falls back to a dated INV number when the pinned series is inactive', function () {
        $inactive = createNumberingSeries([
            'name' => 'Old Series',
            'prefix' => 'OLD',
            'current_number' => 3,
            'is_active' => false,
        ], $this->organization);
        $default = createNumberingSeries([
            'name' => 'Default',
            'prefix' => 'DEF',
            'current_number' => 5,
            'is_default' => true,
        ], $this->organization);

        $this->post(route('invoices.store'), ($this->payload)([
            'invoice_numbering_series_id' => $inactive->id,
        ]))->assertSessionHasNoErrors();

        $invoice = Invoice::sole();

        // Silent: no error reaches the user, and neither series moves.
        expect($invoice->invoice_number)->toBe('INV-2026-05-0001')
            ->and($invoice->invoice_numbering_series_id)->toBeNull()
            ->and($inactive->fresh()->current_number)->toBe(3)
            ->and($default->fresh()->current_number)->toBe(5);
    });

    it('continues the dated fallback sequence within the month', function () {
        $inactive = createNumberingSeries(['is_active' => false], $this->organization);
        createInvoiceWithItems(['invoice_number' => 'INV-2026-05-0004'], null, $this->organization, $this->customer);

        $this->post(route('invoices.store'), ($this->payload)([
            'invoice_numbering_series_id' => $inactive->id,
        ]))->assertSessionHasNoErrors();

        expect(Invoice::latest('id')->first()->invoice_number)->toBe('INV-2026-05-0005');
    });

    it('ignores a series from another organization and numbers from its own default', function () {
        $other = createOrganizationWithLocation([], [], createUserWithTeam());
        $foreign = createNumberingSeries([
            'name' => 'Foreign',
            'prefix' => 'FRN',
            'current_number' => 9,
        ], $other);
        $default = createNumberingSeries([
            'name' => 'Default',
            'prefix' => 'DEF',
            'format_pattern' => '{PREFIX}-{SEQUENCE:4}',
            'current_number' => 5,
            'is_default' => true,
        ], $this->organization);

        $this->post(route('invoices.store'), ($this->payload)([
            'invoice_numbering_series_id' => $foreign->id,
        ]))->assertSessionHasNoErrors();

        $invoice = Invoice::sole();

        expect($invoice->invoice_number)->toBe('DEF-0006')
            ->and($invoice->invoice_numbering_series_id)->toBe($default->id)
            ->and($foreign->fresh()->current_number)->toBe(9);
    });

    it('gives an estimate a dated EST number and never touches a series', function () {
        $series = createNumberingSeries([
            'current_number' => 2,
            'is_default' => true,
        ], $this->organization);

        $response = $this->post(route('invoices.store'), ($this->payload)([
            'type' => 'estimate',
            'invoice_numbering_series_id' => $series->id,
        ]));

        $estimate = Invoice::sole();

        $response->assertRedirect(route('invoices.edit', $estimate->id))
            ->assertSessionHas('success', 'Estimate created successfully.');

        expect($estimate->invoice_number)->toBe('EST-2026-05-0001')
            ->and($estimate->invoice_numbering_series_id)->toBeNull()
            ->and($series->fresh()->current_number)->toBe(2);
    });
});

it('mints no number when the exchange rate is unavailable', function () {
    Http::fake(['api.frankfurter.dev/*' => Http::response([], 500)]);
    ExchangeRate::query()->delete();

    $series = createNumberingSeries([
        'format_pattern' => '{PREFIX}-{SEQUENCE:4}',
        'current_number' => 7,
        'is_default' => true,
    ], $this->organization);
    $aedCustomer = createCustomerWithLocation(['currency' => 'AED'], [], $this->organization);

    $this->from(route('invoices.create'))
        ->post(route('invoices.store'), ($this->payload)([
            'customer_id' => $aedCustomer->id,
            'customer_location_id' => $aedCustomer->primaryLocation->id,
            'customer_shipping_location_id' => $aedCustomer->primaryLocation->id,
        ]))
        ->assertRedirect(route('invoices.create'))
        ->assertSessionHasErrors([
            'issued_at' => __('forms.validation.exchange_rate_unavailable', [
                'from' => 'AED',
                'to' => 'INR',
                'date' => '2026-05-28',
            ]),
        ]);

    expect(Invoice::withTrashed()->count())->toBe(0)
        ->and(InvoiceItem::count())->toBe(0)
        ->and($series->fresh()->current_number)->toBe(7);
});

it('stores the issue and due dates as midnight UTC', function () {
    $this->post(route('invoices.store'), ($this->payload)())->assertSessionHasNoErrors();

    $invoice = Invoice::sole();

    expect($invoice->getRawOriginal('issued_at'))->toBe('2026-05-28 00:00:00')
        ->and($invoice->getRawOriginal('due_at'))->toBe('2026-06-27 00:00:00');
});

it('withholds TDS on the taxable value and collects TCS on the value with GST', function () {
    $this->post(route('invoices.store'), ($this->payload)([
        'tds_rate' => 1000,  // 10%
        'tcs_rate' => 100,   // 1%
        'items' => [
            ['description' => 'Consulting', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 1800],
        ],
    ]))->assertSessionHasNoErrors();

    $invoice = Invoice::sole();

    expect($invoice->subtotal)->toBe(100000)
        ->and($invoice->tax)->toBe(18000)
        ->and($invoice->tds_amount)->toBe(10000)   // 10% of 100000
        ->and($invoice->tcs_amount)->toBe(1180)    // 1% of 118000
        ->and($invoice->total)->toBe(109180);      // 100000 + 18000 + 1180 - 10000
});

it('keeps a zero TDS rate as zero rather than clearing it', function () {
    $this->post(route('invoices.store'), ($this->payload)([
        'tds_rate' => 0,
    ]))->assertSessionHasNoErrors();

    $invoice = Invoice::sole();

    expect($invoice->tds_rate)->toBe(0)
        ->and($invoice->tcs_rate)->toBeNull()
        ->and($invoice->total)->toBe(100000);
});

it('treats a recipient without a checked flag as checked', function () {
    $this->post(route('invoices.store'), ($this->payload)([
        'email_recipients' => [
            ['email' => 'billing@example.test'],
            ['email' => 'muted@example.test', 'checked' => '0'],
        ],
    ]))->assertSessionHasNoErrors();

    expect(Invoice::sole()->email_recipients)->toBe([
        ['email' => 'billing@example.test', 'checked' => true],
        ['email' => 'muted@example.test', 'checked' => false],
    ]);
});

it('takes the currency from the customer, not from the request', function () {
    $this->customer->update(['currency' => 'INR']);

    $this->post(route('invoices.store'), ($this->payload)([
        'currency' => 'USD',
    ]))->assertSessionHasNoErrors();

    $invoice = Invoice::sole();

    expect($invoice->currency->value)->toBe('INR')
        ->and((float) $invoice->exchange_rate)->toBe(1.0);
});
