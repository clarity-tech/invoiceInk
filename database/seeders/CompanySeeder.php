<?php

namespace Database\Seeders;

use App\Currency;
use App\Models\Company;
use App\Models\Location;
use App\Models\Team;
use App\Models\User;
use App\ValueObjects\EmailCollection;

class CompanySeeder extends ProductionSafeSeeder
{
    protected function seed(): void
    {
        $this->info('Seeding companies with locations...');

        // Get teams created by UserSeeder
        $teams = $this->getTeamsForSeeding();

        foreach ($teams as $teamData) {
            $this->createCompaniesForTeam($teamData['team'], $teamData['companies']);
        }

        $this->info('Created companies and locations successfully!');
    }

    private function getTeamsForSeeding(): array
    {
        return [
            [
                'team' => Team::where('name', 'ACME Manufacturing Corp')->first(),
                'companies' => [
                    [
                        'name' => 'ACME Manufacturing Corp',
                        'currency' => Currency::USD,
                        'emails' => ['billing@acmecorp.com', 'accounts@acmecorp.com'],
                        'phone' => '+1-555-0123',
                        'location' => [
                            'name' => 'ACME Manufacturing HQ',
                            'address_line_1' => '1234 Industrial Blvd',
                            'address_line_2' => 'Suite 500',
                            'city' => 'Detroit',
                            'state' => 'Michigan',
                            'country' => 'United States',
                            'postal_code' => '48201',
                        ],
                    ],
                ],
            ],
            [
                'team' => Team::where('name', 'TechStart Innovation Hub')->first(),
                'companies' => [
                    [
                        'name' => 'TechStart Innovation Hub',
                        'currency' => Currency::USD,
                        'emails' => ['hello@techstartup.com', 'billing@techstartup.com'],
                        'phone' => '+1-415-555-0199',
                        'location' => [
                            'name' => 'TechStart HQ',
                            'address_line_1' => '555 Market Street',
                            'address_line_2' => 'Floor 15',
                            'city' => 'San Francisco',
                            'state' => 'California',
                            'country' => 'United States',
                            'postal_code' => '94105',
                        ],
                    ],
                ],
            ],
            [
                'team' => Team::where('name', 'EuroConsult GmbH')->first(),
                'companies' => [
                    [
                        'name' => 'EuroConsult GmbH',
                        'currency' => Currency::EUR,
                        'emails' => ['info@euroconsult.de', 'rechnung@euroconsult.de'],
                        'phone' => '+49-30-12345678',
                        'location' => [
                            'name' => 'EuroConsult Berlin Office',
                            'address_line_1' => 'Unter den Linden 77',
                            'address_line_2' => null,
                            'city' => 'Berlin',
                            'state' => 'Berlin',
                            'country' => 'Germany',
                            'postal_code' => '10117',
                        ],
                    ],
                ],
            ],
            [
                'team' => Team::where('name', 'Demo Company Ltd')->first(),
                'companies' => [
                    [
                        'name' => 'Demo Company Ltd',
                        'currency' => Currency::INR,
                        'emails' => ['demo@invoicing.claritytech.io', 'billing@democompany.com'],
                        'phone' => '+91-22-12345678',
                        'location' => [
                            'name' => 'Demo Company HQ',
                            'address_line_1' => 'Demo Tower, 123 Business District',
                            'address_line_2' => 'Floor 10',
                            'city' => 'Mumbai',
                            'state' => 'Maharashtra',
                            'country' => 'India',
                            'postal_code' => '400001',
                        ],
                    ],
                ],
            ],
            [
                'team' => Team::where('name', 'GlobalCorp Holdings')->first(),
                'companies' => [
                    [
                        'name' => 'GlobalCorp Holdings Inc',
                        'currency' => Currency::USD,
                        'emails' => ['corporate@globalcorp.com', 'finance@globalcorp.com'],
                        'phone' => '+1-212-555-1000',
                        'location' => [
                            'name' => 'GlobalCorp Tower',
                            'address_line_1' => '200 Park Avenue',
                            'address_line_2' => 'Suite 4000',
                            'city' => 'New York',
                            'state' => 'New York',
                            'country' => 'United States',
                            'postal_code' => '10166',
                        ],
                    ],
                ],
            ],
            [
                'team' => Team::where('name', 'GlobalCorp Tech Solutions')->first(),
                'companies' => [
                    [
                        'name' => 'GlobalCorp Tech Solutions',
                        'currency' => Currency::INR,
                        'emails' => ['tech@globalcorp.com', 'billing.tech@globalcorp.com'],
                        'phone' => '+91-80-12345678',
                        'location' => [
                            'name' => 'GlobalCorp Tech Campus',
                            'address_line_1' => 'Electronic City Phase 1',
                            'address_line_2' => 'Plot 123, Sector 5',
                            'city' => 'Bangalore',
                            'state' => 'Karnataka',
                            'country' => 'India',
                            'postal_code' => '560100',
                        ],
                    ],
                ],
            ],
            [
                'team' => Team::where('name', 'GlobalCorp Business Services')->first(),
                'companies' => [
                    [
                        'name' => 'GlobalCorp Business Services Ltd',
                        'currency' => Currency::GBP,
                        'emails' => ['services@globalcorp.com', 'billing.uk@globalcorp.com'],
                        'phone' => '+44-20-7123-4567',
                        'location' => [
                            'name' => 'GlobalCorp London Office',
                            'address_line_1' => '1 Canary Wharf',
                            'address_line_2' => 'Level 25',
                            'city' => 'London',
                            'state' => 'England',
                            'country' => 'United Kingdom',
                            'postal_code' => 'E14 5AB',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function createCompaniesForTeam(Team $team, array $companiesData): void
    {
        if (!$team) {
            $this->warn('Team not found, skipping...');
            return;
        }

        foreach ($companiesData as $companyData) {
            $this->createCompanyWithLocation($team, $companyData);
        }
    }

    private function createCompanyWithLocation(Team $team, array $companyData): Company
    {
        // Create the location first
        $locationData = $companyData['location'];
        $location = Location::create([
            'name' => $locationData['name'],
            'address_line_1' => $locationData['address_line_1'],
            'address_line_2' => $locationData['address_line_2'],
            'city' => $locationData['city'],
            'state' => $locationData['state'],
            'country' => $locationData['country'],
            'postal_code' => $locationData['postal_code'],
            'locatable_type' => Company::class,
            'locatable_id' => 1, // Temporary, will be updated
        ]);

        // Create the company
        $company = Company::create([
            'name' => $companyData['name'],
            'emails' => new EmailCollection($companyData['emails']),
            'phone' => $companyData['phone'] ?? null,
            'primary_location_id' => $location->id,
            'team_id' => $team->id,
            'currency' => $companyData['currency']->value,
        ]);

        // Update location with correct company ID
        $location->update(['locatable_id' => $company->id]);

        // Create additional locations for some companies
        if (in_array($company->name, ['ACME Manufacturing Corp', 'GlobalCorp Holdings Inc'])) {
            $this->createAdditionalLocations($company);
        }

        $this->info("Created company: {$company->name} ({$company->currency->value})");

        return $company->fresh(['primaryLocation']);
    }

    private function createAdditionalLocations(Company $company): void
    {
        if ($company->name === 'ACME Manufacturing Corp') {
            // Manufacturing plant
            Location::create([
                'name' => 'ACME Manufacturing Plant',
                'address_line_1' => '5678 Factory Road',
                'address_line_2' => null,
                'city' => 'Toledo',
                'state' => 'Ohio',
                'country' => 'United States',
                'postal_code' => '43604',
                'locatable_type' => Company::class,
                'locatable_id' => $company->id,
            ]);

            // Warehouse
            Location::create([
                'name' => 'ACME Distribution Center',
                'address_line_1' => '9876 Logistics Way',
                'address_line_2' => 'Building B',
                'city' => 'Chicago',
                'state' => 'Illinois',
                'country' => 'United States',
                'postal_code' => '60601',
                'locatable_type' => Company::class,
                'locatable_id' => $company->id,
            ]);
        }

        if ($company->name === 'GlobalCorp Holdings Inc') {
            // Regional office
            Location::create([
                'name' => 'GlobalCorp West Coast Office',
                'address_line_1' => '999 California Street',
                'address_line_2' => 'Suite 2000',
                'city' => 'San Francisco',
                'state' => 'California',
                'country' => 'United States',
                'postal_code' => '94108',
                'locatable_type' => Company::class,
                'locatable_id' => $company->id,
            ]);
        }
    }
}