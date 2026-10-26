<?php

namespace Database\Seeders;

use App\Enums\Country;
use App\Enums\GstTreatment;
use App\Enums\IndianState;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Organization;
use App\ValueObjects\ContactCollection;

class CustomerSeeder extends ProductionSafeSeeder
{
    protected function seed(): void
    {
        $this->info('Seeding customers...');

        $organizations = Organization::with('primaryLocation')->get();

        foreach ($organizations as $organization) {
            $customers = $this->customersFor($organization);

            foreach ($customers as $customer) {
                $this->createCustomer($organization, $customer);
            }

            $this->info('✓ Created '.count($customers)." customers for {$organization->name}");
        }

        $this->archiveOneCustomer();

        $this->info('Created customers and locations successfully!');
    }

    /**
     * Archive a customer so the archived-customer views have data. Given its
     * own record rather than trashing an active one, to keep the invoice data
     * for existing customers intact.
     */
    private function archiveOneCustomer(): void
    {
        $organization = Organization::where('name', 'LIKE', 'Demo Company%')->first();

        if (! $organization) {
            return;
        }

        $this->createCustomer($organization, [
            'name' => 'Closed Account Traders',
            'email' => 'accounts@closedaccounttraders.test',
            'phone' => '+911123456789',
            'address' => 'Shop 18, Karol Bagh Market',
            'city' => 'New Delhi',
            'state' => IndianState::Delhi,
            'postal_code' => '110005',
            'country' => 'IN',
            'tax_id' => '07AAGFC1180D1Z9',
        ])->delete();

        $this->info('✓ Archived customer Closed Account Traders');
    }

    /**
     * Distinct, plausible customers: a city in its own state with its own
     * postcode, a phone and tax ID from the customer's country, and for an
     * Indian customer a GSTIN whose state code matches the place of supply.
     * An Indian organization's first customer is in its own state, so its
     * invoices show the CGST and SGST split as well as IGST.
     *
     * @return list<array<string, mixed>>
     */
    private function customersFor(Organization $organization): array
    {
        return match (true) {
            str_contains($organization->name, 'Manufacturing') => [
                ['name' => 'Lakeshore Fabrication Inc', 'email' => 'ap@lakeshorefab.test', 'phone' => '+12165550142', 'address' => '2400 Superior Ave', 'city' => 'Cleveland', 'state' => 'Ohio', 'postal_code' => '44114', 'country' => 'US', 'tax_id' => '34-1876520'],
                ['name' => 'Great Lakes Auto Parts LLC', 'email' => 'payables@greatlakesauto.test', 'phone' => '+13135550187', 'address' => '1800 Woodward Ave', 'city' => 'Detroit', 'state' => 'Michigan', 'postal_code' => '48226', 'country' => 'US', 'tax_id' => '38-2945117'],
                ['name' => 'Prairie Tractor Works', 'email' => 'accounts@prairietractor.test', 'phone' => '+13095550163', 'address' => '410 Main St', 'city' => 'Peoria', 'state' => 'Illinois', 'postal_code' => '61602', 'country' => 'US'],
                ['name' => 'Ironbridge Construction Co', 'email' => 'ap@ironbridge.test', 'phone' => '+14125550119', 'address' => '55 Water St', 'city' => 'Pittsburgh', 'state' => 'Pennsylvania', 'postal_code' => '15222', 'country' => 'US', 'tax_id' => '25-1739046'],
                ['name' => 'Sunbelt Equipment Rentals', 'email' => 'billing@sunbeltequipment.test', 'phone' => '+12145550175', 'address' => '900 Commerce St', 'city' => 'Dallas', 'state' => 'Texas', 'postal_code' => '75202', 'country' => 'US'],
            ],

            str_contains($organization->name, 'TechStart') => [
                ['name' => 'Brightpath Learning Inc', 'email' => 'finance@brightpath.test', 'phone' => '+14155550134', 'address' => '300 Pine St', 'city' => 'San Francisco', 'state' => 'California', 'postal_code' => '94104', 'country' => 'US', 'tax_id' => '94-3318206'],
                ['name' => 'Harborview Health Partners', 'email' => 'ap@harborviewhealth.test', 'phone' => '+12065550158', 'address' => '1200 Third Ave', 'city' => 'Seattle', 'state' => 'Washington', 'postal_code' => '98101', 'country' => 'US'],
                ['name' => 'Northwind Analytics LLC', 'email' => 'accounts@northwind.test', 'phone' => '+15125550121', 'address' => '500 Congress Ave', 'city' => 'Austin', 'state' => 'Texas', 'postal_code' => '78701', 'country' => 'US', 'tax_id' => '74-2860193'],
                ['name' => 'Copperline Payments Inc', 'email' => 'payables@copperline.test', 'phone' => '+12125550196', 'address' => '80 Broad St', 'city' => 'New York', 'state' => 'New York', 'postal_code' => '10004', 'country' => 'US', 'tax_id' => '13-4027781'],
                ['name' => 'Redwood Retail Group', 'email' => 'finance@redwoodretail.test', 'phone' => '+15105550148', 'address' => '2100 Broadway', 'city' => 'Oakland', 'state' => 'California', 'postal_code' => '94612', 'country' => 'US'],
            ],

            str_contains($organization->name, 'EuroConsult') => [
                ['name' => 'Hanseatic Logistik GmbH', 'email' => 'buchhaltung@hanseatic-logistik.test', 'phone' => '+494030712345', 'address' => 'Am Sandtorkai 50', 'city' => 'Hamburg', 'state' => 'Hamburg', 'postal_code' => '20457', 'country' => 'DE', 'tax_id' => 'DE812364597'],
                ['name' => 'Bavaria Maschinenbau AG', 'email' => 'rechnungen@bavaria-maschinenbau.test', 'phone' => '+498938812345', 'address' => 'Leopoldstraße 120', 'city' => 'Munich', 'state' => 'Bavaria', 'postal_code' => '80802', 'country' => 'DE', 'tax_id' => 'DE129475308'],
                ['name' => 'Rheinland Energie GmbH', 'email' => 'finanzen@rheinland-energie.test', 'phone' => '+4922129012345', 'address' => 'Rheinauhafen 12', 'city' => 'Cologne', 'state' => 'North Rhine-Westphalia', 'postal_code' => '50678', 'country' => 'DE', 'tax_id' => 'DE264118735'],
                ['name' => 'Main Finanzpartner AG', 'email' => 'kreditoren@main-finanzpartner.test', 'phone' => '+496927312345', 'address' => 'Taunusanlage 8', 'city' => 'Frankfurt', 'state' => 'Hesse', 'postal_code' => '60329', 'country' => 'DE', 'tax_id' => 'DE114207369'],
                ['name' => 'Schwaben Automotive GmbH', 'email' => 'buchhaltung@schwaben-automotive.test', 'phone' => '+4971122212345', 'address' => 'Königstraße 40', 'city' => 'Stuttgart', 'state' => 'Baden-Württemberg', 'postal_code' => '70173', 'country' => 'DE', 'tax_id' => 'DE147736025'],
                ['name' => 'Spree Digital GmbH', 'email' => 'hallo@spree-digital.test', 'phone' => '+493020912345', 'address' => 'Friedrichstraße 68', 'city' => 'Berlin', 'state' => 'Berlin', 'postal_code' => '10117', 'country' => 'DE', 'tax_id' => 'DE301552846'],
            ],

            str_contains($organization->name, 'Demo Company') => [
                ['name' => 'Bangalore Electronics Ltd', 'email' => 'accounts@bangaloreelectronics.test', 'phone' => '+918041239876', 'address' => 'Plot 14, Electronic City Phase 1', 'city' => 'Bengaluru', 'state' => IndianState::Karnataka, 'postal_code' => '560100', 'country' => 'IN', 'tax_id' => '29AABCB2310R1ZP'],
                ['name' => 'Deccan Retail Pvt Ltd', 'email' => 'payables@deccanretail.test', 'phone' => '+912066123456', 'address' => 'Survey No. 21, Baner Road', 'city' => 'Pune', 'state' => IndianState::Maharashtra, 'postal_code' => '411045', 'country' => 'IN', 'tax_id' => '27AAFCD6120K1Z2'],
                ['name' => 'Coromandel Foods Ltd', 'email' => 'finance@coromandelfoods.test', 'phone' => '+914428523311', 'address' => '45 Anna Salai', 'city' => 'Chennai', 'state' => IndianState::TamilNadu, 'postal_code' => '600002', 'country' => 'IN', 'tax_id' => '33AACCC4587H1ZX'],
                [
                    'name' => 'Sandline LLC',
                    'contacts' => [['name' => 'Billing', 'email' => 'billing@sandline.test'], ['name' => 'Finance', 'email' => 'finance@sandline.test']],
                    'phone' => '+97141234567',
                    'location' => 'Sandline Healthcare HQ',
                    'address' => 'Dubai Healthcare City',
                    'address_line_2' => 'Building 64, Office 4001',
                    'city' => 'Dubai',
                    'state' => 'Dubai',
                    'postal_code' => '00000',
                    'country' => 'AE',
                ],
                [
                    'name' => 'Corniche Labs',
                    'contacts' => [['name' => 'Priya', 'email' => 'priya@cornichelabs.test'], ['name' => 'Consultant', 'email' => 'consult@cornichelabs.test']],
                    'phone' => '+97147654321',
                    'address' => 'Al Warsan Towers, 305',
                    'address_line_2' => 'Barsha Heights',
                    'city' => 'Dubai',
                    'state' => 'Dubai',
                    'postal_code' => '00000',
                    'country' => 'AE',
                ],
            ],

            str_contains($organization->name, 'Dubai Trading') => [
                ['name' => 'Gulf Commercial Ltd', 'email' => 'accounts@gulfcommercial.test', 'phone' => '+97142951234', 'address' => 'Al Maktoum Road, Deira', 'city' => 'Dubai', 'state' => 'Dubai', 'postal_code' => '00000', 'country' => 'AE', 'tax_id' => '100345678900003'],
                ['name' => 'Marina Hospitality LLC', 'email' => 'finance@marinahospitality.test', 'phone' => '+97144561234', 'address' => 'Marina Plaza, Dubai Marina', 'city' => 'Dubai', 'state' => 'Dubai', 'postal_code' => '00000', 'country' => 'AE', 'tax_id' => '100567812300003'],
            ],

            str_contains($organization->name, 'GlobalCorp Holdings') => [
                ['name' => 'Konkan Shipping Ltd', 'email' => 'accounts@konkanshipping.test', 'phone' => '+912222651234', 'address' => '15 Shoorji Vallabhdas Marg, Ballard Estate', 'city' => 'Mumbai', 'state' => IndianState::Maharashtra, 'postal_code' => '400001', 'country' => 'IN', 'tax_id' => '27AACCK3921L1Z4'],
                ['name' => 'Sabarmati Textiles Pvt Ltd', 'email' => 'payables@sabarmatitextiles.test', 'phone' => '+917926581234', 'address' => 'Ashram Road, Navrangpura', 'city' => 'Ahmedabad', 'state' => IndianState::Gujarat, 'postal_code' => '380009', 'country' => 'IN', 'tax_id' => '24AAKCS6672F1ZA'],
                ['name' => 'Hudson Freight Holdings Inc', 'email' => 'ap@hudsonfreight.test', 'phone' => '+12125550163', 'address' => '1 Liberty Plaza', 'city' => 'New York', 'state' => 'New York', 'postal_code' => '10006', 'country' => 'US', 'tax_id' => '13-5547203'],
                ['name' => 'Elbe Handelsgesellschaft mbH', 'email' => 'buchhaltung@elbe-handel.test', 'phone' => '+494036812345', 'address' => 'Großer Burstah 31', 'city' => 'Hamburg', 'state' => 'Hamburg', 'postal_code' => '20457', 'country' => 'DE', 'tax_id' => 'DE118845302'],
            ],

            str_contains($organization->name, 'GlobalCorp Tech') => [
                ['name' => 'Charminar Software Services Pvt Ltd', 'email' => 'accounts@charminarsoftware.test', 'phone' => '+914023351234', 'address' => 'Road No. 12, Banjara Hills', 'city' => 'Hyderabad', 'state' => IndianState::Telangana, 'postal_code' => '500034', 'country' => 'IN', 'tax_id' => '36AAGCC8814N1Z1'],
                ['name' => 'Malabar Spice Exports Ltd', 'email' => 'finance@malabarspice.test', 'phone' => '+914842381234', 'address' => 'MG Road, Ernakulam', 'city' => 'Kochi', 'state' => IndianState::Kerala, 'postal_code' => '682016', 'country' => 'IN', 'tax_id' => '32AACCM5521B1Z7'],
                ['name' => 'Mission Street Labs Inc', 'email' => 'ap@missionstreetlabs.test', 'phone' => '+14155550177', 'address' => '600 Mission St', 'city' => 'San Francisco', 'state' => 'California', 'postal_code' => '94105', 'country' => 'US', 'tax_id' => '46-1192837'],
            ],

            str_contains($organization->name, 'Clarity Tech Admin') => [
                ['name' => 'Nilgiri Tea Estates Ltd', 'email' => 'accounts@nilgiritea.test', 'phone' => '+914232441234', 'address' => 'Commercial Road', 'city' => 'Ooty', 'state' => IndianState::TamilNadu, 'postal_code' => '643001', 'country' => 'IN', 'tax_id' => '33AABCN2209E1Z8'],
                ['name' => 'Brahmaputra Logistics Pvt Ltd', 'email' => 'finance@brahmaputralogistics.test', 'phone' => '+913612541234', 'address' => 'GS Road, Christian Basti', 'city' => 'Guwahati', 'state' => IndianState::Assam, 'postal_code' => '781005', 'country' => 'IN', 'tax_id' => '18AAFCB7741Q1Z2'],
            ],

            str_contains($organization->name, 'Robert Global') => [
                ['name' => 'Western Ghats Resorts Pvt Ltd', 'email' => 'accounts@westernghatsresorts.test', 'phone' => '+912114271234', 'address' => 'Old Mumbai-Pune Highway', 'city' => 'Lonavala', 'state' => IndianState::Maharashtra, 'postal_code' => '410401', 'country' => 'IN', 'tax_id' => '27AACCW4412J1ZT'],
                ['name' => 'Narmada Agro Industries Ltd', 'email' => 'finance@narmadaagro.test', 'phone' => '+917312521234', 'address' => 'AB Road, Vijay Nagar', 'city' => 'Indore', 'state' => IndianState::MadhyaPradesh, 'postal_code' => '452010', 'country' => 'IN', 'tax_id' => '23AABCN6630G1Z5'],
            ],

            str_contains($organization->name, 'Fresh Start') => [
                ['name' => 'Ganga Handicrafts', 'email' => 'orders@gangahandicrafts.test', 'phone' => '+915422501234', 'address' => 'Godowlia Market', 'city' => 'Varanasi', 'state' => IndianState::UttarPradesh, 'postal_code' => '221001', 'country' => 'IN'],
                ['name' => 'Hooghly Prints Pvt Ltd', 'email' => 'accounts@hooghlyprints.test', 'phone' => '+913322481234', 'address' => '22 Park Street', 'city' => 'Kolkata', 'state' => IndianState::WestBengal, 'postal_code' => '700016', 'country' => 'IN', 'tax_id' => '19AADCH3318L1Z9'],
            ],

            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $customer  A row from customersFor().
     */
    private function createCustomer(Organization $organization, array $customer): Customer
    {
        $country = Country::from($customer['country']);
        $indianState = $customer['state'] instanceof IndianState ? $customer['state'] : null;

        $model = Customer::factory()->for($organization)->create([
            'name' => $customer['name'],
            'emails' => new ContactCollection($customer['contacts'] ?? [['name' => 'Accounts', 'email' => $customer['email']]]),
            'phone' => $customer['phone'],
            'phone_country' => $country->value,
            'currency' => $country->getDefaultCurrency(),
            'place_of_supply' => $indianState?->value,
            'gst_treatment' => $country === Country::IN ? GstTreatment::Domestic : GstTreatment::Overseas,
        ]);

        $location = Location::create([
            'name' => $customer['location'] ?? 'Head Office',
            'address_line_1' => $customer['address'],
            'address_line_2' => $customer['address_line_2'] ?? null,
            'city' => $customer['city'],
            'state' => $indianState?->label() ?? $customer['state'],
            'postal_code' => $customer['postal_code'],
            'country' => $country->value,
            'tax_id' => $customer['tax_id'] ?? null,
            'locatable_type' => Customer::class,
            'locatable_id' => $model->id,
        ]);

        $model->update(['primary_location_id' => $location->id]);

        return $model;
    }
}
