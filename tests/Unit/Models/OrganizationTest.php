<?php

use App\Casts\ContactCollectionCast;
use App\Currency;
use App\Enums\Country;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Organization;
use App\Models\TaxTemplate;
use App\Models\User;
use App\Support\Jetstream;
use App\ValueObjects\BankDetails;
use App\ValueObjects\ContactCollection;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

test('can create organization with required fields', function () {
    $user = User::factory()->create();

    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'company_name' => 'Test Company Inc.',
        'currency' => 'INR',
    ]);

    expect($organization)->toBeInstanceOf(Organization::class);
    expect($organization->name)->toBe('Test Organization');
    expect($organization->company_name)->toBe('Test Company Inc.');
    expect($organization->currency)->toBe(Currency::INR);
});

test('organization extends eloquent model', function () {
    $organization = new Organization;
    expect($organization)->toBeInstanceOf(Model::class);
});

test('organization uses teams table', function () {
    $organization = new Organization;
    expect($organization->getTable())->toBe('teams');
});

test('organization has correct fillable attributes', function () {
    $organization = new Organization;
    $fillable = $organization->getFillable();

    $expectedFillable = [
        'name',
        'company_name',
        'tax_number',
        'registration_number',
        'emails',
        'phone',
        'phone_country',
        'website',
        'currency',
        'notes',
        'primary_location_id',
        'custom_domain',
    ];

    foreach ($expectedFillable as $field) {
        expect($fillable)->toContain($field);
    }
});

test('organization emails are cast to ContactCollection', function () {
    $user = User::factory()->create();
    $contacts = new ContactCollection([['name' => 'Test 1', 'email' => 'test1@example.com'], ['name' => 'Test 2', 'email' => 'test2@example.com']]);
    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'emails' => $contacts,
        'currency' => 'INR',
    ]);

    expect($organization->emails)->toBeInstanceOf(ContactCollection::class);
    expect($organization->emails->getEmails())->toBe(['test1@example.com', 'test2@example.com']);
});

test('organization currency is cast to Currency enum', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'currency' => 'USD',
    ]);

    expect($organization->currency)->toBe(Currency::USD);
    expect($organization->currency->value)->toBe('USD');
});

test('organization can have primary location relationship', function () {
    $location = Location::create([
        'name' => 'HQ Location',
        'address_line_1' => '123 Business St',
        'city' => 'Business City',
        'state' => 'Business State',
        'country' => 'IN',
        'postal_code' => '12345',
        'locatable_type' => Organization::class,
        'locatable_id' => 1,
    ]);

    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'primary_location_id' => $location->id,
        'currency' => 'INR',
    ]);

    expect($organization->primaryLocation)->toBeInstanceOf(Location::class);
    expect($organization->primaryLocation->name)->toBe('HQ Location');
});

test('organization can have multiple customers', function () {
    $organization = createOrganizationWithLocation();

    $customer1 = Customer::create([
        'name' => 'Customer 1',
        'emails' => new ContactCollection([['name' => 'Customer 1', 'email' => 'customer1@test.com']]),
        'organization_id' => $organization->id,
    ]);

    $customer2 = Customer::create([
        'name' => 'Customer 2',
        'emails' => new ContactCollection([['name' => 'Customer 2', 'email' => 'customer2@test.com']]),
        'organization_id' => $organization->id,
    ]);

    expect($organization->customers)->toHaveCount(2);
    expect($organization->customers->first()->name)->toBe('Customer 1');
    expect($organization->customers->last()->name)->toBe('Customer 2');
});

test('organization can have multiple invoices', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);

    $invoice1 = createInvoiceWithItems([
        'invoice_number' => 'INV-001',
    ], null, $organization, $customer);

    $invoice2 = createInvoiceWithItems([
        'invoice_number' => 'INV-002',
    ], null, $organization, $customer);

    expect($organization->invoices)->toHaveCount(2);
    // Check that the invoice numbers start with expected prefixes (they'll have unique suffixes)
    $invoiceNumbers = $organization->invoices->pluck('invoice_number')->toArray();
    expect(collect($invoiceNumbers)->filter(fn ($num) => str_starts_with($num, 'INV-001')))->toHaveCount(1);
    expect(collect($invoiceNumbers)->filter(fn ($num) => str_starts_with($num, 'INV-002')))->toHaveCount(1);
});

test('organization can have multiple tax templates', function () {
    $organization = createOrganizationWithLocation();

    $taxTemplate1 = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'GST 18%',
        'type' => 'GST',
        'rate' => 18.000,
        'country_code' => 'IN',
    ]);

    $taxTemplate2 = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'VAT 5%',
        'type' => 'VAT',
        'rate' => 5.000,
        'country_code' => 'AE',
    ]);

    expect($organization->taxTemplates)->toHaveCount(2);
    expect($organization->taxTemplates->pluck('name')->toArray())->toContain('GST 18%');
    expect($organization->taxTemplates->pluck('name')->toArray())->toContain('VAT 5%');
});

test('organization getUrlAttribute with custom domain', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'custom_domain' => 'custom.example.com',
        'currency' => 'INR',
    ]);

    expect($organization->url)->toBe('https://custom.example.com');
});

test('organization getUrlAttribute without custom domain', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'currency' => 'INR',
    ]);

    expect($organization->url)->toBe("https://clarity-invoicing.com/organizations/{$organization->id}");
});

test('organization getDisplayNameAttribute uses company name when available', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Team Name',
        'user_id' => $user->id,
        'company_name' => 'Company Name Inc.',
        'currency' => 'INR',
    ]);

    expect($organization->display_name)->toBe('Company Name Inc.');
});

test('organization getDisplayNameAttribute falls back to name when no company name', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Team Name',
        'user_id' => $user->id,
        'currency' => 'INR',
    ]);

    expect($organization->display_name)->toBe('Team Name');
});

test('organization getCurrencySymbolAttribute returns correct symbols', function () {
    $testCases = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'INR' => '₹',
        'CAD' => 'CAD', // fallback
    ];

    foreach ($testCases as $currency => $expectedSymbol) {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Test Organization',
            'user_id' => $user->id,
            'currency' => $currency,
        ]);

        expect($organization->currency_symbol)->toBe($expectedSymbol);
    }
});

test('organization can be created with all fillable attributes', function () {
    $user = User::factory()->create();
    $contacts = new ContactCollection([['name' => 'Test', 'email' => 'test@example.com'], ['name' => 'Info', 'email' => 'info@example.com']]);

    $organization = Organization::create([
        'name' => 'Complete Organization',
        'user_id' => $user->id,
        'company_name' => 'Complete Corp.',
        'tax_number' => 'TAX123456789',
        'registration_number' => 'REG987654321',
        'emails' => $contacts,
        'phone' => '+14155552671',
        'phone_country' => 'US',
        'website' => 'https://example.com',
        'currency' => 'EUR',
        'notes' => 'Test notes for organization',
        'custom_domain' => 'custom.example.com',
    ]);

    expect($organization->name)->toBe('Complete Organization');
    expect($organization->company_name)->toBe('Complete Corp.');
    expect($organization->tax_number)->toBe('TAX123456789');
    expect($organization->registration_number)->toBe('REG987654321');
    expect($organization->emails)->toBeInstanceOf(ContactCollection::class);
    expect($organization->emails->count())->toBe(2);
    expect($organization->phone)->toBe('+14155552671');
    expect($organization->phone_country)->toBe('US');
    expect($organization->formatted_phone)->toBe('+1 415-555-2671');
    expect($organization->website)->toBe('https://example.com');
    expect($organization->currency)->toBe(Currency::EUR);
    expect($organization->notes)->toBe('Test notes for organization');
    expect($organization->custom_domain)->toBe('custom.example.com');
});

test('website mutator prepends https when scheme is missing', function () {
    $organization = Organization::factory()->create(['website' => 'example.com']);

    expect($organization->website)->toBe('https://example.com');
});

test('website mutator leaves an existing scheme untouched', function () {
    expect(Organization::factory()->create(['website' => 'http://example.com'])->website)
        ->toBe('http://example.com')
        ->and(Organization::factory()->create(['website' => 'https://secure.test'])->website)
        ->toBe('https://secure.test');
});

test('website mutator normalizes blank values to null', function () {
    expect(Organization::factory()->create(['website' => '   '])->website)->toBeNull();
});

test('normalizeWebsite handles bare domains, schemes, and blanks', function () {
    expect(Organization::normalizeWebsite('example.com'))->toBe('https://example.com')
        ->and(Organization::normalizeWebsite('https://example.com'))->toBe('https://example.com')
        ->and(Organization::normalizeWebsite('HTTP://example.com'))->toBe('HTTP://example.com')
        ->and(Organization::normalizeWebsite(''))->toBeNull()
        ->and(Organization::normalizeWebsite(null))->toBeNull();
});

test('organization handles empty emails collection', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'emails' => new ContactCollection([]),
        'currency' => 'INR',
    ]);

    expect($organization->emails)->toBeInstanceOf(ContactCollection::class);
    expect($organization->emails->isEmpty())->toBeTrue();
});

test('organization emails cast handles array input', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'emails' => ['test1@example.com', 'test2@example.com'],
        'currency' => 'INR',
    ]);

    expect($organization->emails)->toBeInstanceOf(ContactCollection::class);
    expect($organization->emails->count())->toBe(2);
});

test('organization emails cast handles string input', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'emails' => 'single@example.com',
        'currency' => 'INR',
    ]);

    expect($organization->emails)->toBeInstanceOf(ContactCollection::class);
    expect($organization->emails->count())->toBe(1);
    expect($organization->emails->first()['email'])->toBe('single@example.com');
});

test('organization emails cast handles null input', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'emails' => null,
        'currency' => 'INR',
    ]);

    expect($organization->emails)->toBeInstanceOf(ContactCollection::class);
    expect($organization->emails->isEmpty())->toBeTrue();
});

test('organization casts method returns correct array', function () {
    $organization = new Organization;
    $casts = $organization->getCasts();

    expect($casts['emails'])->toBe(ContactCollectionCast::class);
    expect($casts['currency'])->toBe(Currency::class);
});

test('organization does not dispatch unused model events', function () {
    $organization = new Organization;
    $reflectionClass = new ReflectionClass($organization);
    $property = $reflectionClass->getProperty('dispatchesEvents');
    $property->setAccessible(true);
    $events = $property->getValue($organization);

    expect($events)->toBeEmpty();
});

test('organization uses HasFactory trait', function () {
    $organization = new Organization;
    expect(in_array(HasFactory::class, class_uses($organization)))->toBeTrue();
});

test('organization factory creates valid instances', function () {
    $organization = Organization::factory()->create();

    expect($organization)->toBeInstanceOf(Organization::class);
    expect($organization->name)->not->toBeEmpty();
    expect($organization->currency)->toBeInstanceOf(Currency::class);
});

test('organization can have users relationship through jetstream', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    // Add user to organization
    $organization->users()->attach($user, ['role' => 'admin']);

    expect($organization->users)->toHaveCount(1);
    expect($organization->users->first()->id)->toBe($user->id);
    expect($organization->users->first()->membership->role)->toBe('admin');
});

test('organization can have team invitations relationship', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['user_id' => $user->id]);

    // Create a team invitation with proper attributes
    $invitationModel = Jetstream::teamInvitationModel();
    $invitation = $invitationModel::forceCreate([
        'team_id' => $organization->id,
        'email' => 'invite@example.com',
        'role' => 'editor',
    ]);

    expect($organization->teamInvitations)->toHaveCount(1);
    expect($organization->teamInvitations->first()->email)->toBe('invite@example.com');
});

test('organization can be updated with new attributes', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Original Name',
        'user_id' => $user->id,
        'currency' => 'INR',
    ]);

    $organization->update([
        'name' => 'Updated Name',
        'company_name' => 'Updated Corp.',
        'currency' => 'USD',
    ]);

    expect($organization->name)->toBe('Updated Name');
    expect($organization->company_name)->toBe('Updated Corp.');
    expect($organization->currency)->toBe(Currency::USD);
});

test('organization handles nullable fields correctly', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'currency' => 'INR',
        'phone' => null,
        'website' => null,
        'notes' => null,
        'tax_number' => null,
        'registration_number' => null,
        'custom_domain' => null,
    ]);

    expect($organization->phone)->toBeNull();
    expect($organization->website)->toBeNull();
    expect($organization->notes)->toBeNull();
    expect($organization->tax_number)->toBeNull();
    expect($organization->registration_number)->toBeNull();
    expect($organization->custom_domain)->toBeNull();
});

test('organization relationships are correctly configured', function () {
    $organization = new Organization;

    // Test primaryLocation relationship
    $primaryLocationRelation = $organization->primaryLocation();
    expect($primaryLocationRelation)->toBeInstanceOf(BelongsTo::class);

    // Test customers relationship
    $customersRelation = $organization->customers();
    expect($customersRelation)->toBeInstanceOf(HasMany::class);

    // Test invoices relationship
    $invoicesRelation = $organization->invoices();
    expect($invoicesRelation)->toBeInstanceOf(HasMany::class);

    // Test taxTemplates relationship
    $taxTemplatesRelation = $organization->taxTemplates();
    expect($taxTemplatesRelation)->toBeInstanceOf(HasMany::class);
});

test('organization currency enum integration works correctly', function () {
    foreach (Currency::cases() as $currency) {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => "Test Organization {$currency->value}",
            'user_id' => $user->id,
            'currency' => $currency->value,
        ]);

        expect($organization->currency)->toBe($currency);
        expect($organization->currency->value)->toBe($currency->value);
        expect($organization->currency->symbol())->toBe($currency->symbol());
        expect($organization->currency->name())->toBe($currency->name());
    }
});

test('organization hasBankDetails returns true when bank details with bank_name exist', function () {
    $organization = createOrganizationWithLocation([
        'bank_details' => [
            'account_name' => 'Test Company',
            'account_number' => '1234567890',
            'bank_name' => 'Test Bank',
            'ifsc' => 'TEST0001',
        ],
    ]);

    expect($organization->hasBankDetails())->toBeTrue();
});

test('organization hasBankDetails returns false when bank details is null', function () {
    $organization = createOrganizationWithLocation([
        'bank_details' => null,
    ]);

    expect($organization->hasBankDetails())->toBeFalse();
});

test('organization hasBankDetails returns false when bank_name is missing', function () {
    $organization = createOrganizationWithLocation([
        'bank_details' => [
            'account_name' => 'Test Company',
            'account_number' => '1234567890',
        ],
    ]);

    expect($organization->hasBankDetails())->toBeFalse();
});

test('organization bank_details is cast to BankDetails value object', function () {
    $bankDetails = [
        'account_name' => 'Clarity Technologies',
        'account_number' => '123456 0000 7890',
        'bank_name' => 'Bank of Baroda',
        'ifsc' => 'BARB0MGROAD',
        'branch' => 'MG ROAD',
        'swift' => 'BARBINBBXXX',
        'pan' => 'ABCPE1234F',
    ];

    $organization = createOrganizationWithLocation([
        'bank_details' => $bankDetails,
    ]);

    expect($organization->bank_details)->toBeInstanceOf(BankDetails::class);
    expect($organization->bank_details->bankName)->toBe('Bank of Baroda');
    expect($organization->bank_details->ifsc)->toBe('BARB0MGROAD');
    expect($organization->bank_details->accountName)->toBe('Clarity Technologies');
    expect($organization->bank_details->accountNumber)->toBe('123456 0000 7890');
    expect($organization->bank_details->branch)->toBe('MG ROAD');
    expect($organization->bank_details->swift)->toBe('BARBINBBXXX');
    expect($organization->bank_details->pan)->toBe('ABCPE1234F');
});

// --- Setup tracking methods ---

test('isSetupComplete returns true when setup_completed_at is set', function () {
    $organization = createOrganizationWithLocation([
        'setup_completed_at' => now(),
    ]);

    expect($organization->isSetupComplete())->toBeTrue();
});

test('isSetupComplete returns false when setup_completed_at is null', function () {
    $organization = createOrganizationWithLocation([
        'setup_completed_at' => null,
    ]);

    expect($organization->isSetupComplete())->toBeFalse();
});

test('needsSetup returns true when setup_completed_at is null', function () {
    $organization = createOrganizationWithLocation([
        'setup_completed_at' => null,
    ]);

    expect($organization->needsSetup())->toBeTrue();
});

test('needsSetup returns false when setup_completed_at is set', function () {
    $organization = createOrganizationWithLocation([
        'setup_completed_at' => now(),
    ]);

    expect($organization->needsSetup())->toBeFalse();
});

test('needsSetup returns false when setup is already complete', function () {
    $organization = createOrganizationWithLocation([
        'setup_completed_at' => now(),
    ]);

    expect($organization->needsSetup())->toBeFalse();
});

test('markSetupComplete sets setup_completed_at', function () {
    $organization = createOrganizationWithLocation([
        'setup_completed_at' => null,
    ]);

    expect($organization->isSetupComplete())->toBeFalse();

    $organization->markSetupComplete();
    $organization->refresh();

    expect($organization->isSetupComplete())->toBeTrue();
    expect($organization->setup_completed_at)->not->toBeNull();
});

test('getSetupCompletionPercentage returns 100 when setup is complete', function () {
    $organization = createOrganizationWithLocation([
        'setup_completed_at' => now(),
    ]);

    expect($organization->getSetupCompletionPercentage())->toBe(100);
});

test('getSetupCompletionPercentage calculates percentage based on filled fields', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Incomplete Org',
        'user_id' => $user->id,
        'currency' => 'INR',
        'setup_completed_at' => null,
    ]);

    // Only currency is filled out of 6 required fields => ~17%
    $percentage = $organization->getSetupCompletionPercentage();
    expect($percentage)->toBeGreaterThan(0);
    expect($percentage)->toBeLessThan(100);
});

test('getSetupCompletionPercentage returns higher percentage with more fields filled', function () {
    $organization = createOrganizationWithLocation([
        'setup_completed_at' => null,
        'company_name' => 'Test Corp',
        'currency' => 'INR',
        'country_code' => 'IN',
        'financial_year_type' => 'april_march',
    ]);

    // Has company_name, emails, primary_location, currency, country_code, financial_year_type = all 6
    expect($organization->getSetupCompletionPercentage())->toBe(100);
});

test('getMissingSetupFields returns empty array when setup is complete', function () {
    $organization = createOrganizationWithLocation([
        'setup_completed_at' => now(),
    ]);

    expect($organization->getMissingSetupFields())->toBe([]);
});

test('getMissingSetupFields lists all missing fields for bare organization', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Bare Org',
        'user_id' => $user->id,
        'currency' => 'INR',
        'setup_completed_at' => null,
    ]);

    $missing = $organization->getMissingSetupFields();

    expect($missing)->toContain('Company Information');
    expect($missing)->toContain('Contact Emails');
    expect($missing)->toContain('Primary Location');
    expect($missing)->toContain('Country');
    expect($missing)->toContain('Financial Year Configuration');
});

test('getMissingSetupFields does not list fields that are present', function () {
    $organization = createOrganizationWithLocation([
        'setup_completed_at' => null,
    ]);

    $missing = $organization->getMissingSetupFields();

    expect($missing)->not->toContain('Company Information');
    expect($missing)->not->toContain('Currency');
    expect($missing)->not->toContain('Country');
    expect($missing)->not->toContain('Primary Location');
});

// --- Financial year methods ---

test('getCurrentFinancialYear returns financial year string', function () {
    $organization = createOrganizationWithLocation([
        'financial_year_type' => 'april_march',
    ]);

    $fy = $organization->getCurrentFinancialYear();

    expect($fy)->toBeString();
    expect($fy)->not->toBeEmpty();
});

test('getCurrentFinancialYear uses default when financial_year_type is null', function () {
    $organization = createOrganizationWithLocation([
        'financial_year_type' => null,
    ]);

    $fy = $organization->getCurrentFinancialYear();

    expect($fy)->toBeString();
    expect($fy)->not->toBeEmpty();
});

test('getCurrentFinancialYear accepts a custom date', function () {
    $organization = createOrganizationWithLocation([
        'financial_year_type' => 'april_march',
    ]);

    $fy = $organization->getCurrentFinancialYear(Carbon::create(2025, 1, 15));

    expect($fy)->toBeString();
    // Jan 2025 is in FY 2024-25 for April-March
    expect($fy)->toContain('2024');
});

test('hasFinancialYearChanged detects year change', function () {
    $organization = createOrganizationWithLocation([
        'financial_year_type' => 'april_march',
    ]);

    // Last reset was April 2024, current date is April 2025 => FY changed
    $changed = $organization->hasFinancialYearChanged(
        Carbon::create(2024, 4, 1),
        Carbon::create(2025, 4, 1)
    );

    expect($changed)->toBeTrue();
});

test('hasFinancialYearChanged returns false within same FY', function () {
    $organization = createOrganizationWithLocation([
        'financial_year_type' => 'april_march',
    ]);

    // Both dates in same FY (April 2024 - March 2025)
    $changed = $organization->hasFinancialYearChanged(
        Carbon::create(2024, 5, 1),
        Carbon::create(2024, 12, 1)
    );

    expect($changed)->toBeFalse();
});

test('getFinancialYearLabel returns a label string', function () {
    $organization = createOrganizationWithLocation([
        'financial_year_type' => 'april_march',
    ]);

    $label = $organization->getFinancialYearLabel();

    expect($label)->toBeString();
    expect($label)->not->toBeEmpty();
});

test('getFinancialYearStartDate returns a Carbon instance', function () {
    $organization = createOrganizationWithLocation([
        'financial_year_type' => 'april_march',
    ]);

    $startDate = $organization->getFinancialYearStartDate(2024);

    expect($startDate)->toBeInstanceOf(Carbon::class);
    expect($startDate->month)->toBe(4);
    expect($startDate->day)->toBe(1);
});

test('getFinancialYearEndDate returns a Carbon instance', function () {
    $organization = createOrganizationWithLocation([
        'financial_year_type' => 'april_march',
    ]);

    $endDate = $organization->getFinancialYearEndDate(2024);

    expect($endDate)->toBeInstanceOf(Carbon::class);
    expect($endDate->month)->toBe(3);
    expect($endDate->year)->toBe(2025);
});

test('getNextFinancialYearResetDate returns a future Carbon date', function () {
    $organization = createOrganizationWithLocation([
        'financial_year_type' => 'april_march',
    ]);

    $nextReset = $organization->getNextFinancialYearResetDate();

    expect($nextReset)->toBeInstanceOf(Carbon::class);
});

// --- Country-based methods ---

test('getCountryAttribute returns Country enum', function () {
    $organization = createOrganizationWithLocation([
        'country_code' => 'IN',
    ]);

    expect($organization->country)->toBe(Country::IN);
});

test('getCountryAttribute returns null when no country set', function () {
    $organization = createOrganizationWithLocation([
        'country_code' => null,
    ]);

    expect($organization->country)->toBeNull();
});

test('getSetupRecommendations returns recommendations for a country', function () {
    $organization = createOrganizationWithLocation([
        'country_code' => 'IN',
    ]);

    $recommendations = $organization->getSetupRecommendations();

    expect($recommendations)->toBeArray();
    expect($recommendations)->toHaveKey('currency');
    expect($recommendations)->toHaveKey('financial_year_type');
    expect($recommendations)->toHaveKey('tax_system');
    expect($recommendations)->toHaveKey('common_tax_rates');
    expect($recommendations)->toHaveKey('recommended_numbering_format');
    expect($recommendations)->toHaveKey('financial_year_options');
    expect($recommendations)->toHaveKey('default_financial_year_option');
});

test('getSetupRecommendations returns empty array when no country set', function () {
    $organization = createOrganizationWithLocation([
        'country_code' => null,
    ]);

    expect($organization->getSetupRecommendations())->toBe([]);
});

test('getTaxSystemInfo returns tax info for organization country', function () {
    $organization = createOrganizationWithLocation([
        'country_code' => 'IN',
    ]);

    $taxInfo = $organization->getTaxSystemInfo();

    expect($taxInfo)->toBeArray();
    expect($taxInfo)->toHaveKey('name');
    expect($taxInfo)->toHaveKey('rates');
});

test('getTaxSystemInfo returns empty array when no country set', function () {
    $organization = createOrganizationWithLocation([
        'country_code' => null,
    ]);

    expect($organization->getTaxSystemInfo())->toBe([]);
});

// --- Numbering series relationship ---

test('organization has numbering series relationship', function () {
    $organization = new Organization;
    $relation = $organization->numberingSeries();

    expect($relation)->toBeInstanceOf(HasMany::class);
});

// --- allUsers, hasUser, hasUserWithEmail, userHasPermission, removeUser ---

test('allUsers includes owner and team members', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $organization = $owner->currentTeam;

    $member = User::factory()->create();
    $organization->users()->attach($member, ['role' => 'editor']);

    $allUsers = $organization->allUsers();

    expect($allUsers->pluck('id'))->toContain($owner->id);
    expect($allUsers->pluck('id'))->toContain($member->id);
});

test('hasUser returns true for owner', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $organization = $owner->currentTeam;

    expect($organization->hasUser($owner))->toBeTrue();
});

test('hasUserWithEmail returns true for existing user email', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $organization = $owner->currentTeam;

    expect($organization->hasUserWithEmail($owner->email))->toBeTrue();
});

test('hasUserWithEmail returns false for non-existing email', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $organization = $owner->currentTeam;

    expect($organization->hasUserWithEmail('nonexistent@example.test'))->toBeFalse();
});

test('removeUser removes user from organization', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $organization = $owner->currentTeam;

    $member = User::factory()->create();
    $organization->users()->attach($member, ['role' => 'editor']);
    $member->update(['current_team_id' => $organization->id]);

    $organization->removeUser($member);
    $member->refresh();

    expect($organization->users()->where('user_id', $member->id)->exists())->toBeFalse();
    expect($member->current_team_id)->toBeNull();
});

// --- Media collections ---

test('registerMediaCollections defines logo collection', function () {
    $organization = createOrganizationWithLocation();
    $organization->registerMediaCollections();

    $collections = $organization->mediaCollections;

    expect(collect($collections)->pluck('name'))->toContain('logo');
});

test('registerMediaConversions can be called without error', function () {
    $organization = createOrganizationWithLocation();

    // Just verify it doesn't throw — conversions are registered internally
    $organization->registerMediaConversions(null);
    expect(true)->toBeTrue();
});

test('logoUrl returns null when no logo uploaded', function () {
    $organization = createOrganizationWithLocation();

    expect($organization->logo_url)->toBeNull();
});

test('logoThumbUrl returns null when no logo uploaded', function () {
    $organization = createOrganizationWithLocation();

    expect($organization->logo_thumb_url)->toBeNull();
});

test('logoUrl falls back to a regular url on disks without temporary url support', function () {
    // A plain local disk (no serve/temporary_url) does not support temporary URLs,
    // mirroring the local/dev setup where getTemporaryUrl() previously threw.
    config([
        'filesystems.disks.plain_local' => [
            'driver' => 'local',
            'root' => storage_path('framework/testing/disks/plain_local'),
            'url' => 'http://localhost/storage',
        ],
        'media-library.disk_name' => 'plain_local',
    ]);

    $organization = createOrganizationWithLocation();

    $pngBytes = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
    );

    $organization
        ->addMediaFromString($pngBytes)
        ->usingFileName('logo.png')
        ->toMediaCollection('logo');

    $organization = $organization->fresh();

    expect(Storage::disk('plain_local')->providesTemporaryUrls())->toBeFalse()
        ->and($organization->logo_url)->not->toBeNull()
        ->and($organization->logo_thumb_url)->not->toBeNull();
});

test('logoBase64 returns null when no logo uploaded', function () {
    $organization = createOrganizationWithLocation();

    expect($organization->logo_base64)->toBeNull();
});

test('logoBase64 reads from configured media disk (not local filesystem)', function () {
    config(['media-library.disk_name' => 'public']);
    Storage::fake('public');

    $organization = createOrganizationWithLocation();

    $pngBytes = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
    );

    $organization
        ->addMediaFromString($pngBytes)
        ->usingFileName('logo.png')
        ->toMediaCollection('logo');

    $base64 = $organization->fresh()->logo_base64;

    expect($base64)->toStartWith('data:image/png;base64,')
        ->and(base64_decode(substr($base64, strlen('data:image/png;base64,'))))->toBe($pngBytes);
});
