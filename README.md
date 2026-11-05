# Umm al-Qura Prayer Times — PHP & Laravel

[![Tests](https://github.com/sultan-algarbi/umm-alqura-prayer-times/actions/workflows/tests.yml/badge.svg)](https://github.com/sultan-algarbi/umm-alqura-prayer-times/actions/workflows/tests.yml)
[![Packagist](https://img.shields.io/packagist/v/ihsan/umm-alqura-prayer-times)](https://packagist.org/packages/ihsan/umm-alqura-prayer-times)

English | **[العربية](README.ar.md)**

Islamic prayer (adhan) times using the official **Umm al-Qura** method, computed **fully offline**. Works in any PHP project, with ready-made **Laravel** integration.
Cities are a required `enum` (country + city), and their centre coordinates live in an editable config file.

**Calibrated against the official calendar:** the package matches the [official Umm al-Qura Calendar](https://www.ummulqura.org.sa) to the minute for **97.9%** of times, is never more than one minute apart otherwise, and matches **every** Hijri date (see [Accuracy](#accuracy)).

## Contents

- [Calculation method](#calculation-method)
- [Requirements and installation](#requirements-and-installation)
- [Usage in PHP](#usage-in-php)
- [Usage in Laravel](#usage-in-laravel)
- [Configuration](#configuration-configcitiesphp)
- [Accuracy](#accuracy)

## Calculation method

| Time | Rule |
|---|---|
| Fajr | Sun 18.5° below the horizon |
| Sunrise | Upper limb of the sun at the horizon (refraction included) |
| Dhuhr | Solar transit |
| Asr | Shadow = object height + noon shadow (standard) |
| Maghrib | Sunset |
| Isha | 90 minutes after Maghrib, **120 minutes in Ramadan, automatically** |

- Sun position from Jean Meeus, *Astronomical Algorithms*, sampled for each prayer at the point of the day the official calendar uses.
- **Rounding as in the official tables:** prayer times are rounded **up** to the minute, so an adhan is never announced early, and sunrise is rounded **down**, so it never shows the end of Fajr late.
- City coordinates are the same ones the official calendar uses.
- Ramadan is detected automatically from the Umm al-Qura Hijri calendar.
- Times are in the city's country timezone, with no manual offsets: the result is the adhan time at the city centre.

## Requirements and installation

- PHP 8.1 or later, with the `intl` and `mbstring` extensions
- Laravel is optional: versions 12 and 13 are supported and tested. Laravel 10 and 11 are end-of-life and Composer now blocks them over unpatched security advisories.

```bash
composer require ihsan/umm-alqura-prayer-times
```

## Usage in PHP

### Cities: country + city

Each city is a case of the `City` enum, named `{COUNTRY}_{City}` with the value `{country}.{city}`:

```php
use Ihsan\UmmAlQuraPrayerTimes\City;
use Ihsan\UmmAlQuraPrayerTimes\Country;

City::SA_Riyadh->value;          // 'sa.riyadh'  ← stable, safe to store in a database
City::SA_Riyadh->country();      // Country::SA
City::SA_Riyadh->nameEn();       // 'Riyadh'
City::SA_Riyadh->fullNameEn();   // 'Saudi Arabia - Riyadh'
City::SA_Riyadh->fullNameAr();   // 'السعودية - الرياض'

// From a form or HTTP request value
City::from($value);      // throws for an unknown city
City::tryFrom($value);   // returns null

// For dropdowns
City::options('en');                     // ['sa.riyadh' => 'Saudi Arabia - Riyadh', ...]
City::options('en', withCountry: false); // ['sa.riyadh' => 'Riyadh', ...]
City::options('ar', country: Country::SA);
Country::SA->cities();                   // every Saudi city
```

### Prayer times by date

```php
use Ihsan\UmmAlQuraPrayerTimes\PrayerTimes;

// A Gregorian date (or null for today in the city's timezone)
$t = PrayerTimes::on(City::SA_Riyadh, '2026-11-05');
$t = prayer_times(City::SA_Riyadh, '2026-11-05');   // helper function

$t->fajr->format('H:i');   // DateTimeImmutable in the city's timezone
$t->toArray();
// ['fajr' => '04:44', 'sunrise' => '06:02', 'dhuhr' => '11:37',
//  'asr' => '14:48', 'maghrib' => '17:11', 'isha' => '18:41']
$t->hijri;       // ['year' => 1448, 'month' => 5, 'day' => 25]
$t->isRamadan;   // false

// A Hijri date
$t = PrayerTimes::onHijri(City::SA_Makkah, 1448, 9, 1);

// A date range (array keyed by Y-m-d)
PrayerTimes::between(City::SA_Jeddah, '2026-12-01', '2026-12-31');

// A whole Gregorian month
PrayerTimes::month(City::SA_Madinah, 2026, 12);

// A whole Hijri month — e.g. a Ramadan timetable
foreach (PrayerTimes::hijriMonth(City::SA_Riyadh, 1448, 9) as $date => $day) {
    echo "Ramadan {$day->hijri['day']} ($date): " . implode(' ', $day->toArray()) . PHP_EOL;
}

// The next adhan (rolls over to tomorrow's Fajr after Isha)
['prayer' => $p, 'time' => $time] = PrayerTimes::nextAdhan(City::SA_Makkah);
echo $p->name . ' ' . $time->format('H:i');   // or $p->arabicName()

// Current and next prayer within a day
$t->currentPrayer();   // Prayer|null
$t->nextPrayer();      // Prayer|null
```

A date string must be a valid `Y-m-d` date, otherwise an `InvalidArgumentException` is thrown.
A date object in another timezone is read as the calendar day in the city's timezone.

## Usage in Laravel

The service provider is registered automatically through package discovery.

### Publish the config (optional)

```bash
php artisan vendor:publish --tag=prayer-times-config
```

This creates `config/prayer-cities.php`. Edit any city centre there and it is applied when the application boots. It works with `php artisan config:cache`.

### Validating a city in a request

```php
use Illuminate\Validation\Rule;
use Ihsan\UmmAlQuraPrayerTimes\City;

$request->validate([
    'city' => ['required', Rule::enum(City::class)],
    'date' => ['nullable', 'date_format:Y-m-d'],
]);
```

### Storing the city with Eloquent

```php
use Ihsan\UmmAlQuraPrayerTimes\City;

protected function casts(): array
{
    return ['city' => City::class];   // stored as 'sa.riyadh', read as City::SA_Riyadh
}
```

### Controller

```php
use Ihsan\UmmAlQuraPrayerTimes\City;
use Ihsan\UmmAlQuraPrayerTimes\PrayerTimes;

public function show(Request $request)
{
    $times = PrayerTimes::on(City::from($request->city), $request->date);

    return response()->json([
        'city'  => $times->city->fullNameEn(),
        'hijri' => $times->hijri,
        'times' => $times->toArray(),
    ]);
}
```

### City dropdown in Blade

```blade
<select name="city">
    @foreach (\Ihsan\UmmAlQuraPrayerTimes\City::options('en') as $value => $label)
        <option value="{{ $value }}" @selected(old('city') === $value)>{{ $label }}</option>
    @endforeach
</select>
```

## Configuration (`config/cities.php`)

Two levels only: **country → cities**.

```php
return [
    'SA' => [
        'riyadh' => ['name_ar' => 'الرياض', 'name_en' => 'Riyadh', 'latitude' => 24.67, 'longitude' => 46.69],
        'jeddah' => ['name_ar' => 'جدة',    'name_en' => 'Jeddah', 'latitude' => 21.5,  'longitude' => 39.17],
        // ...
    ],
];
```

The country's name and timezone are defined in the `Country` enum.

### Changing a city centre without editing the package

**In Laravel:** publish the config as above and edit it.

**In plain PHP:** create a file with **only what you want to change**, and load it once at startup:

```php
// config/prayer-cities.php
return [
    'SA' => [
        'riyadh' => ['latitude' => 24.6877, 'longitude' => 46.7219],
    ],
];
```

```php
use Ihsan\UmmAlQuraPrayerTimes\CityConfig;

CityConfig::load(__DIR__ . '/config/prayer-cities.php');
// or directly from an array:
CityConfig::set(['SA' => ['riyadh' => ['latitude' => 24.6877, 'longitude' => 46.7219]]]);
```

Your values are merged over the defaults, so fields and cities you leave out stay as they are.
Unknown keys, cities that aren't in the enum, and out-of-range coordinates are rejected.

### Adding a city

1. Add a case to `src/City.php`, e.g. `case SA_Afif = 'sa.afif';`
2. Add its data to `config/cities.php` under `'SA'`:
   `'afif' => ['name_ar' => 'عفيف', 'name_en' => 'Afif', 'latitude' => 23.91, 'longitude' => 42.92],`

If you forget step 2, the package throws a clear error naming the missing city.
To add a country, add its case to `src/Country.php` (name and timezone), then its key in the config.

### Built-in cities

Makkah, Jeddah, Taif, Rabigh, Al Qunfudhah, Madinah, Yanbu, AlUla, Riyadh, Al Kharj, Al Majmaah, Az Zulfi, Ad Dawadmi, Wadi Al Dawasir, Dammam, Al Khobar, Dhahran, Qatif, Jubail, Al Hofuf (Al-Ahsa), Hafar Al-Batin, Khafji, Buraydah, Unaizah, Ar Rass, Hail, Tabuk, Al Wajh, Abha, Khamis Mushait, Bisha, Al Baha, Jazan, Sabya, Najran, Sharurah, Arar, Turaif, Sakaka, Al Qurayyat.

## Accuracy

The package was compared with the official tables for nine cities across the country (Riyadh, Jeddah, Makkah, Madinah, Dammam, Tabuk, Jazan, Arar, Hail): 3,285 days and 19,710 times.

| | Result |
|---|---|
| Identical to the minute | **97.9%** (Fajr 98.2%, Sunrise 96.4%, Dhuhr 98.5%, Asr 98.0%, Maghrib and Isha 98.1%) |
| Largest difference | One minute |
| Hijri dates | **3,285 of 3,285** |

The remaining differences are mostly times that fall within a few seconds of a minute boundary, where tiny numerical differences decide the rounding.

**About the official data:** about once per Hijri month (around the 3rd or 4th day, 12 dates in 2026), the official site repeats the previous day's times unchanged, in every city and in every view (yearly, monthly and Hijri). The package computes those days normally instead of repeating the previous day, so it can differ from the site by one or two minutes on those dates (and sometimes the day after). Those dates are excluded from the figures above.

## Testing

```bash
composer install && composer test
```

The tests compare the package with the official 2026 Umm al-Qura tables for nine cities, stored in `tests/fixtures/official`. CI runs them on PHP 8.1 to 8.4, without Laravel and with Laravel 12 and 13.

## License

MIT
