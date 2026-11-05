<?php

declare(strict_types=1);

namespace Ihsan\UmmAlQuraPrayerTimes;

use IntlCalendar;
use InvalidArgumentException;
use RuntimeException;

/**
 * Umm al-Qura Hijri calendar conversions (ICU via ext-intl, table-based for 1300–1600 AH).
 */
final class Hijri
{
    public const MIN_YEAR = 1300;
    public const MAX_YEAR = 1600;

    /** @return array{year: int, month: int, day: int} */
    public static function fromGregorian(int $year, int $month, int $day): array
    {
        $cal = self::calendar();
        $cal->setTime(gmmktime(12, 0, 0, $month, $day, $year) * 1000.0);

        return [
            'year' => $cal->get(IntlCalendar::FIELD_YEAR),
            'month' => $cal->get(IntlCalendar::FIELD_MONTH) + 1,
            'day' => $cal->get(IntlCalendar::FIELD_DAY_OF_MONTH),
        ];
    }

    /** @return string Gregorian "Y-m-d" */
    public static function toGregorian(int $year, int $month, int $day): string
    {
        $length = self::monthLength($year, $month);
        if ($day < 1 || $day > $length) {
            throw new InvalidArgumentException(sprintf('Hijri month %d/%d has %d days.', $month, $year, $length));
        }

        $cal = self::calendar();
        $cal->clear();
        $cal->set($year, $month - 1, $day, 12, 0, 0);

        return gmdate('Y-m-d', (int) ($cal->getTime() / 1000));
    }

    public static function monthLength(int $year, int $month): int
    {
        if ($year < self::MIN_YEAR || $year > self::MAX_YEAR) {
            throw new InvalidArgumentException(sprintf('Hijri year must be between %d and %d.', self::MIN_YEAR, self::MAX_YEAR));
        }
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Hijri month must be between 1 and 12.');
        }

        $cal = self::calendar();
        $cal->clear();
        $cal->set($year, $month - 1, 1, 12, 0, 0);

        return $cal->getActualMaximum(IntlCalendar::FIELD_DAY_OF_MONTH);
    }

    private static function calendar(): IntlCalendar
    {
        $cal = IntlCalendar::createInstance('UTC', 'en_US@calendar=islamic-umalqura');
        if ($cal === null) {
            throw new RuntimeException('ext-intl could not create the Umm al-Qura calendar.');
        }

        return $cal;
    }
}
