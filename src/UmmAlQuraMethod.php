<?php

declare(strict_types=1);

namespace Ihsan\UmmAlQuraPrayerTimes;

/**
 * The Umm al-Qura calculation, calibrated against the official calendar (ummulqura.org.sa).
 *
 * Each time is derived from the sun's declination and the equation of time, sampled at a
 * fixed point of the local day, the way the official tables are produced:
 *
 *   prayer   declination at   equation of time at   rule
 *   Fajr     end of day       noon                  sun 18.5° below the horizon
 *   Sunrise  06:00            06:00                 upper limb at the horizon (-0°50')
 *   Dhuhr    —                noon                  solar transit
 *   Asr      end of day       noon                  shadow = object + noon shadow
 *   Maghrib  18:00            18:00                 upper limb at the horizon (-0°50')
 *   Isha     Maghrib + 90 minutes (120 in Ramadan)
 *
 * @internal
 */
final class UmmAlQuraMethod
{
    public const FAJR_ANGLE = 18.5;
    public const HORIZON_ANGLE = 0.833;

    /** [declination sample, equation-of-time sample] as fractions of the local day. */
    private const SAMPLES = [
        'fajr' => [1.0, 0.5],
        'sunrise' => [0.25, 0.25],
        'dhuhr' => [0.5, 0.5],
        'asr' => [1.0, 0.5],
        'maghrib' => [0.75, 0.75],
    ];

    /**
     * Unrounded UNIX timestamps (float seconds) of each time on a Gregorian date.
     *
     * @return array{fajr: float, sunrise: float, dhuhr: float, asr: float, maghrib: float, isha: float}
     */
    public static function compute(int $year, int $month, int $day, float $latitude, float $longitude, int $ishaMinutes): array
    {
        $localDay = Astronomical::julianDay($year, $month, $day) - $longitude / 360;
        $midnightUtc = (float) gmmktime(0, 0, 0, $month, $day, $year);

        $times = [];
        foreach (self::SAMPLES as $prayer => [$decSample, $eqtSample]) {
            $declination = Astronomical::sunPosition($localDay + $decSample)['declination'];
            $noon = 12 - Astronomical::sunPosition($localDay + $eqtSample)['equationOfTime'];

            $localHours = match ($prayer) {
                'dhuhr' => $noon,
                'fajr' => $noon - self::hourAngle(self::FAJR_ANGLE, $latitude, $declination),
                'sunrise' => $noon - self::hourAngle(self::HORIZON_ANGLE, $latitude, $declination),
                'maghrib' => $noon + self::hourAngle(self::HORIZON_ANGLE, $latitude, $declination),
                'asr' => $noon + self::hourAngle(self::asrAltitude($latitude, $declination), $latitude, $declination),
            };

            $times[$prayer] = $midnightUtc + ($localHours - $longitude / 15) * 3600;
        }

        $times['isha'] = $times['maghrib'] + $ishaMinutes * 60;

        return $times;
    }

    /**
     * Hours between solar noon and the moment the sun is $angle degrees below the horizon
     * (a negative angle means above it).
     */
    private static function hourAngle(float $angle, float $latitude, float $declination): float
    {
        $lat = Astronomical::deg2rad($latitude);
        $dec = Astronomical::deg2rad($declination);
        $cos = (-sin(Astronomical::deg2rad($angle)) - sin($dec) * sin($lat)) / (cos($dec) * cos($lat));

        if ($cos < -1 || $cos > 1) {
            return NAN;
        }

        return Astronomical::rad2deg(acos($cos)) / 15;
    }

    /** Angle (degrees below the horizon, so negative) at which a shadow equals the object plus its noon shadow. */
    private static function asrAltitude(float $latitude, float $declination): float
    {
        $tan = tan(Astronomical::deg2rad(abs($latitude - $declination)));

        return -Astronomical::rad2deg(atan(1 / (1 + $tan)));
    }
}
