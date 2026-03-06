<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\BeforeClass;
use Symfony\Component\Process\Process;

abstract class DuskTestCase extends BaseTestCase
{
    protected static ?Process $server = null;

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        $duskDatabasePath = dirname(__DIR__).'/database/dusk.sqlite';

        if (! file_exists($duskDatabasePath)) {
            touch($duskDatabasePath);
        }

        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }

        static::startApplicationServer();
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
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    #[AfterClass]
    public static function stopApplicationServer(): void
    {
        static::$server?->stop();
        static::$server = null;
    }

    protected static function startApplicationServer(): void
    {
        if (static::$server?->isRunning() || static::portIsListening('127.0.0.1', 8000)) {
            return;
        }

        static::$server = new Process(
            ['php', 'artisan', 'serve', '--env=dusk.local', '--host=127.0.0.1', '--port=8000'],
            dirname(__DIR__),
            ['XDEBUG_MODE' => 'off'],
        );

        static::$server->setTimeout(null);
        static::$server->start();

        retry(20, function (): void {
            if (! static::portIsListening('127.0.0.1', 8000)) {
                throw new \RuntimeException('The Dusk application server is not ready.');
            }
        }, 250);
    }

    protected static function portIsListening(string $host, int $port): bool
    {
        $connection = @fsockopen($host, $port, $errorNumber, $errorMessage, 1);

        if (! is_resource($connection)) {
            return false;
        }

        fclose($connection);

        return true;
    }
}
