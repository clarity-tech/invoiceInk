<?php

namespace App\Services;

use App\Enums\Country;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\TaxTemplate;
use Illuminate\Database\Eloquent\Builder;

class TaxTemplateProvisioner
{
    /**
     * Create the country's default tax templates for the organization when it
     * has none yet. Safe to call repeatedly (idempotent).
     */
    public function seedDefaultsIfMissing(Organization $organization, Country $country): void
    {
        if ($this->query($organization)->exists()) {
            return;
        }

        $this->createDefaults($organization, $country);
    }

    /**
     * Replace the organization's auto-generated tax templates with the defaults
     * for the given country.
     *
     * Safe to call destructively: existing invoices store the tax rate value on
     * each line item rather than a template reference, so removing templates
     * never alters past documents.
     */
    public function replaceWithCountryDefaults(Organization $organization, Country $country): void
    {
        $this->query($organization)->delete();

        $this->createDefaults($organization, $country);
    }

    private function createDefaults(Organization $organization, Country $country): void
    {
        foreach ($country->getDefaultTaxTemplates() as $taxData) {
            TaxTemplate::create([
                'organization_id' => $organization->id,
                'name' => $taxData['name'],
                'type' => $taxData['type'],
                'rate' => $taxData['rate'],
                'category' => $taxData['category'],
                'country_code' => $country->value,
                'description' => $taxData['description'],
                'is_active' => true,
            ]);
        }
    }

    /**
     * Query the organization's tax templates without the global organization
     * scope, so provisioning works regardless of the current team context
     * (seeders, queued jobs, owners editing a non-active organization).
     */
    private function query(Organization $organization): Builder
    {
        return TaxTemplate::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organization->id);
    }
}
