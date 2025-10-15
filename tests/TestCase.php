<?php

namespace Tests;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;

abstract class TestCase extends BaseTestCase
{
    use WithFaker;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset faker unique cache to prevent unique constraint violations between tests
        $this->faker->unique(true);
        
        // Clean up test data before each test (for non-browser tests)
        if (!$this instanceof \Tests\DuskTestCase) {
            $this->cleanupTestData();
        }
    }
    
    protected function cleanupTestData(): void
    {
        // Delete all test data in reverse dependency order
        InvoiceItem::truncate();
        Invoice::truncate();
        Customer::truncate();
        Location::truncate();
        
        // Delete all teams (organizations) and their users
        Organization::truncate();
        User::truncate();
    }
}
