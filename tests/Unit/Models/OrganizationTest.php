<?php

use App\Currency;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Organization;
use App\Models\TaxTemplate;
use App\Models\User;
use App\ValueObjects\EmailCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create organization with required fields', function () {
    $user = User::factory()->create();

    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'personal_team' => false,
        'company_name' => 'Test Company Inc.',
        'currency' => 'INR',
    ]);

    expect($organization)->toBeInstanceOf(Organization::class);
    expect($organization->name)->toBe('Test Organization');
    expect($organization->company_name)->toBe('Test Company Inc.');
    expect($organization->currency)->toBe(Currency::INR);
    expect($organization->personal_team)->toBeFalse();
});

test('organization extends jetstream team', function () {
    $organization = new Organization;
    expect($organization)->toBeInstanceOf(\Laravel\Jetstream\Team::class);
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
        'personal_team',
        'company_name',
        'tax_number',
        'registration_number',
        'emails',
        'phone',
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

test('organization emails are cast to EmailCollection', function () {
    $user = User::factory()->create();
    $emails = ['test1@example.com', 'test2@example.com'];
    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'personal_team' => false,
        'emails' => $emails,
        'currency' => 'INR',
    ]);

    expect($organization->emails)->toBeInstanceOf(EmailCollection::class);
    expect($organization->emails->toArray())->toBe($emails);
});

test('organization currency is cast to Currency enum', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'personal_team' => false,
        'currency' => 'USD',
    ]);

    expect($organization->currency)->toBe(Currency::USD);
    expect($organization->currency->value)->toBe('USD');
});

test('organization personal_team is cast to boolean', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'personal_team' => 1,
        'currency' => 'INR',
    ]);

    expect($organization->personal_team)->toBeTrue();
    expect($organization->personal_team)->toBeBool();
});

test('organization can have primary location relationship', function () {
    $location = Location::create([
        'name' => 'HQ Location',
        'address_line_1' => '123 Business St',
        'city' => 'Business City',
        'state' => 'Business State',
        'country' => 'Business Country',
        'postal_code' => '12345',
        'locatable_type' => Organization::class,
        'locatable_id' => 1,
    ]);

    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'personal_team' => false,
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
        'emails' => new EmailCollection(['customer1@test.com']),
        'organization_id' => $organization->id,
    ]);

    $customer2 = Customer::create([
        'name' => 'Customer 2',
        'emails' => new EmailCollection(['customer2@test.com']),
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
    expect($organization->invoices->pluck('invoice_number')->toArray())->toContain('INV-001');
    expect($organization->invoices->pluck('invoice_number')->toArray())->toContain('INV-002');
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
        'personal_team' => false,
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
        'personal_team' => false,
        'currency' => 'INR',
    ]);

    expect($organization->url)->toBe("https://clarity-invoicing.com/organizations/{$organization->id}");
});

test('organization getDisplayNameAttribute uses company name when available', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Team Name',
        'user_id' => $user->id,
        'personal_team' => false,
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
        'personal_team' => false,
        'currency' => 'INR',
    ]);

    expect($organization->display_name)->toBe('Team Name');
});

test('organization isBusinessOrganization returns true for business organizations', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Business Team',
        'user_id' => $user->id,
        'personal_team' => false,
        'company_name' => 'Business Corp.',
        'currency' => 'INR',
    ]);

    expect($organization->isBusinessOrganization())->toBeTrue();
});

test('organization isBusinessOrganization returns false for personal teams', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Personal Team',
        'user_id' => $user->id,
        'personal_team' => true,
        'company_name' => 'Personal Corp.',
        'currency' => 'INR',
    ]);

    expect($organization->isBusinessOrganization())->toBeFalse();
});

test('organization isBusinessOrganization returns false when no company name', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Team Name',
        'user_id' => $user->id,
        'personal_team' => false,
        'currency' => 'INR',
    ]);

    expect($organization->isBusinessOrganization())->toBeFalse();
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
            'personal_team' => false,
            'currency' => $currency,
        ]);

        expect($organization->currency_symbol)->toBe($expectedSymbol);
    }
});

test('organization can be created with all fillable attributes', function () {
    $user = User::factory()->create();
    $emails = new EmailCollection(['test@example.com', 'info@example.com']);

    $organization = Organization::create([
        'name' => 'Complete Organization',
        'user_id' => $user->id,
        'personal_team' => false,
        'company_name' => 'Complete Corp.',
        'tax_number' => 'TAX123456789',
        'registration_number' => 'REG987654321',
        'emails' => $emails,
        'phone' => '+1-555-0123',
        'website' => 'https://example.com',
        'currency' => 'EUR',
        'notes' => 'Test notes for organization',
        'custom_domain' => 'custom.example.com',
    ]);

    expect($organization->name)->toBe('Complete Organization');
    expect($organization->personal_team)->toBeFalse();
    expect($organization->company_name)->toBe('Complete Corp.');
    expect($organization->tax_number)->toBe('TAX123456789');
    expect($organization->registration_number)->toBe('REG987654321');
    expect($organization->emails)->toBeInstanceOf(EmailCollection::class);
    expect($organization->emails->count())->toBe(2);
    expect($organization->phone)->toBe('+1-555-0123');
    expect($organization->website)->toBe('https://example.com');
    expect($organization->currency)->toBe(Currency::EUR);
    expect($organization->notes)->toBe('Test notes for organization');
    expect($organization->custom_domain)->toBe('custom.example.com');
});

test('organization handles empty emails collection', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'personal_team' => false,
        'emails' => new EmailCollection([]),
        'currency' => 'INR',
    ]);

    expect($organization->emails)->toBeInstanceOf(EmailCollection::class);
    expect($organization->emails->isEmpty())->toBeTrue();
});

test('organization emails cast handles array input', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'personal_team' => false,
        'emails' => ['test1@example.com', 'test2@example.com'],
        'currency' => 'INR',
    ]);

    expect($organization->emails)->toBeInstanceOf(EmailCollection::class);
    expect($organization->emails->count())->toBe(2);
});

test('organization emails cast handles string input', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'personal_team' => false,
        'emails' => 'single@example.com',
        'currency' => 'INR',
    ]);

    expect($organization->emails)->toBeInstanceOf(EmailCollection::class);
    expect($organization->emails->count())->toBe(1);
    expect($organization->emails->first())->toBe('single@example.com');
});

test('organization emails cast handles null input', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Test Organization',
        'user_id' => $user->id,
        'personal_team' => false,
        'emails' => null,
        'currency' => 'INR',
    ]);

    expect($organization->emails)->toBeInstanceOf(EmailCollection::class);
    expect($organization->emails->isEmpty())->toBeTrue();
});

test('organization casts method returns correct array', function () {
    $organization = new Organization;
    $casts = $organization->getCasts();

    expect($casts['personal_team'])->toBe('boolean');
    expect($casts['emails'])->toBe(\App\Casts\EmailCollectionCast::class);
    expect($casts['currency'])->toBe(\App\Currency::class);
});

test('organization dispatches jetstream events', function () {
    $organization = new Organization;
    $reflectionClass = new \ReflectionClass($organization);
    $property = $reflectionClass->getProperty('dispatchesEvents');
    $property->setAccessible(true);
    $events = $property->getValue($organization);

    expect($events['created'])->toBe(\Laravel\Jetstream\Events\TeamCreated::class);
    expect($events['updated'])->toBe(\Laravel\Jetstream\Events\TeamUpdated::class);
    expect($events['deleted'])->toBe(\Laravel\Jetstream\Events\TeamDeleted::class);
});

test('organization uses HasFactory trait', function () {
    $organization = new Organization;
    expect(in_array(\Illuminate\Database\Eloquent\Factories\HasFactory::class, class_uses($organization)))->toBeTrue();
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
    $invitationModel = \Laravel\Jetstream\Jetstream::teamInvitationModel();
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
        'personal_team' => false,
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
        'personal_team' => false,
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
    expect($primaryLocationRelation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);

    // Test customers relationship
    $customersRelation = $organization->customers();
    expect($customersRelation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);

    // Test invoices relationship
    $invoicesRelation = $organization->invoices();
    expect($invoicesRelation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);

    // Test taxTemplates relationship
    $taxTemplatesRelation = $organization->taxTemplates();
    expect($taxTemplatesRelation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('organization currency enum integration works correctly', function () {
    foreach (Currency::cases() as $currency) {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => "Test Organization {$currency->value}",
            'user_id' => $user->id,
            'personal_team' => false,
            'currency' => $currency->value,
        ]);

        expect($organization->currency)->toBe($currency);
        expect($organization->currency->value)->toBe($currency->value);
        expect($organization->currency->symbol())->toBe($currency->symbol());
        expect($organization->currency->name())->toBe($currency->name());
    }
});
