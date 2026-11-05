<?php

declare(strict_types=1);

namespace Ihsan\UmmAlQuraPrayerTimes;

use InvalidArgumentException;

/**
 * Loads and validates config/cities.php — two levels, country → cities:
 *
 *   'SA' => [
 *       'riyadh' => ['name_ar' => 'الرياض', 'name_en' => 'Riyadh', 'latitude' => 24.7136, 'longitude' => 46.6753],
 *   ]
 *
 * The package defaults are always loaded first; load()/set() merge on top of them,
 * so a custom file only needs the cities/fields you want to change.
 * Country keys must be Country cases, country+city must be a City case,
 * and every City case must be configured.
 */
final class CityConfig
{
    private const FIELDS = ['name_ar', 'name_en', 'latitude', 'longitude'];

    /** @var array<string, array<string, array{name_ar: string, name_en: string, latitude: float, longitude: float}>>|null */
    private static ?array $config = null;

    /** Merge a custom config file (PHP file returning an array) over the defaults. */
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            throw new InvalidArgumentException(sprintf('Config file "%s" not found.', $path));
        }
        $data = require $path;
        if (!is_array($data)) {
            throw new InvalidArgumentException(sprintf('Config file "%s" must return an array.', $path));
        }
        self::set($data);
    }

    /** Merge a custom config array over the defaults. */
    public static function set(array $config): void
    {
        self::$config = self::build(self::build([], self::defaults()), $config);
    }

    /** Back to the package defaults. */
    public static function reset(): void
    {
        self::$config = null;
    }

    /** @return array{name_ar: string, name_en: string, latitude: float, longitude: float} */
    public static function city(City $city): array
    {
        return self::config()[$city->country()->value][$city->key()];
    }

    // ---------------------------------------------------------------------

    private static function config(): array
    {
        return self::$config ??= self::build([], self::defaults());
    }

    private static function defaults(): array
    {
        return require dirname(__DIR__) . '/config/cities.php';
    }

    private static function build(array $base, array $override): array
    {
        $result = $base;

        foreach ($override as $code => $cities) {
            $country = Country::tryFrom((string) $code)
                ?? throw new InvalidArgumentException(sprintf('"%s" is not a Country enum value (expected e.g. "SA").', $code));
            if (!is_array($cities)) {
                throw new InvalidArgumentException(sprintf('Config for country "%s" must be an array of cities.', $code));
            }

            foreach ($cities as $key => $entry) {
                $value = strtolower($country->value) . '.' . $key;
                if (City::tryFrom($value) === null) {
                    throw new InvalidArgumentException(sprintf('"%s" is not a City enum value. Add the case to City first.', $value));
                }
                if (!is_array($entry)) {
                    throw new InvalidArgumentException(sprintf('Config for "%s" must be an array.', $value));
                }
                $result[$country->value][$key] = self::validate($value, array_merge($result[$country->value][$key] ?? [], $entry));
            }
        }

        foreach (City::cases() as $city) {
            if (!isset($result[$city->country()->value][$city->key()])) {
                throw new InvalidArgumentException(sprintf('City "%s" is missing from the cities config.', $city->value));
            }
        }

        return $result;
    }

    private static function validate(string $value, array $e): array
    {
        $unknown = array_diff(array_keys($e), self::FIELDS);
        if ($unknown !== []) {
            throw new InvalidArgumentException(sprintf('Unknown keys in "%s": %s', $value, implode(', ', $unknown)));
        }
        foreach (['name_ar', 'name_en'] as $k) {
            if (!isset($e[$k]) || !is_string($e[$k]) || trim($e[$k]) === '') {
                throw new InvalidArgumentException(sprintf('"%s": "%s" must be a non-empty string.', $value, $k));
            }
        }
        foreach (['latitude' => 90, 'longitude' => 180] as $k => $limit) {
            if (!isset($e[$k]) || !is_numeric($e[$k]) || abs((float) $e[$k]) > $limit) {
                throw new InvalidArgumentException(sprintf('"%s": "%s" must be a number between -%d and %d.', $value, $k, $limit, $limit));
            }
        }

        return [
            'name_ar' => $e['name_ar'],
            'name_en' => $e['name_en'],
            'latitude' => (float) $e['latitude'],
            'longitude' => (float) $e['longitude'],
        ];
    }
}
