<?php

declare(strict_types=1);

use Ihsan\UmmAlQuraPrayerTimes\City;
use Ihsan\UmmAlQuraPrayerTimes\PrayerTimes;

if (!function_exists('prayer_times')) {
    /**
     * Prayer times of a city on a Gregorian date (null = today).
     *
     *     prayer_times(City::SA_Riyadh, '2026-11-05')->toArray();
     */
    function prayer_times(City $city, DateTimeInterface|string|null $date = null): PrayerTimes
    {
        return PrayerTimes::on($city, $date);
    }
}
