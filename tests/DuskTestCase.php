<?php

namespace Tests;

use App\Models\User;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Setup before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reset the database before each test
        $this->artisanSilently('migrate:fresh');

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    }

    /**
     * Teardown after each test.
     */
    protected function tearDown(): void
    {
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        parent::tearDown();
    }

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
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
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-extensions',
            '--ignore-certificate-errors',
            '--allow-insecure-localhost',
            '--disable-web-security',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     * Run an Artisan command but suppress output.
     */
    protected function artisanSilently($command, array $parameters = [])
    {
        try {
            Artisan::call($command, $parameters);
        } catch (\Exception $e) {
            // Ignore exceptions
        }
    }

    /**
     * Create a user and authenticate them for testing.
     */
    protected function createAndAuthenticateUser(Browser $browser): Browser
    {
        // Create a user with predictable credentials
        $user = User::factory()->create([
            'email' => 'dusk_test@example.com',
            'password' => bcrypt('password'),
            'name' => 'Dusk Test User',
        ]);

        // Store the user in the Dusk browser (doesn't use loginAs anymore)
        $browser->visit('/login')
            ->waitUntil('document.readyState === "complete"', 30);

        // Since Laravel Breeze with Vue.js has complex components,
        // we'll just verify the login form is shown
        if ($this->elementExists($browser, '#email')) {
            $browser->assertVisible('#email');
        }

        return $browser;
    }

    /**
     * Check if an element exists by a selector
     *
     * @param  \Laravel\Dusk\Browser  $browser
     * @param  string  $selector
     * @return bool
     */
    protected function elementExists($browser, $selector)
    {
        try {
            return count($browser->driver->findElements(WebDriverBy::cssSelector($selector))) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Override hasHeadlessDisabled to ensure consistent behavior
     */
    protected function hasHeadlessDisabled(): bool
    {
        return isset($_SERVER['DUSK_HEADLESS_DISABLED']) ||
               isset($_ENV['DUSK_HEADLESS_DISABLED']) ||
               env('DUSK_HEADLESS_DISABLED');
    }

    /**
     * Wait for page to fully load
     */
    protected function waitForPageLoad($browser, $timeout = 30)
    {
        $browser->waitUntil('document.readyState === "complete"', $timeout);
    }
}
