<?php

declare(strict_types=1);

namespace Ihsan\UmmAlQuraPrayerTimes\Tests\Laravel;

use Ihsan\UmmAlQuraPrayerTimes\City;
use Ihsan\UmmAlQuraPrayerTimes\CityConfig;
use Ihsan\UmmAlQuraPrayerTimes\Laravel\PrayerTimesServiceProvider;
use Ihsan\UmmAlQuraPrayerTimes\PrayerTimes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Orchestra\Testbench\TestCase;

if (!class_exists(TestCase::class)) {
    return; // Laravel not installed: the plain-PHP suite still runs.
}

final class ServiceProviderTest extends TestCase
{
    /** Config set per test before the application boots. */
    private ?array $cityConfig = null;

    protected function getPackageProviders($app): array
    {
        return [PrayerTimesServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        if ($this->cityConfig !== null) {
            $app['config']->set(PrayerTimesServiceProvider::CONFIG_KEY, $this->cityConfig);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        CityConfig::reset();
    }

    public function testDefaultsWithoutPublishedConfig(): void
    {
        self::assertSame(46.69, City::SA_Riyadh->longitude());
        self::assertSame('11:37', PrayerTimes::on(City::SA_Riyadh, '2026-11-05')->dhuhr->format('H:i'));
    }

    public function testPublishedConfigOverridesCityCentre(): void
    {
        $this->cityConfig = ['SA' => ['riyadh' => ['longitude' => 46.19]]];
        $this->refreshApplication();

        self::assertSame(46.19, City::SA_Riyadh->longitude());
        self::assertSame('الرياض', City::SA_Riyadh->nameAr());
        // 0.5° west = exactly 2 minutes later
        self::assertSame('11:39', PrayerTimes::on(City::SA_Riyadh, '2026-11-05')->dhuhr->format('H:i'));
    }

    public function testVendorPublishCopiesConfig(): void
    {
        $target = $this->app->configPath('prayer-cities.php');
        @unlink($target);

        $this->artisan('vendor:publish', ['--tag' => 'prayer-times-config'])->assertExitCode(0);

        self::assertFileExists($target);
        $published = require $target;
        self::assertSame(24.67, $published['SA']['riyadh']['latitude']);
        unlink($target);
    }

    public function testEnumValidationRule(): void
    {
        $rules = ['city' => ['required', Rule::enum(City::class)]];

        self::assertTrue(Validator::make(['city' => 'sa.riyadh'], $rules)->passes());
        self::assertFalse(Validator::make(['city' => 'riyadh'], $rules)->passes());
    }
}
