<?php

use App\Contracts\Services\InvoiceNumberingServiceInterface;
use App\Enums\InvoiceStatus;
use App\Enums\NumberingMode;
use App\Exceptions\ExchangeRateUnavailable;
use App\Exceptions\InvoiceNumberUnavailable;
use App\Models\ExchangeRate;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceNumberingSeries;
use App\Services\InvoiceCreator;
use App\ValueObjects\InvoiceInput;
use App\ValueObjects\InvoiceLineInput;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/*
 * Run signed out with two organizations, the way the recurring generator runs.
 * OrganizationScope filters nothing without a signed-in user, so these only
 * pass if the creator keeps organizations apart by the ids it is given.
 */

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 5, 28)->setTime(10, 0));

    Http::preventStrayRequests();

    $this->organization = createOrganizationWithLocation();
    $this->customer = createCustomerWithLocation([], [], $this->organization);
    $this->otherOrganization = createOrganizationWithLocation();
    $this->otherCustomer = createCustomerWithLocation([], [], $this->otherOrganization);

    expect(auth()->check())->toBeFalse()
        ->and($this->otherOrganization->id)->not->toBe($this->organization->id);

    $this->creator = app(InvoiceCreator::class);

    $this->input = fn (array $overrides = []): InvoiceInput => new InvoiceInput(...array_merge([
        'type' => 'invoice',
        'organizationId' => $this->organization->id,
        'organizationLocationId' => $this->organization->primary_location_id,
        'customerId' => $this->customer->id,
        'customerLocationId' => $this->customer->primary_location_id,
        'customerShippingLocationId' => $this->customer->primary_location_id,
        'numberingSeriesId' => null,
        'status' => InvoiceStatus::DRAFT,
        'issuedAt' => CarbonImmutable::parse('2026-05-28'),
        'dueAt' => CarbonImmutable::parse('2026-06-27'),
        'lines' => [new InvoiceLineInput('Retainer', '998311', 1, 100000, 1800)],
    ], $overrides));

    $this->assertNothingWritten = function (InvoiceNumberingSeries ...$series): void {
        expect(Invoice::withoutGlobalScopes()->count())->toBe(0)
            ->and(InvoiceItem::count())->toBe(0);

        foreach ($series as $one) {
            expect($one->fresh()->current_number)->toBe($one->current_number);
        }
    };
});

describe('strict numbering', function () {
    it('refuses an inactive pinned series and writes nothing', function () {
        $series = createNumberingSeries(['current_number' => 3, 'is_active' => false], $this->organization);

        expect(fn () => $this->creator->create(
            ($this->input)(['numberingSeriesId' => $series->id]),
            NumberingMode::Strict,
        ))->toThrow(InvoiceNumberUnavailable::class, 'inactive');

        ($this->assertNothingWritten)($series);
    });

    it('refuses an FY pattern when the organization has no financial year setup', function () {
        $this->organization->update(['financial_year_type' => null]);
        $series = createNumberingSeries([
            'format_pattern' => '{PREFIX}-{FY:2}{SEQUENCE:3}',
            'current_number' => 4,
        ], $this->organization);

        try {
            $this->creator->create(($this->input)(['numberingSeriesId' => $series->id]), NumberingMode::Strict);
            $this->fail('Expected InvoiceNumberUnavailable.');
        } catch (InvoiceNumberUnavailable $e) {
            expect($e->seriesId)->toBe($series->id)
                ->and($e->getPrevious())->toBeInstanceOf(InvalidArgumentException::class);
        }

        ($this->assertNothingWritten)($series);
    });

    it('numbers from the pinned series by id even when an older series shares its name', function () {
        // Created first, so a lookup by name would land on it.
        $decoy = createNumberingSeries([
            'name' => 'Monthly',
            'prefix' => 'DCY',
            'current_number' => 40,
        ], $this->organization);
        $pinned = createNumberingSeries([
            'name' => 'Monthly',
            'prefix' => 'MON',
            'current_number' => 6,
        ], $this->organization);

        $invoice = $this->creator->create(($this->input)(['numberingSeriesId' => $pinned->id]), NumberingMode::Strict);

        expect($invoice->invoice_number)->toBe('MON-0007')
            ->and($invoice->invoice_numbering_series_id)->toBe($pinned->id)
            ->and($pinned->fresh()->current_number)->toBe(7)
            ->and($decoy->fresh()->current_number)->toBe(40);
    });

    it('refuses another organization\'s series before looking up a rate', function () {
        $foreign = createNumberingSeries(['current_number' => 9], $this->otherOrganization);
        // Would need a rate; preventStrayRequests proves none is fetched.
        $this->customer->update(['currency' => 'AED']);

        expect(fn () => $this->creator->create(
            ($this->input)(['numberingSeriesId' => $foreign->id]),
            NumberingMode::Strict,
        ))->toThrow(InvoiceNumberUnavailable::class, 'does not belong');

        ($this->assertNothingWritten)($foreign);
    });

    it('refuses another organization\'s customer', function () {
        $series = createNumberingSeries(['current_number' => 2, 'is_default' => true], $this->organization);

        expect(fn () => $this->creator->create(($this->input)([
            'customerId' => $this->otherCustomer->id,
            'customerLocationId' => $this->otherCustomer->primary_location_id,
            'customerShippingLocationId' => $this->otherCustomer->primary_location_id,
        ]), NumberingMode::Strict))->toThrow(ModelNotFoundException::class);

        ($this->assertNothingWritten)($series);
    });

    it('refuses a billing location that is not the customer\'s', function () {
        expect(fn () => $this->creator->create(($this->input)([
            'customerLocationId' => $this->otherCustomer->primary_location_id,
        ]), NumberingMode::Strict))->toThrow(ModelNotFoundException::class);

        ($this->assertNothingWritten)();
    });

    it('numbers from its own default series, never the other organization\'s', function () {
        $own = createNumberingSeries([
            'prefix' => 'OWN',
            'current_number' => 1,
            'is_default' => true,
        ], $this->organization);
        $theirs = createNumberingSeries([
            'prefix' => 'THR',
            'current_number' => 50,
            'is_default' => true,
        ], $this->otherOrganization);

        $invoice = $this->creator->create(($this->input)(), NumberingMode::Strict);

        expect($invoice->invoice_number)->toBe('OWN-0002')
            ->and($invoice->organization_id)->toBe($this->organization->id)
            ->and($own->fresh()->current_number)->toBe(2)
            ->and($theirs->fresh()->current_number)->toBe(50);
    });

    it('does not create estimates', function () {
        expect(fn () => $this->creator->create(($this->input)(['type' => 'estimate']), NumberingMode::Strict))
            ->toThrow(InvalidArgumentException::class);

        ($this->assertNothingWritten)();
    });
});

describe('lenient numbering', function () {
    it('falls back to a dated INV number for an inactive pinned series, as the form does', function () {
        $series = createNumberingSeries(['current_number' => 3, 'is_active' => false], $this->organization);

        $invoice = $this->creator->create(($this->input)(['numberingSeriesId' => $series->id]), NumberingMode::Lenient);

        expect($invoice->invoice_number)->toBe('INV-2026-05-0001')
            ->and($invoice->invoice_numbering_series_id)->toBeNull()
            ->and($series->fresh()->current_number)->toBe(3);
    });

    it('ignores another organization\'s series and numbers from its own default', function () {
        $foreign = createNumberingSeries(['current_number' => 9], $this->otherOrganization);
        $own = createNumberingSeries([
            'prefix' => 'OWN',
            'current_number' => 1,
            'is_default' => true,
        ], $this->organization);

        $invoice = $this->creator->create(($this->input)(['numberingSeriesId' => $foreign->id]), NumberingMode::Lenient);

        expect($invoice->invoice_number)->toBe('OWN-0002')
            ->and($invoice->invoice_numbering_series_id)->toBe($own->id)
            ->and($foreign->fresh()->current_number)->toBe(9);
    });

    it('gives an estimate a dated EST number', function () {
        $estimate = $this->creator->create(($this->input)(['type' => 'estimate']), NumberingMode::Lenient);

        expect($estimate->invoice_number)->toBe('EST-2026-05-0001')
            ->and($estimate->invoice_numbering_series_id)->toBeNull();
    });
});

it('mints no number and leaves reporting to the caller when the rate is unavailable', function () {
    Http::fake(['api.frankfurter.dev/*' => Http::response([], 500)]);
    ExchangeRate::query()->delete();
    Log::spy();

    $series = createNumberingSeries(['current_number' => 7, 'is_default' => true], $this->organization);
    $this->customer->update(['currency' => 'AED']);

    // A rollback would restore the counter anyway, so prove the series is
    // never asked: the rate is resolved before any number is minted.
    $this->mock(InvoiceNumberingServiceInterface::class)->shouldNotReceive('generateInvoiceNumber', 'generateInvoiceNumberForSeries');
    $creator = app(InvoiceCreator::class);

    foreach (NumberingMode::cases() as $mode) {
        expect(fn () => $creator->create(($this->input)(['numberingSeriesId' => $series->id]), $mode))
            ->toThrow(ExchangeRateUnavailable::class);
    }

    ($this->assertNothingWritten)($series);

    // The form reports it once when converting it to a field error; reporting
    // here as well would send every outage to the alert channel twice.
    Log::shouldNotHaveReceived('error');
});

it('creates the same invoice the form does', function () {
    // Intra-state Indian GST supply with TDS, TCS and remembered recipients.
    $this->organization->primaryLocation->update(['tax_id' => '27ABCDE1234F1Z5']);
    $this->customer->update(['currency' => 'INR']);
    $form = [
        'type' => 'invoice',
        'organization_id' => $this->organization->id,
        'customer_id' => $this->customer->id,
        'organization_location_id' => $this->organization->primary_location_id,
        'customer_location_id' => $this->customer->primary_location_id,
        'customer_shipping_location_id' => $this->customer->primary_location_id,
        'status' => 'draft',
        'issued_at' => '2026-05-28',
        'due_at' => '2026-06-27',
        'place_of_supply' => '27',
        'subject' => 'May retainer',
        'order_number' => 'PO-17',
        'notes' => 'Thanks',
        'terms' => 'Net 30',
        'tds_rate' => 1000,
        'tds_section' => '194J',
        'tcs_rate' => 100,
        'tcs_section' => '206C(1H)',
        'email_recipients' => [
            ['email' => 'billing@example.test', 'checked' => true],
            ['email' => 'BILLING@example.test', 'checked' => false],
        ],
        'items' => [
            ['description' => 'Retainer', 'sac_code' => '998311', 'quantity' => 2, 'unit_price' => 50000, 'tax_rate' => 1800],
            ['description' => 'Printing', 'quantity' => 3, 'unit_price' => 3333, 'tax_rate' => 500],
        ],
    ];

    $this->actingAs($this->organization->owner)
        ->post(route('invoices.store'), $form)
        ->assertSessionHasNoErrors();
    $fromForm = Invoice::withoutGlobalScopes()->sole();

    auth()->forgetGuards();
    expect(auth()->check())->toBeFalse();

    $fromCreator = $this->creator->create(InvoiceInput::fromValidated($form), NumberingMode::Lenient);

    $row = fn (Invoice $invoice): array => Arr::except(
        $invoice->fresh()->getAttributes(),
        ['id', 'ulid', 'invoice_number', 'created_at', 'updated_at'],
    );
    $items = fn (Invoice $invoice): array => $invoice->items()->orderBy('id')->get()
        ->map(fn (InvoiceItem $item) => Arr::except($item->getAttributes(), ['id', 'invoice_id', 'created_at', 'updated_at']))
        ->all();

    expect($row($fromCreator))->toBe($row($fromForm))
        ->and($items($fromCreator))->toBe($items($fromForm))
        ->and($fromForm->invoice_number)->toBe('INV-26001')
        ->and($fromCreator->invoice_number)->toBe('INV-26002')
        ->and($fromCreator->tax_breakdown)->toBe([
            'CGST @ (9%)' => 9000,
            'SGST @ (9%)' => 9000,
            'CGST @ (2.5%)' => 250,
            'SGST @ (2.5%)' => 250,
        ]);
});
