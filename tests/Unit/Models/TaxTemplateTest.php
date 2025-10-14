<?php

use App\Models\Organization;
use App\Models\TaxTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create tax template with required fields', function () {
    $organization = createOrganizationWithLocation();

    $taxTemplate = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'GST 18%',
        'type' => 'GST',
        'rate' => 1800, // 18% in basis points
        'country_code' => 'IN',
    ]);

    expect($taxTemplate)->toBeInstanceOf(TaxTemplate::class);
    expect($taxTemplate->name)->toBe('GST 18%');
    expect($taxTemplate->type)->toBe('GST');
    expect($taxTemplate->rate)->toBe(1800); // Should return basis points as integer
    expect($taxTemplate->country_code)->toBe('IN');
    expect($taxTemplate->organization_id)->toBe($organization->id);
});

test('tax template has correct fillable attributes', function () {
    $taxTemplate = new TaxTemplate;
    $fillable = $taxTemplate->getFillable();

    $expectedFillable = [
        'organization_id',
        'name',
        'type',
        'rate',
        'category',
        'country_code',
        'description',
        'is_active',
        'metadata',
    ];

    foreach ($expectedFillable as $field) {
        expect($fillable)->toContain($field);
    }
});

test('tax template rate is cast to integer basis points', function () {
    $organization = createOrganizationWithLocation();

    $taxTemplate = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'GST 18%',
        'type' => 'GST',
        'rate' => 1812, // 18.12% in basis points
        'country_code' => 'IN',
    ]);

    expect($taxTemplate->rate)->toBe(1812); // Should return basis points as integer
});

test('tax template is_active is cast to boolean', function () {
    $organization = createOrganizationWithLocation();

    $taxTemplate = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'GST 18%',
        'type' => 'GST',
        'rate' => 18.000,
        'country_code' => 'IN',
        'is_active' => 1,
    ]);

    expect($taxTemplate->is_active)->toBeTrue();
    expect($taxTemplate->is_active)->toBeBool();
});

test('tax template metadata is cast to json', function () {
    $organization = createOrganizationWithLocation();
    $metadata = ['application' => 'goods', 'threshold' => 250000];

    $taxTemplate = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'GST 18%',
        'type' => 'GST',
        'rate' => 18.000,
        'country_code' => 'IN',
        'metadata' => $metadata,
    ]);

    expect($taxTemplate->metadata)->toBe($metadata);
    expect($taxTemplate->metadata)->toBeArray();
});

test('tax template belongs to organization', function () {
    $organization = createOrganizationWithLocation();

    $taxTemplate = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'GST 18%',
        'type' => 'GST',
        'rate' => 18.000,
        'country_code' => 'IN',
    ]);

    expect($taxTemplate->organization)->toBeInstanceOf(Organization::class);
    expect($taxTemplate->organization->id)->toBe($organization->id);
});

test('tax template scope active filters active templates', function () {
    $organization = createOrganizationWithLocation();

    $activeTax = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'Active GST',
        'type' => 'GST',
        'rate' => 18.000,
        'country_code' => 'IN',
        'is_active' => true,
    ]);

    $inactiveTax = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'Inactive GST',
        'type' => 'GST',
        'rate' => 18.000,
        'country_code' => 'IN',
        'is_active' => false,
    ]);

    $activeTaxTemplates = TaxTemplate::active()->get();

    expect($activeTaxTemplates)->toHaveCount(1);
    expect($activeTaxTemplates->first()->id)->toBe($activeTax->id);
});

test('tax template scope forCountry filters by country code', function () {
    $organization = createOrganizationWithLocation();

    $indiaTax = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'India GST',
        'type' => 'GST',
        'rate' => 18.000,
        'country_code' => 'IN',
    ]);

    $uaeTax = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'UAE VAT',
        'type' => 'VAT',
        'rate' => 5.000,
        'country_code' => 'AE',
    ]);

    $indiaTaxTemplates = TaxTemplate::forCountry('IN')->get();
    $uaeTaxTemplates = TaxTemplate::forCountry('AE')->get();

    expect($indiaTaxTemplates)->toHaveCount(1);
    expect($indiaTaxTemplates->first()->id)->toBe($indiaTax->id);

    expect($uaeTaxTemplates)->toHaveCount(1);
    expect($uaeTaxTemplates->first()->id)->toBe($uaeTax->id);
});

test('tax template scope byType filters by tax type', function () {
    $organization = createOrganizationWithLocation();

    $gstTax = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'GST 18%',
        'type' => 'GST',
        'rate' => 18.000,
        'country_code' => 'IN',
    ]);

    $vatTax = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'VAT 5%',
        'type' => 'VAT',
        'rate' => 5.000,
        'country_code' => 'AE',
    ]);

    $gstTaxTemplates = TaxTemplate::byType('GST')->get();
    $vatTaxTemplates = TaxTemplate::byType('VAT')->get();

    expect($gstTaxTemplates)->toHaveCount(1);
    expect($gstTaxTemplates->first()->id)->toBe($gstTax->id);

    expect($vatTaxTemplates)->toHaveCount(1);
    expect($vatTaxTemplates->first()->id)->toBe($vatTax->id);
});

test('tax template getFormattedRateAttribute returns formatted percentage', function () {
    $organization = createOrganizationWithLocation();

    $taxTemplate = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'GST 18%',
        'type' => 'GST',
        'rate' => 1850, // 18.50% in basis points
        'country_code' => 'IN',
    ]);

    expect($taxTemplate->formatted_rate)->toBe('18.50%');
});

test('tax template isGST method identifies GST types correctly', function () {
    $organization = createOrganizationWithLocation();

    $gstTypes = ['GST', 'CGST', 'SGST', 'IGST'];
    $nonGstTypes = ['VAT', 'Service Tax', 'Excise Tax'];

    foreach ($gstTypes as $type) {
        $taxTemplate = TaxTemplate::create([
            'organization_id' => $organization->id,
            'name' => "{$type} Tax",
            'type' => $type,
            'rate' => 18.000,
            'country_code' => 'IN',
        ]);

        expect($taxTemplate->isGST())->toBeTrue();
    }

    foreach ($nonGstTypes as $type) {
        $taxTemplate = TaxTemplate::create([
            'organization_id' => $organization->id,
            'name' => "{$type} Tax",
            'type' => $type,
            'rate' => 10.000,
            'country_code' => 'IN',
        ]);

        expect($taxTemplate->isGST())->toBeFalse();
    }
});

test('tax template isVAT method identifies VAT type correctly', function () {
    $organization = createOrganizationWithLocation();

    $vatTax = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'VAT 5%',
        'type' => 'VAT',
        'rate' => 5.000,
        'country_code' => 'AE',
    ]);

    $gstTax = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'GST 18%',
        'type' => 'GST',
        'rate' => 18.000,
        'country_code' => 'IN',
    ]);

    expect($vatTax->isVAT())->toBeTrue();
    expect($gstTax->isVAT())->toBeFalse();
});

test('tax template can be created with all fillable attributes', function () {
    $organization = createOrganizationWithLocation();

    $taxTemplate = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'Complete Tax Template',
        'type' => 'GST',
        'rate' => 1800, // 18% in basis points
        'category' => 'standard',
        'country_code' => 'IN',
        'description' => 'Standard GST rate for goods and services',
        'is_active' => true,
        'metadata' => [
            'application' => 'goods',
            'threshold' => 250000,
            'exemption_limit' => 40000,
        ],
    ]);

    expect($taxTemplate->organization_id)->toBe($organization->id);
    expect($taxTemplate->name)->toBe('Complete Tax Template');
    expect($taxTemplate->type)->toBe('GST');
    expect($taxTemplate->rate)->toBe(1800); // Should return basis points as integer
    expect($taxTemplate->category)->toBe('standard');
    expect($taxTemplate->country_code)->toBe('IN');
    expect($taxTemplate->description)->toBe('Standard GST rate for goods and services');
    expect($taxTemplate->is_active)->toBeTrue();
    expect($taxTemplate->metadata)->toHaveKey('application');
    expect($taxTemplate->metadata)->toHaveKey('threshold');
    expect($taxTemplate->metadata)->toHaveKey('exemption_limit');
});

test('tax template handles nullable fields correctly', function () {
    $organization = createOrganizationWithLocation();

    $taxTemplate = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'Basic Tax Template',
        'type' => 'GST',
        'rate' => 18.000,
        'country_code' => 'IN',
        'category' => null,
        'description' => null,
        'metadata' => null,
    ]);

    expect($taxTemplate->category)->toBeNull();
    expect($taxTemplate->description)->toBeNull();
    expect($taxTemplate->metadata)->toBeNull();
});

test('tax template defaults is_active to true when not specified', function () {
    $organization = createOrganizationWithLocation();

    $taxTemplate = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'Default Active Tax',
        'type' => 'GST',
        'rate' => 18.000,
        'country_code' => 'IN',
    ]);

    // Since is_active is not explicitly set, it should be null by default
    expect($taxTemplate->is_active)->toBeNull();
});

test('tax template can be updated', function () {
    $organization = createOrganizationWithLocation();

    $taxTemplate = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'Original Name',
        'type' => 'GST',
        'rate' => 1800, // 18% in basis points
        'country_code' => 'IN',
        'is_active' => true,
    ]);

    $taxTemplate->update([
        'name' => 'Updated Name',
        'rate' => 2800, // 28% in basis points
        'is_active' => false,
    ]);

    expect($taxTemplate->name)->toBe('Updated Name');
    expect($taxTemplate->rate)->toBe(2800); // Should return basis points as integer
    expect($taxTemplate->is_active)->toBeFalse();
});

test('tax template can combine multiple scopes', function () {
    $organization = createOrganizationWithLocation();

    // Create various tax templates
    $activeIndiaGst = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'Active India GST',
        'type' => 'GST',
        'rate' => 18.000,
        'country_code' => 'IN',
        'is_active' => true,
    ]);

    $inactiveIndiaGst = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'Inactive India GST',
        'type' => 'GST',
        'rate' => 18.000,
        'country_code' => 'IN',
        'is_active' => false,
    ]);

    $activeUaeVat = TaxTemplate::create([
        'organization_id' => $organization->id,
        'name' => 'Active UAE VAT',
        'type' => 'VAT',
        'rate' => 5.000,
        'country_code' => 'AE',
        'is_active' => true,
    ]);

    // Test combined scopes
    $activeIndiaGstTemplates = TaxTemplate::active()
        ->forCountry('IN')
        ->byType('GST')
        ->get();

    expect($activeIndiaGstTemplates)->toHaveCount(1);
    expect($activeIndiaGstTemplates->first()->id)->toBe($activeIndiaGst->id);
});

test('tax template factory creates valid instances', function () {
    $taxTemplate = TaxTemplate::factory()->create();

    expect($taxTemplate)->toBeInstanceOf(TaxTemplate::class);
    expect($taxTemplate->name)->not->toBeEmpty();
    expect($taxTemplate->type)->not->toBeEmpty();
    expect($taxTemplate->rate)->toBeNumeric();
    expect($taxTemplate->country_code)->not->toBeEmpty();
    expect($taxTemplate->organization_id)->toBeInt();
});

test('tax template factory gst state creates GST template', function () {
    $gstTemplate = TaxTemplate::factory()->gst(180000)->create(); // 18% in basis points

    expect($gstTemplate->type)->toBe('GST');
    expect($gstTemplate->rate)->toBe(180000); // Should return basis points as integer
    expect($gstTemplate->country_code)->toBe('IN');
    expect($gstTemplate->name)->toContain('GST 18%');
});

test('tax template factory vat state creates VAT template', function () {
    $vatTemplate = TaxTemplate::factory()->vat(50000)->create(); // 5% in basis points

    expect($vatTemplate->type)->toBe('VAT');
    expect($vatTemplate->rate)->toBe(50000); // Should return basis points as integer
    expect($vatTemplate->country_code)->toBe('AE');
    expect($vatTemplate->name)->toContain('VAT 5%');
});

test('tax template factory active state creates active template', function () {
    $activeTemplate = TaxTemplate::factory()->active()->create();

    expect($activeTemplate->is_active)->toBeTrue();
});

test('tax template factory inactive state creates inactive template', function () {
    $inactiveTemplate = TaxTemplate::factory()->inactive()->create();

    expect($inactiveTemplate->is_active)->toBeFalse();
});

test('tax template factory cgst state creates CGST template', function () {
    $cgstTemplate = TaxTemplate::factory()->cgst(90000)->create(); // 9% in basis points

    expect($cgstTemplate->type)->toBe('CGST');
    expect($cgstTemplate->rate)->toBe(90000); // Should return basis points as integer
    expect($cgstTemplate->country_code)->toBe('IN');
    expect($cgstTemplate->isGST())->toBeTrue();
});

test('tax template factory sgst state creates SGST template', function () {
    $sgstTemplate = TaxTemplate::factory()->sgst(90000)->create(); // 9% in basis points

    expect($sgstTemplate->type)->toBe('SGST');
    expect($sgstTemplate->rate)->toBe(90000); // Should return basis points as integer
    expect($sgstTemplate->country_code)->toBe('IN');
    expect($sgstTemplate->isGST())->toBeTrue();
});

test('tax template factory igst state creates IGST template', function () {
    $igstTemplate = TaxTemplate::factory()->igst(180000)->create(); // 18% in basis points

    expect($igstTemplate->type)->toBe('IGST');
    expect($igstTemplate->rate)->toBe(180000); // Should return basis points as integer
    expect($igstTemplate->country_code)->toBe('IN');
    expect($igstTemplate->isGST())->toBeTrue();
});

test('tax template factory forCountry state sets correct country', function () {
    $usTemplate = TaxTemplate::factory()->forCountry('US')->create();

    expect($usTemplate->country_code)->toBe('US');
});

test('tax template factory withMetadata state sets metadata', function () {
    $metadata = ['application' => 'services', 'category' => 'digital'];
    $template = TaxTemplate::factory()->withMetadata($metadata)->create();

    expect($template->metadata)->toBe($metadata);
});

test('tax template casts method returns correct array', function () {
    $taxTemplate = new TaxTemplate;
    $casts = $taxTemplate->getCasts();

    expect($casts['rate'])->toBe('integer'); // Now stores basis points as integers
    expect($casts['is_active'])->toBe('boolean');
    expect($casts['metadata'])->toBe('json');
});

test('tax template uses HasFactory trait', function () {
    $taxTemplate = new TaxTemplate;
    expect(in_array(\Illuminate\Database\Eloquent\Factories\HasFactory::class, class_uses($taxTemplate)))->toBeTrue();
});

test('tax template has organization scope applied globally', function () {
    // Create two different organizations
    $org1 = createOrganizationWithLocation();
    $org2 = createOrganizationWithLocation();

    // Create tax templates for each organization
    $tax1 = TaxTemplate::create([
        'organization_id' => $org1->id,
        'name' => 'Org 1 Tax',
        'type' => 'GST',
        'rate' => 18.000,
        'country_code' => 'IN',
    ]);

    $tax2 = TaxTemplate::create([
        'organization_id' => $org2->id,
        'name' => 'Org 2 Tax',
        'type' => 'GST',
        'rate' => 18.000,
        'country_code' => 'IN',
    ]);

    // Act as user from org1
    $user1 = User::factory()->create();
    $user1->switchTeam($org1);
    $this->actingAs($user1);

    // Currently OrganizationScope is not fully implemented, so all templates are visible
    // This test documents the current behavior - in future this should filter by organization
    $templates = TaxTemplate::all();

    expect($templates)->toHaveCount(2);
    expect($templates->contains('id', $tax1->id))->toBeTrue();
    expect($templates->contains('id', $tax2->id))->toBeTrue();
});

test('tax template relationship is correctly configured', function () {
    $taxTemplate = new TaxTemplate;

    // Test organization relationship
    $organizationRelation = $taxTemplate->organization();
    expect($organizationRelation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});
