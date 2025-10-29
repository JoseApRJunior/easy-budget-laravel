<?php

namespace Tests;

use App\Models\Provider;
use App\Models\User;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass ]
    public static function prepare(): void
    {
        if ( !static::runningInSail() ) {
            static::startChromeDriver( [ '--port=9515' ] );
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = ( new ChromeOptions )->addArguments( collect( [
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
            '--disable-dev-shm-usage',
            '--no-sandbox',
            '--disable-web-security',
        ] )->unless( $this->hasHeadlessDisabled(), function ( Collection $items ) {
            return $items->merge( [
                '--disable-gpu',
                '--headless=new',
            ] );
        } )->all() );

        return RemoteWebDriver::create(
            $_ENV[ 'DUSK_DRIVER_URL' ] ?? env( 'DUSK_DRIVER_URL' ) ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options,
            ),
        );
    }

    /**
     * Take screenshot for debugging.
     */
    public function takeScreenshot( string $name = 'screenshot' )
    {
        if ( app()->environment( 'local', 'testing' ) ) {
            $screenshot = storage_path( 'app/public/test-screenshots' );

            if ( !file_exists( $screenshot ) ) {
                mkdir( $screenshot, 0755, true );
            }

            $timestamp = now()->format( 'Y-m-d_H-i-s' );
            $filename  = "{$screenshot}/{$name}_{$timestamp}.png";

            $this->browse( function ( $browser ) use ( $filename ) {
                $browser->screenshot( $filename );
            } );

            return $filename;
        }
    }

    /**
     * Clear old screenshots.
     */
    protected static function cleanupScreenshots()
    {
        $screenshotDir = storage_path( 'app/public/test-screenshots' );

        if ( is_dir( $screenshotDir ) ) {
            $files      = glob( $screenshotDir . '/*' );
            $oneWeekAgo = time() - ( 7 * 24 * 60 * 60 );

            foreach ( $files as $file ) {
                if ( filemtime( $file ) < $oneWeekAgo ) {
                    unlink( $file );
                }
            }
        }
    }

    /**
     * Check if browser is in headless mode.
     */
    protected function isHeadlessMode(): bool
    {
        return env( 'DUSK_HEADLESS_DISABLED', false ) === false;
    }

    /**
     * Create test provider for isolation.
     */
    protected function createTestProvider()
    {
        return User::factory()->create( [
            'role'      => 'provider',
            'is_active' => true,
        ] );
    }

    /**
     * Clean up test data after each test.
     */
    protected function cleanUpTestData()
    {
        // Limpeza específica para testes do formulário business
        User::where( 'email', 'like', '%@test.com' )->delete();
        Provider::whereHas( 'user', function ( $query ) {
            $query->where( 'email', 'like', '%@test.com' );
        } )->delete();
    }

}
