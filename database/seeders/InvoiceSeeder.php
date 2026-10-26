<?php

namespace Database\Seeders;

use App\Enums\Country;
use App\Enums\IndianState;
use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Services\GstBreakdownService;
use App\Services\InvoiceCalculator;
use App\Services\PaymentService;
use Throwable;

class InvoiceSeeder extends ProductionSafeSeeder
{
    public function __construct(
        private readonly InvoiceCalculator $calculator,
        private readonly GstBreakdownService $gstBreakdownService,
        private readonly PaymentService $paymentService,
    ) {}

    protected function seed(): void
    {
        $this->info('Seeding invoices using factory states...');

        $organizations = Organization::with(['customers', 'primaryLocation'])->get();

        foreach ($organizations as $organization) {
            if ($organization->customers->count() > 0 && $organization->primaryLocation) {
                $this->createInvoicesForOrganization($organization);
            }
        }

        $this->archiveOneDraft();

        $this->info('Created invoices and invoice items successfully!');
    }

    /**
     * Archive a draft so the trashed-document paths have something to show.
     */
    private function archiveOneDraft(): void
    {
        $draft = Invoice::where('status', InvoiceStatus::DRAFT)->latest('id')->first();

        if ($draft) {
            $draft->delete();
            $this->info("✓ Archived draft {$draft->invoice_number}");
        }
    }

    private function createInvoicesForOrganization(Organization $organization): void
    {
        $customers = $organization->customers;
        $invoiceCount = 0;

        foreach ($customers as $customer) {
            $invoiceCount += $this->createInvoicesForCustomer($organization, $customer);
        }

        $this->info("✓ Created {$invoiceCount} invoices for {$organization->name}");
    }

    private function createInvoicesForCustomer(Organization $organization, Customer $customer): int
    {
        $invoiceCount = 0;

        // Create invoices based on customer location and organization type
        if ($this->isForeignCustomer($customer, $organization)) {
            // Create foreign currency invoices for foreign customers
            $invoiceCount += $this->createForeignCurrencyInvoices($organization, $customer);
        }

        // Always create some domestic currency invoices
        $domesticInvoices = $this->createDomesticCurrencyInvoices($organization, $customer);
        $invoiceCount += $domesticInvoices;

        return $invoiceCount;
    }

    private function isForeignCustomer(Customer $customer, Organization $organization): bool
    {
        $customerCountry = $customer->primaryLocation?->country ?? 'IN';
        $orgCountry = $organization->country_code?->value ?? 'IN';

        return $customerCountry !== $orgCountry;
    }

    private function createForeignCurrencyInvoices(Organization $organization, Customer $customer): int
    {
        $customerCountry = $customer->primaryLocation?->country ?? 'IN';
        $invoices = [];

        // Create foreign currency invoices based on customer location
        if ($customerCountry === 'AE') {
            $invoices = [
                Invoice::factory()->consultingInvoice()->aedInvoice()->sent(),
                Invoice::factory()->techServicesInvoice()->aedInvoice()->recentlyPaid(),
            ];
        } elseif ($customerCountry === 'US') {
            $invoices = [
                Invoice::factory()->techServicesInvoice()->usdInvoice()->recentlyPaid(),
                Invoice::factory()->consultingInvoice()->usdInvoice()->overdueInvoice(),
            ];
        } elseif ($customerCountry === 'DE') {
            $invoices = [
                Invoice::factory()->consultingInvoice()->eurInvoice()->sent(),
                Invoice::factory()->techServicesInvoice()->eurInvoice()->overdueInvoice(),
            ];
        }

        $invoiceCount = 0;
        foreach ($invoices as $factory) {
            $invoice = $factory
                ->for($organization)
                ->for($customer)
                ->state([
                    'organization_location_id' => $organization->primaryLocation->id,
                    'customer_location_id' => $customer->primaryLocation->id,
                    'customer_shipping_location_id' => $customer->primaryLocation->id,
                ])
                ->withLocations()
                ->create();

            $this->createInvoiceItemsForInvoice($invoice, $organization);
            $invoiceCount++;
        }

        return $invoiceCount;
    }

    private function createDomesticCurrencyInvoices(Organization $organization, Customer $customer): int
    {
        // Take a couple of the organization's patterns per customer to keep the
        // data set manageable. Picking at random rather than always slicing the
        // first two means every pattern — estimates included — shows up.
        $allFactories = $this->getInvoiceFactoriesForOrganization($organization);
        $domesticFactories = fake()->randomElements($allFactories, 2);

        $invoiceCount = 0;
        foreach ($domesticFactories as $factory) {
            $invoice = $factory
                ->for($organization)
                ->for($customer)
                ->state([
                    'organization_location_id' => $organization->primaryLocation->id,
                    'customer_location_id' => $customer->primaryLocation->id,
                    'customer_shipping_location_id' => $customer->primaryLocation->id,
                    'currency' => $organization->currency,
                ])
                ->withLocations()
                ->create();

            $this->createInvoiceItemsForInvoice($invoice, $organization);
            $invoiceCount++;
        }

        return $invoiceCount;
    }

    private function getInvoiceFactoriesForOrganization(Organization $organization): array
    {
        return match (true) {
            str_contains($organization->name, 'Manufacturing') => [
                Invoice::factory()->manufacturingInvoice()->recentDraft(),
                Invoice::factory()->autoPartsInvoice()->sent(),
                Invoice::factory()->manufacturingInvoice()->recentlyPaid(),
                Invoice::factory()->manufacturingInvoice()->overdueInvoice(),
                Invoice::factory()->manufacturingInvoice()->partiallyPaid(),
                Invoice::factory()->estimate()->manufacturingInvoice()->draft(),
                Invoice::factory()->estimate()->manufacturingInvoice()->sent(),
            ],

            str_contains($organization->name, 'TechStart') => [
                Invoice::factory()->techServicesInvoice()->recentDraft(),
                Invoice::factory()->techServicesInvoice()->sent(),
                Invoice::factory()->techServicesInvoice()->recentlyPaid(),
                Invoice::factory()->digitalMarketingInvoice()->sent(),
                Invoice::factory()->techServicesInvoice()->partiallyPaid(),
                Invoice::factory()->estimate()->techServicesInvoice()->approvedEstimate(),
                Invoice::factory()->estimate()->techServicesInvoice()->draft(),
            ],

            str_contains($organization->name, 'EuroConsult') => [
                Invoice::factory()->consultingInvoice()->eurInvoice()->recentlyPaid(),
                Invoice::factory()->consultingInvoice()->eurInvoice()->sent(),
                Invoice::factory()->consultingInvoice()->eurInvoice()->overdueInvoice(),
                Invoice::factory()->consultingInvoice()->eurInvoice()->partiallyPaid(),
                Invoice::factory()->estimate()->consultingInvoice()->eurInvoice()->sent(),
                Invoice::factory()->estimate()->consultingInvoice()->eurInvoice()->rejectedEstimate(),
            ],

            str_contains($organization->name, 'Demo Company') => [
                Invoice::factory()->digitalMarketingInvoice()->inrInvoice()->recentlyPaid(),
                Invoice::factory()->techServicesInvoice()->inrInvoice()->sent(),
                Invoice::factory()->consultingInvoice()->inrInvoice()->draft(),
                Invoice::factory()->consultingInvoice()->inrInvoice()->partiallyPaid(),
                Invoice::factory()->estimate()->techServicesInvoice()->inrInvoice()->draft(),
                Invoice::factory()->estimate()->digitalMarketingInvoice()->inrInvoice()->sent(),
                // Foreign customer AED invoices (Dubai customers)
                Invoice::factory()->consultingInvoice()->aedInvoice()->sent(),
                Invoice::factory()->techServicesInvoice()->aedInvoice()->recentlyPaid(),
                Invoice::factory()->estimate()->consultingInvoice()->aedInvoice()->draft(),
            ],

            str_contains($organization->name, 'Dubai Trading') => [
                Invoice::factory()->consultingInvoice()->aedInvoice()->sent(),
                Invoice::factory()->techServicesInvoice()->aedInvoice()->recentlyPaid(),
                Invoice::factory()->estimate()->consultingInvoice()->aedInvoice()->draft(),
            ],

            // GlobalCorp organizations
            str_contains($organization->name, 'GlobalCorp Holdings') => [
                Invoice::factory()->enterpriseAmount()->consultingInvoice()->inrInvoice()->recentlyPaid(),
                Invoice::factory()->largeAmount()->manufacturingInvoice()->inrInvoice()->sent(),
                Invoice::factory()->estimate()->enterpriseAmount()->consultingInvoice()->inrInvoice()->approvedEstimate(),
                // International customers with different currencies
                Invoice::factory()->consultingInvoice()->usdInvoice()->overdueInvoice(),
                Invoice::factory()->techServicesInvoice()->eurInvoice()->sent(),
                Invoice::factory()->estimate()->manufacturingInvoice()->aedInvoice()->draft(),
            ],

            str_contains($organization->name, 'GlobalCorp Tech') => [
                Invoice::factory()->techServicesInvoice()->inrInvoice()->recentlyPaid(),
                Invoice::factory()->digitalMarketingInvoice()->inrInvoice()->sent(),
                Invoice::factory()->estimate()->techServicesInvoice()->inrInvoice()->draft(),
                // Foreign customers
                Invoice::factory()->techServicesInvoice()->usdInvoice()->recentlyPaid(),
                Invoice::factory()->consultingInvoice()->eurInvoice()->overdueInvoice(),
            ],

            default => [
                Invoice::factory()->mediumAmount()->sent(),
                Invoice::factory()->smallAmount()->recentlyPaid(),
                Invoice::factory()->estimate()->draft(),
            ],
        };
    }

    private function createInvoiceItemsForInvoice(Invoice $invoice, Organization $organization): void
    {
        // Create appropriate invoice items based on invoice type and organization
        $itemsData = $this->getInvoiceItemsForType($organization, $invoice);
        $isIndianSupplier = $organization->country_code === Country::IN;
        // The price lists are in the organization's own currency. Unconverted,
        // a ₹1,20,000 licence billed in dirhams came to AED 1,20,000.
        $rate = ExchangeRateSeeder::rateFor($organization->currency->value, $invoice->currency->value);

        foreach ($itemsData as $itemData) {
            $invoice->items()->create([
                'description' => $itemData['description'],
                // SAC codes identify the service supplied on Indian invoices.
                'sac_code' => $isIndianSupplier ? fake()->randomElement(['998313', '998314', '998319', '997331']) : null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $rate === 1.0 ? $itemData['unit_price'] : $this->convertedPrice($itemData['unit_price'], $rate),
                'tax_rate' => $itemData['tax_rate'],
            ]);
        }

        $this->finalizeInvoice($invoice, $organization);
    }

    /**
     * A price converted into another currency and rounded to two significant
     * figures, as a price list in that currency would set it.
     */
    private function convertedPrice(int $unitPrice, float $rate): int
    {
        $converted = $unitPrice * $rate;
        $step = 10 ** max(0, (int) floor(log10(max($converted, 1))) - 1);

        return (int) (round($converted / $step) * $step);
    }

    /**
     * Bring the document's header in line with the line items just created:
     * derive the totals, resolve the place of supply and GST split, convert
     * foreign currency, and record any payments its status implies.
     */
    private function finalizeInvoice(Invoice $invoice, Organization $organization): void
    {
        $invoice->load(['items', 'customer', 'organizationLocation', 'customerLocation']);

        $isIndianExport = $organization->country_code === Country::IN
            && $this->isExportSupply($invoice, $organization);

        $invoice->place_of_supply = $this->resolvePlaceOfSupply($invoice);
        $invoice->exchange_rate = ExchangeRateSeeder::rateFor($invoice->currency->value, $organization->currency->value);
        $invoice->subject = fake()->boolean(40) ? fake()->randomElement([
            'Monthly retainer',
            'Project milestone',
            'Annual subscription renewal',
            'Professional services rendered',
        ]) : null;
        $invoice->order_number = fake()->boolean(30) ? 'PO-'.fake()->numerify('#####') : null;

        // Indian exports are zero-rated supplies under a Letter of Undertaking,
        // which the invoice has to state.
        if ($isIndianExport) {
            $invoice->notes = 'Supply meant for export under LUT without payment of IGST. Zero rated.';
        }

        // TDS is withheld by Indian buyers on professional fees; TCS is
        // collected on the sale of goods. Only one applies to a given supply,
        // and neither applies to an export.
        if ($organization->country_code === Country::IN && ! $isIndianExport && fake()->boolean(25)) {
            if (fake()->boolean()) {
                $invoice->tds_rate = 1000; // 10%
                $invoice->tds_section = '194J';
            } else {
                $invoice->tcs_rate = 10; // 0.1%
                $invoice->tcs_section = '206C(1H)';
            }
        }

        // Totals are derived from the items so the header can never contradict
        // the lines it is summing.
        $this->calculator->updateInvoiceTotals($invoice);

        $breakdown = $this->gstBreakdownService->build($invoice);
        $invoice->tax_breakdown = $breakdown ?: null;
        $invoice->tax_type = $breakdown ? 'GST' : null;

        $invoice->amount_paid = 0;
        $invoice->save();

        $this->recordPaymentsFor($invoice);
        $this->attachSupportingDocument($invoice);
    }

    /**
     * Attach a supporting document to a minority of documents, so the
     * attachments list and the email attachment picker have real data.
     */
    private function attachSupportingDocument(Invoice $invoice): void
    {
        if (! fake()->boolean(20)) {
            return;
        }

        $body = "Supporting notes for {$invoice->invoice_number}\n\n".fake()->paragraph();

        try {
            $invoice
                ->addMediaFromString($body)
                ->usingFileName('supporting-notes.txt')
                ->toMediaCollection('attachments');
        } catch (Throwable $exception) {
            $this->warn('Skipped attachment — object storage unavailable: '.$exception->getMessage());
        }
    }

    /**
     * Indian place of supply, taken from the customer. Exports don't have one.
     */
    private function resolvePlaceOfSupply(Invoice $invoice): ?string
    {
        if ($invoice->customer?->isOverseas()) {
            return null;
        }

        if ($invoice->customer?->place_of_supply) {
            return $invoice->customer->place_of_supply;
        }

        // Fall back to the state on the customer's billing address.
        $location = $invoice->customerLocation;

        if (! $location?->isIndian()) {
            return null;
        }

        return collect(IndianState::cases())
            ->first(fn (IndianState $state) => $state->label() === $location->state)
            ?->value;
    }

    /**
     * Give settled invoices the payment history their status implies, so the
     * balance, payment list and dashboard KPIs all agree.
     */
    private function recordPaymentsFor(Invoice $invoice): void
    {
        if ($invoice->type !== 'invoice') {
            return;
        }

        $amount = match ($invoice->status) {
            InvoiceStatus::PAID => $invoice->total,
            InvoiceStatus::PARTIALLY_PAID => intdiv($invoice->total, 3),
            default => 0,
        };

        if ($amount <= 0) {
            return;
        }

        $this->paymentService->recordPayment($invoice, [
            'amount' => $amount,
            'payment_date' => ($invoice->issued_at ?? now())->copy()->addDays(fake()->numberBetween(1, 20))->toDateString(),
            'payment_method' => fake()->randomElement(['bank_transfer', 'card', 'cheque', 'cash']),
            'reference' => fake()->bothify('TXN-####-????'),
        ]);
    }

    private function getInvoiceItemsForType(Organization $organization, Invoice $invoice): array
    {
        return match (true) {
            str_contains($organization->name, 'Manufacturing') => $this->getManufacturingItems($organization, $invoice),
            str_contains($organization->name, 'TechStart') => $this->getTechItems($organization, $invoice),
            str_contains($organization->name, 'EuroConsult') => $this->getConsultingItems($organization, $invoice),
            str_contains($organization->name, 'Demo Company') => $this->getDemoItems($organization, $invoice),
            str_contains($organization->name, 'Dubai Trading') => $this->getTradingItems($organization, $invoice),
            str_contains($organization->name, 'GlobalCorp') => $this->getEnterpriseItems($organization, $invoice),
            default => $this->getGeneralServiceItems($organization, $invoice),
        };
    }

    private function getManufacturingItems(Organization $organization, Invoice $invoice): array
    {
        return fake()->randomElement([
            // Steel manufacturing items
            [
                ['description' => 'Steel Components - Grade A', 'quantity' => 1000, 'unit_price' => 2500, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
                ['description' => 'Manufacturing Setup Fee', 'quantity' => 1, 'unit_price' => 500000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
            ],
            // Machined parts
            [
                ['description' => 'Custom Machined Parts', 'quantity' => 500, 'unit_price' => 7500, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
                ['description' => 'Quality Inspection Service', 'quantity' => 20, 'unit_price' => 15000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
            ],
        ]);
    }

    private function getTechItems(Organization $organization, Invoice $invoice): array
    {
        return fake()->randomElement([
            // Software development
            [
                ['description' => 'Mobile App Development - Phase 1', 'quantity' => 1, 'unit_price' => 2500000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
                ['description' => 'UI/UX Design Services', 'quantity' => 80, 'unit_price' => 15000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
            ],
            // Infrastructure
            [
                ['description' => 'Cloud Infrastructure Setup', 'quantity' => 1, 'unit_price' => 1200000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
                ['description' => 'DevOps Consultation (monthly)', 'quantity' => 12, 'unit_price' => 300000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
            ],
        ]);
    }

    private function getConsultingItems(Organization $organization, Invoice $invoice): array
    {
        return fake()->randomElement([
            // Strategic consulting
            [
                ['description' => 'Strategic Business Consulting', 'quantity' => 120, 'unit_price' => 20000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
                ['description' => 'Market Analysis Report', 'quantity' => 1, 'unit_price' => 750000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
            ],
            // Digital transformation
            [
                ['description' => 'Digital Transformation Consultation', 'quantity' => 40, 'unit_price' => 25000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
                ['description' => 'Implementation Roadmap', 'quantity' => 1, 'unit_price' => 500000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
            ],
        ]);
    }

    private function getDemoItems(Organization $organization, Invoice $invoice): array
    {
        return fake()->randomElement([
            // Software license
            [
                ['description' => 'Software License (Annual)', 'quantity' => 1, 'unit_price' => 12000000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
                ['description' => 'Training Services', 'quantity' => 5, 'unit_price' => 1500000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
            ],
            // Development
            [
                ['description' => 'Custom Integration Development', 'quantity' => 160, 'unit_price' => 250000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
                ['description' => 'API Development', 'quantity' => 1, 'unit_price' => 5000000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
            ],
        ]);
    }

    private function getTradingItems(Organization $organization, Invoice $invoice): array
    {
        return fake()->randomElement([
            // Trading services
            [
                ['description' => 'Import/Export Consultation', 'quantity' => 20, 'unit_price' => 25000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
                ['description' => 'Trade Documentation', 'quantity' => 1, 'unit_price' => 150000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
            ],
            // Logistics
            [
                ['description' => 'Logistics Coordination', 'quantity' => 1, 'unit_price' => 350000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
                ['description' => 'Customs Clearance Service', 'quantity' => 3, 'unit_price' => 75000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
            ],
        ]);
    }

    private function getEnterpriseItems(Organization $organization, Invoice $invoice): array
    {
        return fake()->randomElement([
            // Enterprise consulting
            [
                ['description' => 'Enterprise Strategy Consultation', 'quantity' => 200, 'unit_price' => 30000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
                ['description' => 'Business Process Analysis', 'quantity' => 1, 'unit_price' => 2000000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
            ],
            // Technology solutions
            [
                ['description' => 'Enterprise Software License', 'quantity' => 1, 'unit_price' => 5000000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
                ['description' => 'Implementation Services', 'quantity' => 300, 'unit_price' => 25000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
            ],
        ]);
    }

    private function getGeneralServiceItems(Organization $organization, Invoice $invoice): array
    {
        return [
            ['description' => 'Professional Services', 'quantity' => 20, 'unit_price' => 15000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
            ['description' => 'Project Management', 'quantity' => 1, 'unit_price' => 200000, 'tax_rate' => $this->getTaxRate($organization, $invoice)],
        ];
    }

    /**
     * The tax rate for a line, in basis points (1800 = 18%).
     *
     * Drawn from the same catalogue the invoice form offers for the supplier's
     * country, so every seeded rate is one a user could actually have picked.
     */
    private function getTaxRate(Organization $organization, Invoice $invoice): int
    {
        // A cross-border supply is zero-rated: an Indian exporter supplying
        // under LUT charges no GST, and no domestic rate applies elsewhere
        // either. Charging tax here would put, say, a UAE VAT rate on an
        // Indian invoice and have it reported as IGST.
        if ($this->isExportSupply($invoice, $organization)) {
            return 0;
        }

        $country = $organization->country_code ?? Country::IN;

        $rates = collect($country->taxOptions())
            ->pluck('rate')
            ->unique()
            ->values()
            ->all();

        return (int) fake()->randomElement($rates);
    }

    /**
     * Whether the document is billed to a customer outside the supplier's own
     * country.
     */
    private function isExportSupply(Invoice $invoice, Organization $organization): bool
    {
        $customerCountry = $invoice->customerLocation?->country
            ?? $invoice->customer?->primaryLocation?->country;

        if ($customerCountry === null) {
            return false;
        }

        return $customerCountry !== ($organization->country_code?->value ?? Country::IN->value);
    }
}
