<?php

declare(strict_types=1);

namespace Ihsan\UmmAlQuraPrayerTimes;

/**
 * Sun position for one day plus the derived event times (UT hours).
 *
 * @internal
 */
final class SolarTime
{
    public readonly float $transit;
    public readonly float $sunrise;
    public readonly float $sunset;

    private readonly float $approxTransit;
    /** @var array{dec: float, ra: float, sidereal: float} */
    private readonly array $solar;
    /** @var array{dec: float, ra: float, sidereal: float} */
    private readonly array $prev;
    /** @var array{dec: float, ra: float, sidereal: float} */
    private readonly array $next;

    public function __construct(
        int $year,
        int $month,
        int $day,
        private readonly float $latitude,
        private readonly float $longitude,
    ) {
        $jd = Astronomical::julianDay($year, $month, $day);
        $this->solar = self::coordinates($jd);
        $this->prev = self::coordinates($jd - 1);
        $this->next = self::coordinates($jd + 1);

        $this->approxTransit = Astronomical::approximateTransit($longitude, $this->solar['sidereal'], $this->solar['ra']);
        $this->transit = Astronomical::correctedTransit(
            $this->approxTransit,
            $longitude,
            $this->solar['sidereal'],
            $this->solar['ra'],
            $this->prev['ra'],
            $this->next['ra'],
        );

        $solarAltitude = -50 / 60.0; // refraction + solar semi-diameter
        $this->sunrise = $this->hourAngle($solarAltitude, false);
        $this->sunset = $this->hourAngle($solarAltitude, true);
    }

    public function hourAngle(float $angle, bool $afterTransit): float
    {
        return Astronomical::correctedHourAngle(
            $this->approxTransit,
            $angle,
            $this->latitude,
            $this->longitude,
            $afterTransit,
            $this->solar['sidereal'],
            $this->solar['ra'],
            $this->prev['ra'],
            $this->next['ra'],
            $this->solar['dec'],
            $this->prev['dec'],
            $this->next['dec'],
        );
    }

    /** Asr: shadow length = $shadowLength × object height + noon shadow. */
    public function afternoon(float $shadowLength): float
    {
        $tangent = abs($this->latitude - $this->solar['dec']);
        $inverse = $shadowLength + tan(Astronomical::deg2rad($tangent));
        $angle = Astronomical::rad2deg(atan(1.0 / $inverse));

        return $this->hourAngle($angle, true);
    }

    /**
     * Apparent solar coordinates. AA p.165 and p.88.
     *
     * @return array{dec: float, ra: float, sidereal: float}
     */
    private static function coordinates(float $jd): array
    {
        $T = Astronomical::julianCentury($jd);
        $L0 = Astronomical::meanSolarLongitude($T);
        $Lp = Astronomical::meanLunarLongitude($T);
        $omega = Astronomical::ascendingLunarNodeLongitude($T);
        $lambda = Astronomical::deg2rad(Astronomical::apparentSolarLongitude($T, $L0));
        $theta0 = Astronomical::meanSiderealTime($T);
        $dPsi = Astronomical::nutationInLongitude($L0, $Lp, $omega);
        $dEpsilon = Astronomical::nutationInObliquity($L0, $Lp, $omega);
        $epsilon0 = Astronomical::meanObliquityOfTheEcliptic($T);
        $epsilonApp = Astronomical::deg2rad(Astronomical::apparentObliquityOfTheEcliptic($T, $epsilon0));

        return [
            'dec' => Astronomical::rad2deg(asin(sin($epsilonApp) * sin($lambda))),
            'ra' => Astronomical::unwindAngle(Astronomical::rad2deg(atan2(cos($epsilonApp) * sin($lambda), cos($lambda)))),
            'sidereal' => $theta0 + $dPsi * 3600 * cos(Astronomical::deg2rad($epsilon0 + $dEpsilon)) / 3600,
        ];
    }
}
