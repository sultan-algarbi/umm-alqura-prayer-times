<?php

declare(strict_types=1);

namespace Ihsan\UmmAlQuraPrayerTimes;

/**
 * Supported cities, named Country + City.
 *
 * Case:  {ISO country code}_{City}   e.g. City::SA_Riyadh
 * Value: {country}.{city}            e.g. 'sa.riyadh'  — stable, safe to store in a database or URL.
 *
 * City names and centre coordinates live in config/cities.php (see CityConfig);
 * the country name and timezone live in the Country enum.
 * From a request/form value use City::from('sa.riyadh') (throws) or City::tryFrom() (null).
 */
enum City: string
{
    // ── السعودية — منطقة مكة المكرمة
    case SA_Makkah = 'sa.makkah';
    case SA_Jeddah = 'sa.jeddah';
    case SA_Taif = 'sa.taif';
    case SA_Rabigh = 'sa.rabigh';
    case SA_Qunfudhah = 'sa.qunfudhah';

    // ── السعودية — منطقة المدينة المنورة
    case SA_Madinah = 'sa.madinah';
    case SA_Yanbu = 'sa.yanbu';
    case SA_AlUla = 'sa.alula';

    // ── السعودية — منطقة الرياض
    case SA_Riyadh = 'sa.riyadh';
    case SA_Kharj = 'sa.kharj';
    case SA_Majmaah = 'sa.majmaah';
    case SA_Zulfi = 'sa.zulfi';
    case SA_Dawadmi = 'sa.dawadmi';
    case SA_WadiAlDawasir = 'sa.wadi-aldawasir';

    // ── السعودية — المنطقة الشرقية
    case SA_Dammam = 'sa.dammam';
    case SA_Khobar = 'sa.khobar';
    case SA_Dhahran = 'sa.dhahran';
    case SA_Qatif = 'sa.qatif';
    case SA_Jubail = 'sa.jubail';
    case SA_Hofuf = 'sa.hofuf';
    case SA_HafarAlBatin = 'sa.hafar-albatin';
    case SA_Khafji = 'sa.khafji';

    // ── السعودية — منطقة القصيم
    case SA_Buraydah = 'sa.buraydah';
    case SA_Unaizah = 'sa.unaizah';
    case SA_AlRass = 'sa.alrass';

    // ── السعودية — منطقة حائل
    case SA_Hail = 'sa.hail';

    // ── السعودية — منطقة تبوك
    case SA_Tabuk = 'sa.tabuk';
    case SA_AlWajh = 'sa.alwajh';

    // ── السعودية — منطقة عسير
    case SA_Abha = 'sa.abha';
    case SA_KhamisMushait = 'sa.khamis-mushait';
    case SA_Bisha = 'sa.bisha';

    // ── السعودية — منطقة الباحة
    case SA_AlBaha = 'sa.albaha';

    // ── السعودية — منطقة جازان
    case SA_Jazan = 'sa.jazan';
    case SA_Sabya = 'sa.sabya';

    // ── السعودية — منطقة نجران
    case SA_Najran = 'sa.najran';
    case SA_Sharurah = 'sa.sharurah';

    // ── السعودية — منطقة الحدود الشمالية
    case SA_Arar = 'sa.arar';
    case SA_Turaif = 'sa.turaif';

    // ── السعودية — منطقة الجوف
    case SA_Sakaka = 'sa.sakaka';
    case SA_Qurayyat = 'sa.qurayyat';

    public function country(): Country
    {
        return Country::from(strtoupper(strstr($this->value, '.', true)));
    }

    /** City key inside its country, e.g. 'riyadh'. */
    public function key(): string
    {
        return substr(strstr($this->value, '.'), 1);
    }

    public function nameAr(): string
    {
        return CityConfig::city($this)['name_ar'];
    }

    public function nameEn(): string
    {
        return CityConfig::city($this)['name_en'];
    }

    /** e.g. "السعودية - الرياض" */
    public function fullNameAr(string $separator = ' - '): string
    {
        return $this->country()->nameAr() . $separator . $this->nameAr();
    }

    /** e.g. "Saudi Arabia - Riyadh" */
    public function fullNameEn(string $separator = ' - '): string
    {
        return $this->country()->nameEn() . $separator . $this->nameEn();
    }

    public function latitude(): float
    {
        return CityConfig::city($this)['latitude'];
    }

    public function longitude(): float
    {
        return CityConfig::city($this)['longitude'];
    }

    public function timezone(): string
    {
        return $this->country()->timezone();
    }

    /**
     * All cities of one country.
     *
     * @return list<self>
     */
    public static function ofCountry(Country $country): array
    {
        return array_values(array_filter(self::cases(), fn (self $c) => $c->country() === $country));
    }

    /**
     * For dropdowns: ['sa.riyadh' => 'السعودية - الرياض', ...] in declaration order.
     *
     * @param bool $withCountry false gives ['sa.riyadh' => 'الرياض', ...]
     *
     * @return array<string, string>
     */
    public static function options(string $locale = 'ar', bool $withCountry = true, ?Country $country = null): array
    {
        $cities = $country === null ? self::cases() : self::ofCountry($country);
        $out = [];
        foreach ($cities as $city) {
            $out[$city->value] = match ([$locale === 'ar', $withCountry]) {
                [true, true] => $city->fullNameAr(),
                [true, false] => $city->nameAr(),
                [false, true] => $city->fullNameEn(),
                [false, false] => $city->nameEn(),
            };
        }

        return $out;
    }
}
