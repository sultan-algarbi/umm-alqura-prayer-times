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
    protected function tearDown(): void
    {
        CityConfig::reset();
    }

    /**
     * Reference values generated with the Adhan library (adhan-js 4.4.6,
     * CalculationMethod.UmmAlQura, Shafi, +30 min Isha in Umm al-Qura Ramadan).
     * Rows: [city key (SA), date, fajr, sunrise, dhuhr, asr, maghrib, isha].
     */
    public function testMatchesReferenceImplementation(): void
    {
        $rows = json_decode((string) file_get_contents(__DIR__ . '/fixtures/adhan-reference.json'), true);
        $keys = ['fajr', 'sunrise', 'dhuhr', 'asr', 'maghrib', 'isha'];

        foreach ($rows as $row) {
            $times = PrayerTimes::on(City::from('sa.' . $row[0]), $row[1])->toArray();
            foreach ($keys as $i => $key) {
                self::assertSame($row[$i + 2], $times[$key], "{$row[0]} {$row[1]} {$key}");
            }
        }
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
