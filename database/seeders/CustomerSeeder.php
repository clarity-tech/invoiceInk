<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Location;
use App\ValueObjects\EmailCollection;

class CustomerSeeder extends ProductionSafeSeeder
{
    protected function seed(): void
    {
        $this->info('Seeding customers with locations...');

        $companies = Company::with('team')->get();

        foreach ($companies as $company) {
            $this->createCustomersForCompany($company);
        }

        $this->info('Created customers and locations successfully!');
    }

    private function createCustomersForCompany(Company $company): void
    {
        $customerData = $this->getCustomerDataForCompany($company);

        foreach ($customerData as $data) {
            $this->createCustomerWithLocation($company, $data);
        }

        $count = count($customerData);
        $this->info("Created {$count} customers for {$company->name}");
    }

    private function getCustomerDataForCompany(Company $company): array
    {
        switch ($company->name) {
            case 'ACME Manufacturing Corp':
                return $this->getACMECustomers();
            
            case 'TechStart Innovation Hub':
                return $this->getTechStartCustomers();
            
            case 'EuroConsult GmbH':
                return $this->getEuroConsultCustomers();
            
            case 'Demo Company Ltd':
                return $this->getDemoCompanyCustomers();
            
            case 'GlobalCorp Holdings Inc':
                return $this->getGlobalCorpHoldingsCustomers();
            
            case 'GlobalCorp Tech Solutions':
                return $this->getGlobalCorpTechCustomers();
            
            case 'GlobalCorp Business Services Ltd':
                return $this->getGlobalCorpServicesCustomers();
            
            default:
                return [];
        }
    }

    private function getACMECustomers(): array
    {
        return [
            [
                'name' => 'Detroit Auto Parts Inc',
                'emails' => ['purchasing@detroitautoparts.com', 'accounting@detroitautoparts.com'],
                'phone' => '+1-313-555-0145',
                'type' => 'B2B',
                'location' => [
                    'name' => 'Detroit Auto Parts HQ',
                    'address_line_1' => '789 Motor City Blvd',
                    'city' => 'Detroit',
                    'state' => 'Michigan',
                    'country' => 'United States',
                    'postal_code' => '48226',
                ],
            ],
            [
                'name' => 'Midwest Industrial Supply',
                'emails' => ['orders@midwestindustrial.com'],
                'phone' => '+1-414-555-0167',
                'type' => 'B2B',
                'location' => [
                    'name' => 'Midwest Industrial Warehouse',
                    'address_line_1' => '1234 Industrial Park Drive',
                    'address_line_2' => 'Building C',
                    'city' => 'Milwaukee',
                    'state' => 'Wisconsin',
                    'country' => 'United States',
                    'postal_code' => '53202',
                ],
            ],
            [
                'name' => 'Great Lakes Manufacturing',
                'emails' => ['procurement@greatlakesmfg.com', 'finance@greatlakesmfg.com'],
                'phone' => '+1-216-555-0189',
                'type' => 'B2B',
                'location' => [
                    'name' => 'Great Lakes Manufacturing Plant',
                    'address_line_1' => '4567 Lakefront Avenue',
                    'city' => 'Cleveland',
                    'state' => 'Ohio',
                    'country' => 'United States',
                    'postal_code' => '44114',
                ],
            ],
            [
                'name' => 'American Steel Works',
                'emails' => ['purchasing@americansteel.com'],
                'phone' => '+1-412-555-0123',
                'type' => 'B2B',
                'location' => [
                    'name' => 'American Steel HQ',
                    'address_line_1' => '999 Steel Mill Road',
                    'city' => 'Pittsburgh',
                    'state' => 'Pennsylvania',
                    'country' => 'United States',
                    'postal_code' => '15222',
                ],
            ],
            [
                'name' => 'Precision Tools Corp',
                'emails' => ['orders@precisiontools.com'],
                'phone' => '+1-248-555-0145',
                'type' => 'B2B',
                'location' => [
                    'name' => 'Precision Tools Factory',
                    'address_line_1' => '567 Precision Way',
                    'city' => 'Warren',
                    'state' => 'Michigan',
                    'country' => 'United States',
                    'postal_code' => '48088',
                ],
            ],
        ];
    }

    private function getTechStartCustomers(): array
    {
        return [
            [
                'name' => 'Innovate Digital Agency',
                'emails' => ['billing@innovatedigital.com', 'accounts@innovatedigital.com'],
                'phone' => '+1-415-555-0299',
                'type' => 'B2B',
                'location' => [
                    'name' => 'Innovate Digital HQ',
                    'address_line_1' => '123 SOMA Street',
                    'address_line_2' => 'Suite 200',
                    'city' => 'San Francisco',
                    'state' => 'California',
                    'country' => 'United States',
                    'postal_code' => '94103',
                ],
            ],
            [
                'name' => 'CloudFirst Enterprises',
                'emails' => ['finance@cloudfirst.com'],
                'phone' => '+1-650-555-0234',
                'type' => 'B2B',
                'location' => [
                    'name' => 'CloudFirst Campus',
                    'address_line_1' => '456 Tech Drive',
                    'city' => 'Palo Alto',
                    'state' => 'California',
                    'country' => 'United States',
                    'postal_code' => '94301',
                ],
            ],
            [
                'name' => 'NextGen Startups Inc',
                'emails' => ['admin@nextgenstartups.com'],
                'phone' => '+1-408-555-0167',
                'type' => 'B2B',
                'location' => [
                    'name' => 'NextGen Office',
                    'address_line_1' => '789 Innovation Blvd',
                    'city' => 'San Jose',
                    'state' => 'California',
                    'country' => 'United States',
                    'postal_code' => '95113',
                ],
            ],
            [
                'name' => 'Mobile App Solutions',
                'emails' => ['billing@mobileappsolutions.com'],
                'phone' => '+1-415-555-0345',
                'type' => 'B2B',
                'location' => [
                    'name' => 'Mobile App Solutions Office',
                    'address_line_1' => '321 Mission Street',
                    'address_line_2' => 'Floor 8',
                    'city' => 'San Francisco',
                    'state' => 'California',
                    'country' => 'United States',
                    'postal_code' => '94105',
                ],
            ],
            [
                'name' => 'E-commerce Pioneers',
                'emails' => ['finance@ecommercepioneers.com', 'accounting@ecommercepioneers.com'],
                'phone' => '+1-510-555-0456',
                'type' => 'B2B',
                'location' => [
                    'name' => 'E-commerce Pioneers HQ',
                    'address_line_1' => '654 Bay Area Blvd',
                    'city' => 'Oakland',
                    'state' => 'California',
                    'country' => 'United States',
                    'postal_code' => '94607',
                ],
            ],
        ];
    }

    private function getEuroConsultCustomers(): array
    {
        return [
            [
                'name' => 'Deutsche Bank AG',
                'emails' => ['procurement@db.com', 'supplier.management@db.com'],
                'phone' => '+49-69-910-00',
                'type' => 'B2B',
                'location' => [
                    'name' => 'Deutsche Bank Twin Towers',
                    'address_line_1' => 'Taunusanlage 12',
                    'city' => 'Frankfurt am Main',
                    'state' => 'Hessen',
                    'country' => 'Germany',
                    'postal_code' => '60325',
                ],
            ],
            [
                'name' => 'BMW Group',
                'emails' => ['consulting.services@bmw.de'],
                'phone' => '+49-89-382-0',
                'type' => 'B2B',
                'location' => [
                    'name' => 'BMW Group HQ',
                    'address_line_1' => 'Petuelring 130',
                    'city' => 'Munich',
                    'state' => 'Bavaria',
                    'country' => 'Germany',
                    'postal_code' => '80788',
                ],
            ],
            [
                'name' => 'Siemens AG',
                'emails' => ['external.services@siemens.com'],
                'phone' => '+49-89-636-00',
                'type' => 'B2B',
                'location' => [
                    'name' => 'Siemens HQ',
                    'address_line_1' => 'Werner-von-Siemens-Straße 1',
                    'city' => 'Munich',
                    'state' => 'Bavaria',
                    'country' => 'Germany',
                    'postal_code' => '80333',
                ],
            ],
            [
                'name' => 'SAP SE',
                'emails' => ['consulting@sap.com'],
                'phone' => '+49-6227-7-47474',
                'type' => 'B2B',
                'location' => [
                    'name' => 'SAP Campus',
                    'address_line_1' => 'Dietmar-Hopp-Allee 16',
                    'city' => 'Walldorf',
                    'state' => 'Baden-Württemberg',
                    'country' => 'Germany',
                    'postal_code' => '69190',
                ],
            ],
            [
                'name' => 'Volkswagen AG',
                'emails' => ['consulting.services@volkswagen.de'],
                'phone' => '+49-5361-9-0',
                'type' => 'B2B',
                'location' => [
                    'name' => 'Volkswagen HQ',
                    'address_line_1' => 'Berliner Ring 2',
                    'city' => 'Wolfsburg',
                    'state' => 'Lower Saxony',
                    'country' => 'Germany',
                    'postal_code' => '38440',
                ],
            ],
            [
                'name' => 'BASF SE',
                'emails' => ['services@basf.com'],
                'phone' => '+49-621-60-0',
                'type' => 'B2B',
                'location' => [
                    'name' => 'BASF HQ',
                    'address_line_1' => 'Carl-Bosch-Straße 38',
                    'city' => 'Ludwigshafen',
                    'state' => 'Rhineland-Palatinate',
                    'country' => 'Germany',
                    'postal_code' => '67056',
                ],
            ],
        ];
    }

    private function getDemoCompanyCustomers(): array
    {
        return [
            [
                'name' => 'Retail Chain India Pvt Ltd',
                'emails' => ['procurement@retailchain.in'],
                'phone' => '+91-11-12345678',
                'type' => 'B2B',
                'location' => [
                    'name' => 'Retail Chain HQ',
                    'address_line_1' => 'Connaught Place',
                    'address_line_2' => 'Block A, Suite 123',
                    'city' => 'New Delhi',
                    'state' => 'Delhi',
                    'country' => 'India',
                    'postal_code' => '110001',
                ],
            ],
            [
                'name' => 'Mumbai Textiles Exports',
                'emails' => ['orders@mumbaitextiles.com', 'finance@mumbaitextiles.com'],
                'phone' => '+91-22-87654321',
                'type' => 'B2B',
                'location' => [
                    'name' => 'Mumbai Textiles Factory',
                    'address_line_1' => 'Industrial Estate, Andheri',
                    'city' => 'Mumbai',
                    'state' => 'Maharashtra',
                    'country' => 'India',
                    'postal_code' => '400069',
                ],
            ],
            [
                'name' => 'Tech Solutions Bangalore',
                'emails' => ['billing@techsolutionsblr.com'],
                'phone' => '+91-80-23456789',
                'type' => 'B2B',
                'location' => [
                    'name' => 'Tech Solutions Office',
                    'address_line_1' => 'Electronic City Phase 2',
                    'city' => 'Bangalore',
                    'state' => 'Karnataka',
                    'country' => 'India',
                    'postal_code' => '560100',
                ],
            ],
            [
                'name' => 'Chennai Auto Components',
                'emails' => ['purchase@chennaiauto.com'],
                'phone' => '+91-44-34567890',
                'type' => 'B2B',
                'location' => [
                    'name' => 'Chennai Auto Plant',
                    'address_line_1' => 'SIPCOT Industrial Park',
                    'city' => 'Chennai',
                    'state' => 'Tamil Nadu',
                    'country' => 'India',
                    'postal_code' => '603103',
                ],
            ],
        ];
    }

    private function getGlobalCorpHoldingsCustomers(): array
    {
        return [
            [
                'name' => 'Fortune 500 Corp',
                'emails' => ['procurement@fortune500corp.com'],
                'phone' => '+1-212-555-9999',
                'type' => 'B2B',
                'location' => [
                    'name' => 'Fortune 500 Corp HQ',
                    'address_line_1' => '100 Wall Street',
                    'address_line_2' => 'Suite 5000',
                    'city' => 'New York',
                    'state' => 'New York',
                    'country' => 'United States',
                    'postal_code' => '10005',
                ],
            ],
            [
                'name' => 'Enterprise Solutions Ltd',
                'emails' => ['contracts@enterprisesolutions.com'],
                'phone' => '+1-617-555-8888',
                'type' => 'B2B',
                'location' => [
                    'name' => 'Enterprise Solutions Office',
                    'address_line_1' => '200 Clarendon Street',
                    'city' => 'Boston',
                    'state' => 'Massachusetts',
                    'country' => 'United States',
                    'postal_code' => '02116',
                ],
            ],
            [
                'name' => 'Investment Bank Partners',
                'emails' => ['services@investmentbankpartners.com'],
                'phone' => '+1-312-555-7777',
                'type' => 'B2B',
                'location' => [
                    'name' => 'Investment Bank Partners Tower',
                    'address_line_1' => '300 North LaSalle',
                    'city' => 'Chicago',
                    'state' => 'Illinois',
                    'country' => 'United States',
                    'postal_code' => '60654',
                ],
            ],
        ];
    }

    private function getGlobalCorpTechCustomers(): array
    {
        return [
            [
                'name' => 'Infosys Limited',
                'emails' => ['partnerships@infosys.com'],
                'phone' => '+91-80-28520261',
                'type' => 'B2B',
                'location' => [
                    'name' => 'Infosys Campus',
                    'address_line_1' => 'Electronics City, Hosur Road',
                    'city' => 'Bangalore',
                    'state' => 'Karnataka',
                    'country' => 'India',
                    'postal_code' => '560100',
                ],
            ],
            [
                'name' => 'Wipro Technologies',
                'emails' => ['vendor.management@wipro.com'],
                'phone' => '+91-80-28440011',
                'type' => 'B2B',
                'location' => [
                    'name' => 'Wipro HQ',
                    'address_line_1' => 'Doddakannelli, Sarjapur Road',
                    'city' => 'Bangalore',
                    'state' => 'Karnataka',
                    'country' => 'India',
                    'postal_code' => '560035',
                ],
            ],
            [
                'name' => 'HCL Technologies',
                'emails' => ['procurement@hcltech.com'],
                'phone' => '+91-120-4688000',
                'type' => 'B2B',
                'location' => [
                    'name' => 'HCL Campus',
                    'address_line_1' => 'Sector 126, NOIDA',
                    'city' => 'Noida',
                    'state' => 'Uttar Pradesh',
                    'country' => 'India',
                    'postal_code' => '201303',
                ],
            ],
        ];
    }

    private function getGlobalCorpServicesCustomers(): array
    {
        return [
            [
                'name' => 'British Petroleum plc',
                'emails' => ['procurement@bp.com'],
                'phone' => '+44-20-7496-4000',
                'type' => 'B2B',
                'location' => [
                    'name' => 'BP HQ',
                    'address_line_1' => '1 St James\'s Square',
                    'city' => 'London',
                    'state' => 'England',
                    'country' => 'United Kingdom',
                    'postal_code' => 'SW1Y 4PD',
                ],
            ],
            [
                'name' => 'HSBC Holdings plc',
                'emails' => ['supplier.services@hsbc.com'],
                'phone' => '+44-20-7991-8888',
                'type' => 'B2B',
                'location' => [
                    'name' => 'HSBC Tower',
                    'address_line_1' => '8 Canada Square',
                    'city' => 'London',
                    'state' => 'England',
                    'country' => 'United Kingdom',
                    'postal_code' => 'E14 5HQ',
                ],
            ],
            [
                'name' => 'Rolls-Royce Holdings',
                'emails' => ['procurement@rolls-royce.com'],
                'phone' => '+44-20-7222-9020',
                'type' => 'B2B',
                'location' => [
                    'name' => 'Rolls-Royce HQ',
                    'address_line_1' => '62 Buckingham Gate',
                    'city' => 'London',
                    'state' => 'England',
                    'country' => 'United Kingdom',
                    'postal_code' => 'SW1E 6AT',
                ],
            ],
        ];
    }

    private function createCustomerWithLocation(Company $company, array $customerData): Customer
    {
        // Create the location first
        $locationData = $customerData['location'];
        $location = Location::create([
            'name' => $locationData['name'],
            'address_line_1' => $locationData['address_line_1'],
            'address_line_2' => $locationData['address_line_2'] ?? null,
            'city' => $locationData['city'],
            'state' => $locationData['state'],
            'country' => $locationData['country'],
            'postal_code' => $locationData['postal_code'],
            'locatable_type' => Customer::class,
            'locatable_id' => 1, // Temporary, will be updated
        ]);

        // Create the customer
        $customer = Customer::create([
            'name' => $customerData['name'],
            'emails' => new EmailCollection($customerData['emails']),
            'phone' => $customerData['phone'] ?? null,
            'primary_location_id' => $location->id,
            'company_id' => $company->id,
        ]);

        // Update location with correct customer ID
        $location->update(['locatable_id' => $customer->id]);

        return $customer->fresh(['primaryLocation']);
    }
}