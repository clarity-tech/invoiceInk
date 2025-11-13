<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Track if the current test has failed.
     */
    protected bool $testHasFailed = false;

    /**
     * Track if database setup has been completed for the test suite.
     */
    protected static bool $databaseSetupComplete = false;

    /**
     * Tables that should be truncated between tests for performance.
     * Currently preserving all data between tests for maximum reliability.
     */
    protected static array $tablesToTruncate = [
        // Note: Preserving all data between tests due to complex foreign key relationships
        // This ensures maximum reliability at the cost of some test isolation
    ];

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        // When running in Sail, we use the selenium container instead of local ChromeDriver
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Set up database and reset test failure flag for each test.
     * Uses hybrid approach: full setup once, then smart truncation.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->testHasFailed = false;

        // Handle database setup with performance optimization
        $this->setupDatabaseForBrowserTests();
    }

    /**
     * Handle database setup without refreshing.
     * Tests will create their own data inline using factories.
     */
    protected function setupDatabaseForBrowserTests(): void
    {
        // Database migrations already run for testing environment
        // Tests create data inline using factories - no refresh needed
        if (! static::$databaseSetupComplete) {
            static::$databaseSetupComplete = true;
        }
    }

    /**
     * Perform full database migration and seeding (first test only).
     */
    protected function performFullDatabaseSetup(): void
    {
        // Check if data already exists to avoid unnecessary fresh migration
        $existingUser = DB::table('users')->where('email', 'browser@example.test')->first();
        $existingInvoice = DB::table('invoices')->where('invoice_number', 'INV-BROWSER-001')->first();

        // Only do fresh migration if essential test data doesn't exist
        if (! $existingUser || ! $existingInvoice) {
            // Fresh migration with seeding for testing environment
            Artisan::call('migrate:fresh', [
                '--env' => 'testing',
                '--force' => true,
            ]);

            Artisan::call('db:seed', [
                '--env' => 'testing',
                '--force' => true,
            ]);
        }
    }

    /**
     * Perform optimized database reset between tests.
     * For maximum reliability, we preserve all seeded data between tests.
     */
    protected function performOptimizedDatabaseReset(): void
    {
        // For now, preserve all data between tests for reliability
        // Individual tests should be designed to work with existing data
        // This ensures maximum speed and reliability

        // Future optimization: Could implement selective cleanup here if needed
    }

    /**
     * Truncate tables that change between tests.
     * Preserves static data like users, organizations, tax_templates.
     */
    protected function truncateTestTables(): void
    {
        // For PostgreSQL, we need to handle foreign key constraints differently
        try {
            foreach (static::$tablesToTruncate as $table) {
                // PostgreSQL TRUNCATE with CASCADE to handle foreign keys
                DB::statement("TRUNCATE TABLE {$table} RESTART IDENTITY CASCADE");
            }
        } catch (\Exception $e) {
            // Fallback: Delete records if truncate fails
            foreach (static::$tablesToTruncate as $table) {
                DB::table($table)->delete();
            }
        }
    }

    /**
     * Reseed essential data that tests depend on.
     * Since we preserve all data between tests, this is now a no-op.
     */
    protected function reseedEssentialData(): void
    {
        // No reseeding needed since we preserve all data between tests
        // This ensures maximum reliability and consistency
    }

    /**
     * Ensure basic test data exists if reseeding fails.
     * Creates minimal data needed for tests to run.
     */
    protected function ensureBasicTestData(): void
    {
        // Ensure we have a test user (should already exist from initial setup)
        $testUser = \App\Models\User::where('email', 'browser@example.test')->first();

        if (! $testUser) {
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\Testing\\BrowserTestUserSeeder',
                '--env' => 'testing',
                '--force' => true,
            ]);
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
            '--disable-dev-shm-usage',
            '--disable-web-security',
            '--disable-features=VizDisplayCompositor',
            '--no-sandbox',
            '--no-first-run',
            '--disable-background-timer-throttling',
            '--disable-renderer-backgrounding',
            '--disable-backgrounding-occluded-windows',
            '--disable-extensions',
            '--disable-plugins',
            '--disable-default-apps',
            '--disable-sync',
            '--no-default-browser-check',
            '--no-pings',
            '--aggressive-cache-discard',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        $driver = RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://selenium:4444/wd/hub',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );

        // Set balanced timeouts for speed with reliability
        $driver->manage()->timeouts()->implicitlyWait(3); // 3 seconds for elements
        $driver->manage()->timeouts()->pageLoadTimeout(8); // 8 seconds for page loads
        $driver->manage()->timeouts()->setScriptTimeout(6); // 6 seconds for scripts

        return $driver;
    }

    /**
     * Called when a test fails.
     */
    protected function onNotSuccessfulTest(\Throwable $t): never
    {
        $this->testHasFailed = true;

        // Remove any automatic screenshots that might have been created in tearDown
        // since Laravel Dusk will handle failure screenshots
        $this->cleanupAutomaticScreenshots();

        parent::onNotSuccessfulTest($t);
    }

    /**
     * Check if the current test has passed.
     */
    protected function hasPassed(): bool
    {
        return ! $this->testHasFailed;
    }

    /**
     * Get the base URL for browser tests.
     * Override to use correct URL for selenium container connectivity.
     */
    protected function baseUrl()
    {
        return 'http://laravel.test';
    }

    /**
     * Automatically save responsive screenshots after each test.
     * Only captures screenshots for passing tests to avoid duplicates with Laravel Dusk's automatic failure screenshots.
     */
    protected function tearDown(): void
    {
        // Skip automatic screenshots for now to avoid hanging issues
        // Focus on getting tests to complete successfully first

        parent::tearDown();
    }

    /**
     * Get the current test method name for screenshot naming.
     */
    protected function getTestMethodName(): string
    {
        $testName = $this->name();

        // Clean the test name for filename safety
        $testName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $testName);
        $testName = trim($testName, '_');
        $testName = str_replace(['pest', '_evaluable', '__'], ['', '', ''], $testName);
        $testName = 'auto'.$testName;

        return $testName ?: 'unknown_test';
    }

    /**
     * Clean up automatic screenshots for failed tests.
     */
    protected function cleanupAutomaticScreenshots(): void
    {
        try {
            $testName = $this->getTestMethodName();
            $screenshotDir = base_path('tests/Browser/screenshots/auto-screenshots/passed/');

            if (is_dir($screenshotDir)) {
                $files = glob($screenshotDir."*{$testName}*");
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        } catch (\Exception $e) {
            error_log('Failed to cleanup automatic screenshots: '.$e->getMessage());
        }
    }
}
