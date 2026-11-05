<?php

declare(strict_types=1);

namespace Ihsan\UmmAlQuraPrayerTimes;

/**
 * Supported countries (ISO 3166-1 alpha-2). Each case is a top-level key in config/cities.php.
 */
enum Country: string
{
    case SA = 'SA';

    public function nameAr(): string
    {
        return match ($this) {
            self::SA => 'السعودية',
        };
    }

    public function nameEn(): string
    {
        return match ($this) {
            self::SA => 'Saudi Arabia',
        };
    }

    /** Timezone the prayer times are returned in. */
    public function timezone(): string
    {
        return match ($this) {
            self::SA => 'Asia/Riyadh',
        };
    }

    /** @return list<City> */
    public function cities(): array
    {
        return City::ofCountry($this);
    }
}
