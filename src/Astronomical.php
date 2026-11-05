<?php

declare(strict_types=1);

namespace Ihsan\UmmAlQuraPrayerTimes;

/**
 * Solar-position algorithms from Jean Meeus, "Astronomical Algorithms" (2nd ed.).
 *
 * This is a faithful port of the reference implementation used by the Adhan
 * libraries (adhan-java / adhan-swift / adhan-js), so results are identical to them.
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

    public static function quadrantShiftAngle(float $angle): float
    {
        if ($angle >= -180 && $angle <= 180) {
            return $angle;
        }

        // JS Math.round rounds .5 toward +infinity
        return $angle - 360 * floor($angle / 360 + 0.5);
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
        $lambda = $longitude - 0.00569 - 0.00478 * sin(self::deg2rad($omega));

        return self::unwindAngle($lambda);
    }

    /** Mean obliquity of the ecliptic (degrees). AA p.147 */
    public static function meanObliquityOfTheEcliptic(float $T): float
    {
        return 23.439291 - 0.013004167 * $T - 0.0000001639 * $T ** 2 + 0.0000005036 * $T ** 3;
    }

    /** Apparent obliquity of the ecliptic (degrees). AA p.165 */
    public static function apparentObliquityOfTheEcliptic(float $T, float $meanObliquity): float
    {
        $o = 125.04 - 1934.136 * $T;

        return $meanObliquity + 0.00256 * cos(self::deg2rad($o));
    }

    /** Mean sidereal time (degrees). AA p.165 */
    public static function meanSiderealTime(float $T): float
    {
        $jd = $T * 36525 + 2451545.0;
        $theta = 280.46061837
            + 360.98564736629 * ($jd - 2451545)
            + 0.000387933 * $T ** 2
            - $T ** 3 / 38710000;

        return self::unwindAngle($theta);
    }

    /** AA p.144 */
    public static function nutationInLongitude(float $L0, float $Lp, float $omega): float
    {
        return -17.2 / 3600 * sin(self::deg2rad($omega))
            - 1.32 / 3600 * sin(2 * self::deg2rad($L0))
            - 0.23 / 3600 * sin(2 * self::deg2rad($Lp))
            + 0.21 / 3600 * sin(2 * self::deg2rad($omega));
    }

    /** AA p.144 */
    public static function nutationInObliquity(float $L0, float $Lp, float $omega): float
    {
        return 9.2 / 3600 * cos(self::deg2rad($omega))
            + 0.57 / 3600 * cos(2 * self::deg2rad($L0))
            + 0.1 / 3600 * cos(2 * self::deg2rad($Lp))
            - 0.09 / 3600 * cos(2 * self::deg2rad($omega));
    }

    /** AA p.93 */
    public static function altitudeOfCelestialBody(float $latitude, float $declination, float $hourAngle): float
    {
        $t1 = sin(self::deg2rad($latitude)) * sin(self::deg2rad($declination));
        $t2 = cos(self::deg2rad($latitude)) * cos(self::deg2rad($declination)) * cos(self::deg2rad($hourAngle));

        return self::rad2deg(asin($t1 + $t2));
    }

    /** AA p.102 */
    public static function approximateTransit(float $longitude, float $siderealTime, float $rightAscension): float
    {
        $lw = -$longitude;
        $m0 = self::normalizeToScale(($rightAscension + $lw - $siderealTime) / 360, 1);

        $expected = self::normalizeToScale((12.0 - $longitude / 15.0) / 24.0, 1);
        if ($m0 - $expected > 0.5) {
            return $m0 - 1.0;
        }
        if ($expected - $m0 > 0.5) {
            return $m0 + 1.0;
        }

        return $m0;
    }

    /** Solar noon in UT hours. AA p.102 */
    public static function correctedTransit(
        float $m0,
        float $longitude,
        float $siderealTime,
        float $ra,
        float $prevRa,
        float $nextRa,
    ): float {
        $lw = -$longitude;
        $theta = self::unwindAngle($siderealTime + 360.985647 * $m0);
        $a = self::unwindAngle(self::interpolateAngles($ra, $prevRa, $nextRa, $m0));
        $h = self::quadrantShiftAngle($theta - $lw - $a);
        $dm = $h / -360;

        return ($m0 + $dm) * 24;
    }

    /** UT hours at which the sun reaches altitude $h0. AA p.102 */
    public static function correctedHourAngle(
        float $m0,
        float $h0,
        float $latitude,
        float $longitude,
        bool $afterTransit,
        float $siderealTime,
        float $ra,
        float $prevRa,
        float $nextRa,
        float $dec,
        float $prevDec,
        float $nextDec,
    ): float {
        $lw = -$longitude;
        $t1 = sin(self::deg2rad($h0)) - sin(self::deg2rad($latitude)) * sin(self::deg2rad($dec));
        $t2 = cos(self::deg2rad($latitude)) * cos(self::deg2rad($dec));
        $ratio = $t1 / $t2;
        if ($ratio < -1 || $ratio > 1) {
            return NAN;
        }
        $H0 = self::rad2deg(acos($ratio));
        $m = $afterTransit ? $m0 + $H0 / 360 : $m0 - $H0 / 360;
        $theta = self::unwindAngle($siderealTime + 360.985647 * $m);
        $a = self::unwindAngle(self::interpolateAngles($ra, $prevRa, $nextRa, $m));
        $delta = self::interpolate($dec, $prevDec, $nextDec, $m);
        $H = $theta - $lw - $a;
        $h = self::altitudeOfCelestialBody($latitude, $delta, $H);
        $t3 = $h - $h0;
        $t4 = 360 * cos(self::deg2rad($delta)) * cos(self::deg2rad($latitude)) * sin(self::deg2rad($H));
        $dm = $t3 / $t4;

        return ($m + $dm) * 24;
    }

    /** AA p.24 */
    public static function interpolate(float $y2, float $y1, float $y3, float $n): float
    {
        $a = $y2 - $y1;
        $b = $y3 - $y2;
        $c = $b - $a;

        return $y2 + $n / 2 * ($a + $b + $n * $c);
    }

    /** AA p.24, with angle unwinding. */
    public static function interpolateAngles(float $y2, float $y1, float $y3, float $n): float
    {
        $a = self::unwindAngle($y2 - $y1);
        $b = self::unwindAngle($y3 - $y2);
        $c = $b - $a;

        return $y2 + $n / 2 * ($a + $b + $n * $c);
    }

    /** Julian Day for a Gregorian date at 0h UT. AA p.60 */
    public static function julianDay(int $year, int $month, int $day, float $hours = 0.0): float
    {
        $Y = $month > 2 ? $year : $year - 1;
        $M = $month > 2 ? $month : $month + 12;
        $D = $day + $hours / 24;
        $A = intdiv($Y, 100);
        $B = (int) (2 - $A + intdiv($A, 4));
        $i0 = (int) (365.25 * ($Y + 4716));
        $i1 = (int) (30.6001 * ($M + 1));

        return $i0 + $i1 + $D + $B - 1524.5;
    }

    /** AA p.163 */
    public static function julianCentury(float $jd): float
    {
        return ($jd - 2451545.0) / 36525;
    }
}
