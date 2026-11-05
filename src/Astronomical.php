<?php

declare(strict_types=1);

namespace Ihsan\UmmAlQuraPrayerTimes;

/**
 * Solar-position algorithms from Jean Meeus, "Astronomical Algorithms" (2nd ed.).
 *
 * @internal
 */
final class Astronomical
{
    public static function deg2rad(float $deg): float
    {
        return $deg * M_PI / 180.0;
    }

    public static function rad2deg(float $rad): float
    {
        return $rad * 180.0 / M_PI;
    }

    public static function normalizeToScale(float $num, float $max): float
    {
        return $num - $max * floor($num / $max);
    }

    public static function unwindAngle(float $angle): float
    {
        return self::normalizeToScale($angle, 360.0);
    }

    /**
     * Apparent declination of the sun (degrees) and the equation of time (hours) at a Julian Day.
     *
     * @return array{declination: float, equationOfTime: float}
     */
    public static function sunPosition(float $jd): array
    {
        $T = self::julianCentury($jd);
        $L0 = self::meanSolarLongitude($T);
        $Lp = self::meanLunarLongitude($T);
        $omega = self::ascendingLunarNodeLongitude($T);
        $lambda = self::deg2rad(self::apparentSolarLongitude($T, $L0));
        $epsilon0 = self::meanObliquityOfTheEcliptic($T);
        $epsilon = self::deg2rad(self::apparentObliquityOfTheEcliptic($T, $epsilon0));

        $declination = self::rad2deg(asin(sin($epsilon) * sin($lambda)));
        $rightAscension = self::unwindAngle(self::rad2deg(atan2(cos($epsilon) * sin($lambda), cos($lambda))));

        // AA p.185
        $e = $L0 - 0.0057183 - $rightAscension + self::nutationInLongitude($L0, $Lp, $omega) * cos($epsilon);
        $e -= 360 * round($e / 360);

        return ['declination' => $declination, 'equationOfTime' => $e / 15];
    }

    /** Geometric mean longitude of the sun (degrees). AA p.163 */
    public static function meanSolarLongitude(float $T): float
    {
        return self::unwindAngle(280.4664567 + 36000.76983 * $T + 0.0003032 * $T ** 2);
    }

    /** Geometric mean longitude of the moon (degrees). AA p.144 */
    public static function meanLunarLongitude(float $T): float
    {
        return self::unwindAngle(218.3165 + 481267.8813 * $T);
    }

    /** AA p.144 */
    public static function ascendingLunarNodeLongitude(float $T): float
    {
        return self::unwindAngle(125.04452 - 1934.136261 * $T + 0.0020708 * $T ** 2 + $T ** 3 / 450000);
    }

    /** Mean anomaly of the sun (degrees). AA p.163 */
    public static function meanSolarAnomaly(float $T): float
    {
        return self::unwindAngle(357.52911 + 35999.05029 * $T - 0.0001537 * $T ** 2);
    }

    /** Sun's equation of the center (degrees). AA p.164 */
    public static function solarEquationOfTheCenter(float $T, float $meanAnomaly): float
    {
        $m = self::deg2rad($meanAnomaly);

        return (1.914602 - 0.004817 * $T - 0.000014 * $T ** 2) * sin($m)
            + (0.019993 - 0.000101 * $T) * sin(2 * $m)
            + 0.000289 * sin(3 * $m);
    }

    /** Apparent longitude of the sun (degrees). AA p.164 */
    public static function apparentSolarLongitude(float $T, float $meanLongitude): float
    {
        $longitude = $meanLongitude + self::solarEquationOfTheCenter($T, self::meanSolarAnomaly($T));
        $omega = 125.04 - 1934.136 * $T;

        return self::unwindAngle($longitude - 0.00569 - 0.00478 * sin(self::deg2rad($omega)));
    }

    /** Mean obliquity of the ecliptic (degrees). AA p.147 */
    public static function meanObliquityOfTheEcliptic(float $T): float
    {
        return 23.439291 - 0.013004167 * $T - 0.0000001639 * $T ** 2 + 0.0000005036 * $T ** 3;
    }

    /** Apparent obliquity of the ecliptic (degrees). AA p.165 */
    public static function apparentObliquityOfTheEcliptic(float $T, float $meanObliquity): float
    {
        return $meanObliquity + 0.00256 * cos(self::deg2rad(125.04 - 1934.136 * $T));
    }

    /** Nutation in longitude (degrees). AA p.144 */
    public static function nutationInLongitude(float $L0, float $Lp, float $omega): float
    {
        return -17.2 / 3600 * sin(self::deg2rad($omega))
            - 1.32 / 3600 * sin(2 * self::deg2rad($L0))
            - 0.23 / 3600 * sin(2 * self::deg2rad($Lp))
            + 0.21 / 3600 * sin(2 * self::deg2rad($omega));
    }

    /** Julian Day for a Gregorian date at 0h UT. AA p.60 */
    public static function julianDay(int $year, int $month, int $day): float
    {
        $Y = $month > 2 ? $year : $year - 1;
        $M = $month > 2 ? $month : $month + 12;
        $A = intdiv($Y, 100);
        $B = 2 - $A + intdiv($A, 4);

        return (int) (365.25 * ($Y + 4716)) + (int) (30.6001 * ($M + 1)) + $day + $B - 1524.5;
    }

    /** AA p.163 */
    public static function julianCentury(float $jd): float
    {
        return ($jd - 2451545.0) / 36525;
    }
}
