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
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
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
     * Automatically save screenshots after each test.
     */
    protected function tearDown(): void
    {
        // Capture screenshots for all browsers after each test
        foreach (static::$browsers as $browser) {
            $testName = $this->getTestMethodName();
            $timestamp = now()->format('Y-m-d_H-i-s');
            $screenshotName = "{$testName}_{$timestamp}";

            try {
                $browser->screenshot($screenshotName);
            } catch (\Exception $e) {
                // If screenshot fails, don't break the test
                error_log("Failed to capture screenshot for {$testName}: ".$e->getMessage());
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

        return $testName ?: 'unknown_test';
    }
}
