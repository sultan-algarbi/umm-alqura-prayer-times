<?php

declare(strict_types=1);

namespace Ihsan\UmmAlQuraPrayerTimes\Laravel;

use Ihsan\UmmAlQuraPrayerTimes\CityConfig;
use Illuminate\Support\ServiceProvider;

/**
 * Laravel integration, registered automatically through package discovery.
 *
 *  - `php artisan vendor:publish --tag=prayer-times-config` copies the cities config
 *    to config/prayer-cities.php.
 *  - When that file exists, its values are merged over the package defaults on boot.
 *    It is read through Laravel's config repository, so `php artisan config:cache` works.
 */
final class PrayerTimesServiceProvider extends ServiceProvider
{
    public const CONFIG_KEY = 'prayer-cities';

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__, 2) . '/config/cities.php' => $this->app->configPath(self::CONFIG_KEY . '.php'),
            ], 'prayer-times-config');
        }

        $config = $this->app['config']->get(self::CONFIG_KEY);

        if (is_array($config) && $config !== []) {
            CityConfig::set($config);
        }
    }
}
