<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Track if the current test has failed.
     */
    protected bool $testHasFailed = false;

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
     * Reset test failure flag for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->testHasFailed = false;
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
            '--no-sandbox',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://selenium:4444/wd/hub',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
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
     * Automatically save responsive screenshots after each test.
     * Only captures screenshots for passing tests to avoid duplicates with Laravel Dusk's automatic failure screenshots.
     */
    protected function tearDown(): void
    {
        // Only capture screenshots for passing tests
        // Laravel Dusk automatically captures failure screenshots, so we avoid duplicates
        if ($this->hasPassed()) {
            // Capture responsive screenshots for all browsers after each test
            foreach (static::$browsers as $browser) {
                $testName = $this->getTestMethodName();
                $timestamp = now()->format('Y-m-d_H-i-s');

                try {
                    // Use Laravel Dusk's built-in responsiveScreenshots method
                    $screenshotName = "auto-passed/{$testName}_{$timestamp}";
                    $browser->responsiveScreenshots($screenshotName);
                } catch (\Exception $e) {
                    // If screenshot fails, don't break the test
                    error_log("Failed to capture responsive screenshots for {$testName}: ".$e->getMessage());
                }
            }
        }

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
