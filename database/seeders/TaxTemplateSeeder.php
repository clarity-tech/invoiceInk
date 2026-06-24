<?php

namespace Database\Seeders;

use App\Enums\Country;
use App\Models\Organization;
use App\Services\TaxTemplateProvisioner;
use Illuminate\Database\Seeder;

class TaxTemplateSeeder extends Seeder
{
    public function __construct(private TaxTemplateProvisioner $taxTemplates) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating tax templates for all organizations...');

        Organization::all()->each(
            fn (Organization $organization) => $this->createTaxTemplatesForOrganization($organization)
        );

        $this->command->info('Tax templates created successfully!');
    }

    private function createTaxTemplatesForOrganization(Organization $organization): void
    {
        $country = $this->resolveCountry($organization);

        if (! $country) {
            return;
        }

        $this->taxTemplates->seedDefaultsIfMissing($organization, $country);
    }

    /**
     * Resolve the organization's country, falling back to the country whose
     * default currency matches the organization currency.
     */
    private function resolveCountry(Organization $organization): ?Country
    {
        if ($organization->country_code instanceof Country) {
            return $organization->country_code;
        }

        return collect(Country::cases())
            ->first(fn (Country $country) => $country->getDefaultCurrency() === $organization->currency);
    }
}
