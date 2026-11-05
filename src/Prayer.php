<?php

declare(strict_types=1);

namespace Ihsan\UmmAlQuraPrayerTimes;

enum Prayer: string
{
    case Fajr = 'fajr';
    case Sunrise = 'sunrise';
    case Dhuhr = 'dhuhr';
    case Asr = 'asr';
    case Maghrib = 'maghrib';
    case Isha = 'isha';

    public function arabicName(): string
    {
        return match ($this) {
            self::Fajr => 'الفجر',
            self::Sunrise => 'الشروق',
            self::Dhuhr => 'الظهر',
            self::Asr => 'العصر',
            self::Maghrib => 'المغرب',
            self::Isha => 'العشاء',
        };
    }

    /** The five prayers that have an adhan (sunrise excluded). */
    public function hasAdhan(): bool
    {
        return $this !== self::Sunrise;
    }
}
