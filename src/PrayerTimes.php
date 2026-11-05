<?php

declare(strict_types=1);

namespace Ihsan\UmmAlQuraPrayerTimes;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use IntlCalendar;
use InvalidArgumentException;
use RuntimeException;

/**
 * Prayer (adhan) times using the official Umm al-Qura method, fully offline.
 *
 *  - Fajr:    sun 18.5° below the horizon
 *  - Sunrise: upper limb at the horizon (refraction included, -0°50')
 *  - Dhuhr:   solar transit
 *  - Asr:     shadow = object height + noon shadow (standard / Shafi'i)
 *  - Maghrib: sunset
 *  - Isha:    90 minutes after Maghrib, 120 minutes during Ramadan
 *             (Ramadan detected with the Umm al-Qura Hijri calendar)
 *
 * All times are rounded to the nearest minute, in the city's country timezone.
 */
final class PrayerTimes
{
    public const FAJR_ANGLE = 18.5;
    public const ISHA_INTERVAL = 90;
    public const ISHA_INTERVAL_RAMADAN = 120;

    /** Longest range accepted by between(). */
    public const MAX_RANGE_DAYS = 3660;

    public readonly DateTimeImmutable $fajr;
    public readonly DateTimeImmutable $sunrise;
    public readonly DateTimeImmutable $dhuhr;
    public readonly DateTimeImmutable $asr;
    public readonly DateTimeImmutable $maghrib;
    public readonly DateTimeImmutable $isha;

    /** Gregorian date these times belong to (midnight, city timezone). */
    public readonly DateTimeImmutable $date;

    /** @var array{year: int, month: int, day: int} Umm al-Qura Hijri date. */
    public readonly array $hijri;

    public readonly bool $isRamadan;

    private function __construct(public readonly City $city, DateTimeImmutable $date)
    {
        $tz = $date->getTimezone();
        $this->date = $date->setTime(0, 0);
        [$y, $m, $d] = array_map('intval', explode('-', $this->date->format('Y-n-j')));

        $this->hijri = Hijri::fromGregorian($y, $m, $d);
        $this->isRamadan = $this->hijri['month'] === 9;

        $lat = $city->latitude();
        $lng = $city->longitude();
        $solar = new SolarTime($y, $m, $d, $lat, $lng);
        $base = (float) gmmktime(0, 0, 0, $m, $d, $y);

        $dhuhr = self::componentsToTimestamp($solar->transit, $base);
        $sunrise = self::componentsToTimestamp($solar->sunrise, $base);
        $sunset = self::componentsToTimestamp($solar->sunset, $base);
        $asr = self::componentsToTimestamp($solar->afternoon(1.0), $base);
        $fajr = self::componentsToTimestamp($solar->hourAngle(-self::FAJR_ANGLE, false), $base);

        if (is_nan($sunrise) || is_nan($sunset)) {
            throw new RuntimeException('The sun does not rise or set at this location on this date.');
        }

        // Fajr safety limit (middle of the night) — no effect at Saudi latitudes,
        // kept for parity with the reference implementation.
        [$ty, $tm, $td] = array_map('intval', explode('-', $this->date->modify('+1 day')->format('Y-n-j')));
        $tomorrowSolar = new SolarTime($ty, $tm, $td, $lat, $lng);
        $tomorrowSunrise = self::componentsToTimestamp($tomorrowSolar->sunrise, (float) gmmktime(0, 0, 0, $tm, $td, $ty));
        $safeFajr = $sunrise - ($tomorrowSunrise - $sunset) / 2;
        if (is_nan($fajr) || $safeFajr > $fajr) {
            $fajr = $safeFajr;
        }

        $isha = $sunset + ($this->isRamadan ? self::ISHA_INTERVAL_RAMADAN : self::ISHA_INTERVAL) * 60;

        $this->fajr = self::toDate($fajr, $tz);
        $this->sunrise = self::toDate($sunrise, $tz);
        $this->dhuhr = self::toDate($dhuhr, $tz);
        $this->asr = self::toDate($asr, $tz);
        $this->maghrib = self::toDate($sunset, $tz);
        $this->isha = self::toDate($isha, $tz);
    }

    // ── Lookups by date ──────────────────────────────────────────────────

    /**
     * Prayer times of a city on a Gregorian date.
     *
     * @param DateTimeInterface|string|null $date a date object, "Y-m-d", or null for today (city timezone)
     */
    public static function on(City $city, DateTimeInterface|string|null $date = null): self
    {
        return new self($city, self::resolveDate($date, $city));
    }

    /** Prayer times of a city on an Umm al-Qura Hijri date, e.g. onHijri(City::SA_Makkah, 1448, 9, 1). */
    public static function onHijri(City $city, int $year, int $month, int $day): self
    {
        return self::on($city, Hijri::toGregorian($year, $month, $day));
    }

    /**
     * Every day from $from to $to inclusive.
     *
     * @return array<string, self> keyed by Gregorian "Y-m-d"
     */
    public static function between(City $city, DateTimeInterface|string $from, DateTimeInterface|string $to): array
    {
        $start = self::resolveDate($from, $city);
        $end = self::resolveDate($to, $city);
        if ($end < $start) {
            throw new InvalidArgumentException('The end date is before the start date.');
        }
        if ((int) $start->diff($end)->days >= self::MAX_RANGE_DAYS) {
            throw new InvalidArgumentException(sprintf('Range is limited to %d days.', self::MAX_RANGE_DAYS));
        }

        $result = [];
        for ($d = $start; $d <= $end; $d = $d->modify('+1 day')) {
            $result[$d->format('Y-m-d')] = new self($city, $d);
        }

        return $result;
    }

    /**
     * A whole Gregorian month.
     *
     * @return array<string, self> keyed by "Y-m-d"
     */
    public static function month(City $city, int $year, int $month): array
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Month must be between 1 and 12.');
        }
        $first = sprintf('%04d-%02d-01', $year, $month);
        $days = (int) (new DateTimeImmutable($first))->format('t');

        return self::between($city, $first, sprintf('%04d-%02d-%02d', $year, $month, $days));
    }

    /**
     * A whole Umm al-Qura Hijri month — e.g. hijriMonth(City::SA_Riyadh, 1448, 9) for Ramadan.
     *
     * @return array<string, self> keyed by Gregorian "Y-m-d"
     */
    public static function hijriMonth(City $city, int $year, int $month): array
    {
        $length = Hijri::monthLength($year, $month);

        return self::between($city, Hijri::toGregorian($year, $month, 1), Hijri::toGregorian($year, $month, $length));
    }

    /**
     * The next adhan after $now, rolling over to tomorrow's Fajr after Isha.
     *
     * @return array{prayer: Prayer, time: DateTimeImmutable}
     */
    public static function nextAdhan(City $city, ?DateTimeInterface $now = null): array
    {
        $tz = new DateTimeZone($city->timezone());
        $now = $now === null
            ? new DateTimeImmutable('now', $tz)
            : DateTimeImmutable::createFromInterface($now)->setTimezone($tz);

        $today = self::on($city, $now);
        $next = $today->nextPrayer($now);
        if ($next !== null) {
            return ['prayer' => $next, 'time' => $today->timeFor($next)];
        }

        return ['prayer' => Prayer::Fajr, 'time' => self::on($city, $now->modify('+1 day'))->fajr];
    }

    // ── Instance helpers ─────────────────────────────────────────────────

    public function timeFor(Prayer $prayer): DateTimeImmutable
    {
        return match ($prayer) {
            Prayer::Fajr => $this->fajr,
            Prayer::Sunrise => $this->sunrise,
            Prayer::Dhuhr => $this->dhuhr,
            Prayer::Asr => $this->asr,
            Prayer::Maghrib => $this->maghrib,
            Prayer::Isha => $this->isha,
        };
    }

    /** The prayer whose time has most recently started, or null before Fajr. */
    public function currentPrayer(?DateTimeInterface $now = null, bool $includeSunrise = false): ?Prayer
    {
        $now ??= new DateTimeImmutable('now', $this->date->getTimezone());
        $current = null;
        foreach (Prayer::cases() as $p) {
            if (($includeSunrise || $p->hasAdhan()) && $this->timeFor($p) <= $now) {
                $current = $p;
            }
        }

        return $current;
    }

    /** The next prayer today, or null after Isha. */
    public function nextPrayer(?DateTimeInterface $now = null, bool $includeSunrise = false): ?Prayer
    {
        $now ??= new DateTimeImmutable('now', $this->date->getTimezone());
        foreach (Prayer::cases() as $p) {
            if (($includeSunrise || $p->hasAdhan()) && $this->timeFor($p) > $now) {
                return $p;
            }
        }

        return null;
    }

    /** @return array<string, string> e.g. ['fajr' => '04:43', ...] */
    public function toArray(string $format = 'H:i'): array
    {
        $out = [];
        foreach (Prayer::cases() as $p) {
            $out[$p->value] = $this->timeFor($p)->format($format);
        }

        return $out;
    }

    // ── Internals ────────────────────────────────────────────────────────

    private static function resolveDate(DateTimeInterface|string|null $date, City $city): DateTimeImmutable
    {
        $tz = new DateTimeZone($city->timezone());

        if ($date === null) {
            return new DateTimeImmutable('today', $tz);
        }
        if (is_string($date)) {
            if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                throw new InvalidArgumentException(sprintf('Invalid date "%s"; expected Y-m-d.', $date));
            }

            return new DateTimeImmutable($date, $tz);
        }

        // The calendar day as seen in the city's timezone.
        return DateTimeImmutable::createFromInterface($date)->setTimezone($tz)->setTime(0, 0);
    }

    /** UT decimal hours → UNIX timestamp, truncated to whole seconds (as in Adhan). */
    private static function componentsToTimestamp(float $hours, float $midnightUtc): float
    {
        if (is_nan($hours)) {
            return NAN;
        }
        $h = floor($hours);
        $m = floor(($hours - $h) * 60);
        $s = floor(($hours - ($h + $m / 60)) * 3600);

        return $midnightUtc + $h * 3600 + $m * 60 + $s;
    }

    /** Round to the nearest minute (≥30 s rounds up). */
    private static function toDate(float $timestamp, DateTimeZone $tz): DateTimeImmutable
    {
        $whole = (int) floor($timestamp);
        $seconds = (($whole % 60) + 60) % 60;
        $rounded = $whole + ($seconds >= 30 ? 60 - $seconds : -$seconds);

        return (new DateTimeImmutable('@' . $rounded))->setTimezone($tz);
    }
}
