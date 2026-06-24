<?php

use App\Enums\Country;
use App\Models\Scopes\OrganizationScope;
use App\Models\TaxTemplate;
use App\Services\TaxTemplateProvisioner;

function provisionerTemplates($organization)
{
    return TaxTemplate::withoutGlobalScope(OrganizationScope::class)
        ->where('organization_id', $organization->id)
        ->get();
}

beforeEach(function () {
    $this->provisioner = app(TaxTemplateProvisioner::class);
});

test('seedDefaultsIfMissing creates the country defaults when the org has none', function () {
    $organization = createOrganizationWithLocation(['country_code' => 'IN']);

    $this->provisioner->seedDefaultsIfMissing($organization, Country::IN);

    $templates = provisionerTemplates($organization);
    expect($templates)->toHaveCount(count(Country::IN->getDefaultTaxTemplates()));
    expect($templates->firstWhere('name', 'GST 18%')->rate)->toBe(1800);
});

test('seedDefaultsIfMissing is idempotent and does not duplicate templates', function () {
    $organization = createOrganizationWithLocation(['country_code' => 'IN']);

    $this->provisioner->seedDefaultsIfMissing($organization, Country::IN);
    $this->provisioner->seedDefaultsIfMissing($organization, Country::IN);

    expect(provisionerTemplates($organization))
        ->toHaveCount(count(Country::IN->getDefaultTaxTemplates()));
});

test('replaceWithCountryDefaults swaps the old country templates for the new ones', function () {
    $organization = createOrganizationWithLocation(['country_code' => 'IN']);
    $this->provisioner->seedDefaultsIfMissing($organization, Country::IN);

    $this->provisioner->replaceWithCountryDefaults($organization, Country::GB);

    $templates = provisionerTemplates($organization);
    expect($templates->pluck('country_code')->unique()->all())->toBe(['GB']);
    expect($templates->pluck('name'))->not->toContain('GST 18%');
    expect($templates->firstWhere('name', 'VAT 20%')->rate)->toBe(2000);
});
