<?php

declare(strict_types=1);

namespace Ihsan\UmmAlQuraPrayerTimes\Tests;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Ihsan\UmmAlQuraPrayerTimes\City;
use Ihsan\UmmAlQuraPrayerTimes\CityConfig;
use Ihsan\UmmAlQuraPrayerTimes\Country;
use Ihsan\UmmAlQuraPrayerTimes\Hijri;
use Ihsan\UmmAlQuraPrayerTimes\Prayer;
use Ihsan\UmmAlQuraPrayerTimes\PrayerTimes;

final class PrayerTimesTest extends TestCase
{
    /** Official fixture file => city. */
    private const OFFICIAL = [
        'riyadh' => City::SA_Riyadh,
        'jeddah' => City::SA_Jeddah,
        'mecca' => City::SA_Makkah,
        'medina' => City::SA_Madinah,
        'dammam' => City::SA_Dammam,
        'tabuk' => City::SA_Tabuk,
        'jazan' => City::SA_Jazan,
        'arar' => City::SA_Arar,
        'hail' => City::SA_Hail,
    ];

    /** Dates on which the official 2026 tables repeat the previous day's row (see fixtures/official/README.md). */
    private const REPEATED_DATES = [
        '2026-01-23', '2026-02-21', '2026-03-23', '2026-04-21', '2026-05-20', '2026-06-19',
        '2026-07-18', '2026-08-17', '2026-09-15', '2026-10-15', '2026-11-13', '2026-12-12',
    ];

    private const KEYS = ['fajr', 'sunrise', 'dhuhr', 'asr', 'maghrib', 'isha'];

    protected function tearDown(): void
    {
        CityConfig::reset();
    }

    /** @return iterable<array{City, list<string>}> each official row with its city */
    private static function officialRows(): iterable
    {
        foreach (self::OFFICIAL as $file => $city) {
            foreach (file(__DIR__ . "/fixtures/official/{$file}-2026.txt", FILE_IGNORE_NEW_LINES) as $line) {
                yield [$city, explode('|', $line)];
            }
        }
    }

    private static function minutes(string $hhmm): int
    {
        return (int) substr($hhmm, 0, 2) * 60 + (int) substr($hhmm, 3, 2);
    }

    /**
     * 9 cities × 365 days of the official Umm al-Qura calendar:
     * at least 97.5% of times identical to the minute, and never more than one minute apart
     * outside the source's repeated dates (and the day after them).
     */
    public function testMatchesOfficialCalendar(): void
    {
        $exact = 0;
        $total = 0;
        $farOff = [];

        foreach (self::officialRows() as [$city, $row]) {
            $date = $row[0];
            $times = PrayerTimes::on($city, $date)->toArray();
            $repeated = in_array($date, self::REPEATED_DATES, true);
            $dayAfter = in_array((new DateTimeImmutable($date))->modify('-1 day')->format('Y-m-d'), self::REPEATED_DATES, true);

            foreach (self::KEYS as $i => $key) {
                $official = $row[$i + 2];
                if (!$repeated) {
                    $total++;
                    $exact += (int) ($times[$key] === $official);
                }
                if (!$repeated && !$dayAfter && abs(self::minutes($times[$key]) - self::minutes($official)) > 1) {
                    $farOff[] = "{$city->value} {$date} {$key}: official {$official}, package {$times[$key]}";
                }
            }
        }

        self::assertSame([], $farOff, 'Times more than one minute from the official calendar');
        self::assertGreaterThanOrEqual(0.975, $exact / $total, sprintf('Exact match rate %.2f%%', 100 * $exact / $total));
    }

    public function testHijriDatesMatchOfficialCalendar(): void
    {
        foreach (self::officialRows() as [$city, $row]) {
            $h = PrayerTimes::on($city, $row[0])->hijri;
            self::assertSame($row[1], "{$h['year']}-{$h['month']}-{$h['day']}", "{$city->value} {$row[0]}");
        }
    }

    /** Spot check: the official site's times for Riyadh on 5 November 2026 (25 Jumada I 1448). */
    public function testOfficialRiyadhToday(): void
    {
        self::assertSame(
            ['fajr' => '04:44', 'sunrise' => '06:02', 'dhuhr' => '11:37', 'asr' => '14:48', 'maghrib' => '17:11', 'isha' => '18:41'],
            PrayerTimes::on(City::SA_Riyadh, '2026-11-05')->toArray(),
        );
    }

    public function testEveryCityIsConfigured(): void
    {
        foreach (City::cases() as $city) {
            self::assertNotSame('', $city->nameAr());
            self::assertSame('Asia/Riyadh', $city->timezone());
        }
        self::assertCount(count(City::cases()), Country::SA->cities());
    }

    public function testCountryPlusCityNaming(): void
    {
        self::assertSame('sa.riyadh', City::SA_Riyadh->value);
        self::assertSame(Country::SA, City::SA_Riyadh->country());
        self::assertSame('riyadh', City::SA_Riyadh->key());
        self::assertSame('السعودية - الرياض', City::SA_Riyadh->fullNameAr());
        self::assertSame('Saudi Arabia - Riyadh', City::SA_Riyadh->fullNameEn());
        self::assertSame('السعودية - مكة المكرمة', City::options()['sa.makkah']);
        self::assertSame('Jeddah', City::options('en', false)['sa.jeddah']);
    }

    public function testDateFunctions(): void
    {
        $t = PrayerTimes::on(City::SA_Riyadh, '2026-11-05');
        self::assertSame('2026-11-05', $t->date->format('Y-m-d'));
        self::assertSame($t->toArray(), prayer_times(City::SA_Riyadh, '2026-11-05')->toArray());

        // Same calendar day whatever timezone the input object carries
        $utcLate = new DateTimeImmutable('2026-11-04 22:30', new DateTimeZone('UTC')); // 01:30 on the 5th in Riyadh
        self::assertSame('2026-11-05', PrayerTimes::on(City::SA_Riyadh, $utcLate)->date->format('Y-m-d'));

        $range = PrayerTimes::between(City::SA_Riyadh, '2026-12-30', '2027-01-02');
        self::assertSame(['2026-12-30', '2026-12-31', '2027-01-01', '2027-01-02'], array_keys($range));

        self::assertCount(28, PrayerTimes::month(City::SA_Riyadh, 2026, 2));
    }

    public function testInvalidDatesAreRejected(): void
    {
        foreach (['2026-02-30', '05-11-2026', '2026/11/05'] as $bad) {
            try {
                PrayerTimes::on(City::SA_Riyadh, $bad);
                self::fail("accepted $bad");
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function testHijriDates(): void
    {
        $t = PrayerTimes::onHijri(City::SA_Makkah, 1448, 9, 8);
        self::assertSame('2027-02-15', $t->date->format('Y-m-d'));
        self::assertSame(['year' => 1448, 'month' => 9, 'day' => 8], $t->hijri);

        $ramadan = PrayerTimes::hijriMonth(City::SA_Makkah, 1448, 9);
        self::assertCount(Hijri::monthLength(1448, 9), $ramadan);
        foreach ($ramadan as $day) {
            self::assertTrue($day->isRamadan);
            self::assertSame(9, $day->hijri['month']);
        }
    }

    public function testRamadanIshaIs120Minutes(): void
    {
        $t = PrayerTimes::on(City::SA_Makkah, '2027-02-15');
        self::assertTrue($t->isRamadan);
        self::assertSame(120 * 60, $t->isha->getTimestamp() - $t->maghrib->getTimestamp());

        $t = PrayerTimes::on(City::SA_Makkah, '2026-11-05');
        self::assertFalse($t->isRamadan);
        self::assertSame(90 * 60, $t->isha->getTimestamp() - $t->maghrib->getTimestamp());
    }

    public function testConfigOverrideMovesCityCentre(): void
    {
        $before = PrayerTimes::on(City::SA_Riyadh, '2026-11-05')->dhuhr;

        CityConfig::set(['SA' => ['riyadh' => ['longitude' => 46.1753]]]); // 0.5° west ≈ 2 min later
        self::assertSame('الرياض', City::SA_Riyadh->nameAr()); // untouched fields kept
        $after = PrayerTimes::on(City::SA_Riyadh, '2026-11-05')->dhuhr;
        self::assertSame(120, $after->getTimestamp() - $before->getTimestamp());

        CityConfig::reset();
        self::assertEquals($before, PrayerTimes::on(City::SA_Riyadh, '2026-11-05')->dhuhr);
    }

    public function testConfigRejectsUnknownEntries(): void
    {
        $bad = [
            ['XX' => []],                                         // unknown country
            ['SA' => ['cairo' => ['latitude' => 30]]],            // city not in enum
            ['SA' => ['riyadh' => ['latitude' => 95]]],           // out of range
            ['SA' => ['riyadh' => ['timezone' => 'UTC']]],        // unknown field
        ];
        foreach ($bad as $config) {
            try {
                CityConfig::set($config);
                self::fail('accepted ' . json_encode($config));
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function testNextAdhanRollsOverToTomorrow(): void
    {
        $tz = new DateTimeZone('Asia/Riyadh');
        $next = PrayerTimes::nextAdhan(City::SA_Riyadh, new DateTimeImmutable('2026-11-05 23:00', $tz));
        self::assertSame(Prayer::Fajr, $next['prayer']);
        self::assertSame('2026-11-06', $next['time']->format('Y-m-d'));

        $t = PrayerTimes::on(City::SA_Riyadh, '2026-11-05');
        self::assertSame(Prayer::Dhuhr, $t->nextPrayer(new DateTimeImmutable('2026-11-05 09:00', $tz)));
        self::assertSame(Prayer::Fajr, $t->currentPrayer(new DateTimeImmutable('2026-11-05 09:00', $tz)));
    }
}
