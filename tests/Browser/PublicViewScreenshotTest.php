<?php

namespace Tests\Browser;

use App\Models\Customer;
use App\Models\Location;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PublicViewScreenshotTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_capture_public_invoice_and_estimate(): void
    {
        $this->browse(function (Browser $browser) {
            // Create complete demo data
            $user = User::factory()->withPersonalTeam()->create([
                'name' => 'Demo Company Owner',
                'email' => 'owner@democorp.test',
            ]);

            $organization = $user->currentTeam;
            $organization->update([
                'company_name' => 'Demo Corporation Ltd',
                'setup_completed_at' => now(),
            ]);

            // Create organization location
            $orgLocation = Location::create([
                'name' => 'Head Office',
                'address_line_1' => '123 Business Center',
                'address_line_2' => 'Suite 456',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'country' => 'IN',
                'postal_code' => '400001',
                'locatable_type' => Organization::class,
                'locatable_id' => $organization->id,
            ]);
            $organization->update(['primary_location_id' => $orgLocation->id]);

            // Create customer
            $customer = Customer::create([
                'name' => 'Tech Startup Inc',
                'organization_id' => $organization->id,
                'emails' => ['billing@techstartup.test'],
                'phone' => '+91-98765-43210',
            ]);

            // Create customer location
            $customerLocation = Location::create([
                'name' => 'Customer Office',
                'address_line_1' => '789 Innovation Drive',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'country' => 'IN',
                'postal_code' => '411001',
                'locatable_type' => Customer::class,
                'locatable_id' => $customer->id,
            ]);
            $customer->update(['primary_location_id' => $customerLocation->id]);

            // Create invoice with all required fields
            $invoice = \App\Models\Invoice::create([
                'ulid' => \Illuminate\Support\Str::ulid(),
                'type' => 'invoice',
                'organization_id' => $organization->id,
                'customer_id' => $customer->id,
                'organization_location_id' => $orgLocation->id,
                'customer_location_id' => $customerLocation->id,
                'invoice_number' => 'INV-2024-001',
                'status' => 'sent',
                'currency' => 'INR',
                'subtotal' => 100000, // ₹1000.00
                'tax' => 18000,       // ₹180.00 (18%)
                'total' => 118000,    // ₹1180.00
                'issued_at' => now(),
                'due_at' => now()->addDays(30),
                'notes' => 'Thank you for your business!',
                'terms' => 'Payment due within 30 days.',
            ]);

            // Create invoice items
            \App\Models\InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'Web Development Services',
                'quantity' => 40,
                'unit_price' => 2500, // ₹25.00 per unit
                'tax_rate' => 18.0,
            ]);

            // Screenshot: Public Invoice View
            $browser->visit("/invoices/view/{$invoice->ulid}")
                ->pause(4000)
                ->screenshot('invoice-journey/02-public-invoice-view');

            // Create estimate with required fields
            $estimate = \App\Models\Invoice::create([
                'ulid' => \Illuminate\Support\Str::ulid(),
                'type' => 'estimate',
                'organization_id' => $organization->id,
                'customer_id' => $customer->id,
                'organization_location_id' => $orgLocation->id,
                'customer_location_id' => $customerLocation->id,
                'invoice_number' => 'EST-2024-001',
                'status' => 'draft',
                'currency' => 'INR',
                'subtotal' => 250000, // ₹2500.00
                'tax' => 45000,       // ₹450.00 (18%)
                'total' => 295000,    // ₹2950.00
                'issued_at' => now(),
                'due_at' => now()->addDays(15),
                'notes' => 'This estimate is valid for 30 days.',
                'terms' => 'Project will begin upon approval.',
            ]);

            // Create estimate items
            \App\Models\InvoiceItem::create([
                'invoice_id' => $estimate->id,
                'description' => 'Digital Marketing Strategy',
                'quantity' => 1,
                'unit_price' => 50000, // ₹500.00
                'tax_rate' => 18.0,
            ]);

            \App\Models\InvoiceItem::create([
                'invoice_id' => $estimate->id,
                'description' => 'Social Media Management (6 months)',
                'quantity' => 6,
                'unit_price' => 12000, // ₹120.00 per month
                'tax_rate' => 18.0,
            ]);

            \App\Models\InvoiceItem::create([
                'invoice_id' => $estimate->id,
                'description' => 'SEO Optimization',
                'quantity' => 1,
                'unit_price' => 25000, // ₹250.00
                'tax_rate' => 18.0,
            ]);

            // Screenshot: Public Estimate View
            $browser->visit("/estimates/view/{$estimate->ulid}")
                ->pause(4000)
                ->screenshot('estimate-journey/01-public-estimate-view');

            // Login and show internal views
            $browser->loginAs($user)
                ->visit('/invoices')
                ->pause(4000)
                ->screenshot('invoice-journey/03-invoices-list-populated');

            // Try to show PDF views if working
            try {
                $browser->visit("/invoices/view/{$invoice->ulid}/pdf")
                    ->pause(3000)
                    ->screenshot('invoice-journey/04-invoice-pdf-view');
            } catch (\Exception $e) {
                // PDF might not work in browser test, skip
            }

            try {
                $browser->visit("/estimates/view/{$estimate->ulid}/pdf")
                    ->pause(3000)
                    ->screenshot('estimate-journey/02-estimate-pdf-view');
            } catch (\Exception $e) {
                // PDF might not work in browser test, skip
            }
        });
    }

    public function test_capture_dashboard_summary(): void
    {
        $this->browse(function (Browser $browser) {
            // Create user with rich demo data
            $user = User::factory()->withPersonalTeam()->create([
                'name' => 'Business Owner',
                'email' => 'owner@business.test',
            ]);

            $organization = $user->currentTeam;
            $organization->update([
                'company_name' => 'Professional Services LLC',
                'setup_completed_at' => now(),
            ]);

            // Screenshot: Rich dashboard view
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->pause(4000)
                ->screenshot('user-onboarding/08-rich-dashboard-view');
        });
    }
}
