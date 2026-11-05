<div dir="rtl">

# أوقات الصلاة بتقويم أم القرى — PHP و Laravel

[![Tests](https://github.com/sultan-algarbi/umm-alqura-prayer-times/actions/workflows/tests.yml/badge.svg)](https://github.com/sultan-algarbi/umm-alqura-prayer-times/actions/workflows/tests.yml)
[![Packagist](https://img.shields.io/packagist/v/ihsan/umm-alqura-prayer-times)](https://packagist.org/packages/ihsan/umm-alqura-prayer-times)

**[English](README.md)** | العربية

حزمة PHP تحسب أوقات الأذان بطريقة **أم القرى** الرسمية، وتعمل **دون إنترنت** بالكامل. تعمل في أي مشروع PHP، وفي **Laravel** بتكامل جاهز.
المدن معرّفة في `enum` إلزامي (الدولة + المدينة)، وإحداثيات مراكزها في ملف config قابل للتعديل.

**مُعايَرة على التقويم الرسمي:** تطابق الحزمة جداول [تقويم أم القرى الرسمي](https://www.ummulqura.org.sa) حتى الدقيقة في **97.9%** من الأوقات، ولا يتجاوز الفرق دقيقة واحدة في أي وقت آخر، مع تطابق **كل** التواريخ الهجرية (انظر [الدقة](#الدقة)).

## المحتويات

- [طريقة الحساب](#طريقة-الحساب)
- [المتطلبات والتثبيت](#المتطلبات-والتثبيت)
- [الاستخدام في PHP](#الاستخدام-في-php)
- [الاستخدام في Laravel](#الاستخدام-في-laravel)
- [ملف الإعدادات](#ملف-الإعدادات-configcitiesphp)
- [الدقة](#الدقة)

## طريقة الحساب

| الوقت | القاعدة |
|---|---|
| الفجر | الشمس تحت الأفق بزاوية 18.5° |
| الشروق | ظهور حافة الشمس العليا (مع الانكسار الجوي) |
| الظهر | زوال الشمس |
| العصر | ظل الشيء مثله + ظل الزوال (الجمهور) |
| المغرب | غروب الشمس |
| العشاء | بعد المغرب بـ 90 دقيقة، و **120 دقيقة في رمضان تلقائيًا** |

- موقع الشمس من معادلات Jean Meeus *Astronomical Algorithms*، ويُؤخذ لكل صلاة في اللحظة التي يعتمدها التقويم الرسمي من اليوم.
- **التقريب كالجداول الرسمية:** أوقات الصلوات تُقرَّب **للأعلى** إلى الدقيقة، فلا يُعلَن أذان قبل وقته، والشروق يُقرَّب **للأسفل**، فلا يتأخر عن آخر وقت الفجر.
- إحداثيات المدن هي نفسها التي يستخدمها التقويم الرسمي.
- رمضان يُحدَّد تلقائيًا من تقويم أم القرى الهجري.
- الأوقات بتوقيت دولة المدينة، ولا توجد إزاحات يدوية: الناتج هو وقت الأذان حسب مركز المدينة.

## المتطلبات والتثبيت

- PHP 8.1 أو أحدث، مع امتدادي `intl` و `mbstring`
- Laravel اختياري: الإصداران 12 و13 مدعومان ومختبران. أما 10 و11 فانتهى دعمهما، وأصبح Composer يمنع تثبيتهما بسبب ثغرات أمنية غير مُصلَحة.

```bash
composer require ihsan/umm-alqura-prayer-times
```

## الاستخدام في PHP

### المدن: الدولة + المدينة

كل مدينة حالة في `City` enum، اسمها `{كود الدولة}_{المدينة}` وقيمتها `{الدولة}.{المدينة}`:

```php
use Ihsan\UmmAlQuraPrayerTimes\City;
use Ihsan\UmmAlQuraPrayerTimes\Country;

City::SA_Riyadh->value;          // 'sa.riyadh'  ← ثابتة، آمنة للتخزين في قاعدة البيانات
City::SA_Riyadh->country();      // Country::SA
City::SA_Riyadh->nameAr();       // 'الرياض'
City::SA_Riyadh->fullNameAr();   // 'السعودية - الرياض'
City::SA_Riyadh->fullNameEn();   // 'Saudi Arabia - Riyadh'

// من قيمة قادمة من نموذج أو طلب HTTP
City::from($value);      // يرمي خطأ إن لم تكن مدينة معرّفة
City::tryFrom($value);   // يعيد null

// لقوائم الاختيار
City::options();                         // ['sa.riyadh' => 'السعودية - الرياض', ...]
City::options('ar', withCountry: false); // ['sa.riyadh' => 'الرياض', ...]
City::options('en', country: Country::SA);
Country::SA->cities();                   // كل مدن السعودية
```

### استخراج الأوقات حسب التاريخ

```php
use Ihsan\UmmAlQuraPrayerTimes\PrayerTimes;

// يوم ميلادي محدد (أو null لليوم الحالي بتوقيت المدينة)
$t = PrayerTimes::on(City::SA_Riyadh, '2026-11-05');
$t = prayer_times(City::SA_Riyadh, '2026-11-05');   // دالة مختصرة

$t->fajr->format('H:i');   // DateTimeImmutable بتوقيت المدينة
$t->toArray();
// ['fajr' => '04:44', 'sunrise' => '06:02', 'dhuhr' => '11:37',
//  'asr' => '14:48', 'maghrib' => '17:11', 'isha' => '18:41']
$t->hijri;       // ['year' => 1448, 'month' => 5, 'day' => 25]
$t->isRamadan;   // false

// يوم هجري
$t = PrayerTimes::onHijri(City::SA_Makkah, 1448, 9, 1);

// نطاق تواريخ (مصفوفة مفاتيحها Y-m-d)
PrayerTimes::between(City::SA_Jeddah, '2026-12-01', '2026-12-31');

// شهر ميلادي كامل
PrayerTimes::month(City::SA_Madinah, 2026, 12);

// شهر هجري كامل — مثلًا إمساكية رمضان
foreach (PrayerTimes::hijriMonth(City::SA_Riyadh, 1448, 9) as $date => $day) {
    echo "{$day->hijri['day']} رمضان ($date): " . implode(' ', $day->toArray()) . PHP_EOL;
}

// الأذان القادم (ينتقل لفجر الغد بعد العشاء)
['prayer' => $p, 'time' => $time] = PrayerTimes::nextAdhan(City::SA_Makkah);
echo $p->arabicName() . ' ' . $time->format('H:i');

// الصلاة الحالية والقادمة ضمن يوم
$t->currentPrayer();   // Prayer|null
$t->nextPrayer();      // Prayer|null
```

التاريخ النصي يجب أن يكون `Y-m-d` وتاريخًا صحيحًا، وإلا يُرمى `InvalidArgumentException`.
وإن مرّرت كائن تاريخ بمنطقة زمنية أخرى، يؤخذ اليوم كما هو في توقيت المدينة.

## الاستخدام في Laravel

يُكتشف الـ Service Provider تلقائيًا بعد التثبيت، فلا حاجة لأي تسجيل يدوي.

### نشر ملف الإعدادات (اختياري)

```bash
php artisan vendor:publish --tag=prayer-times-config
```

ينشئ هذا الملف `config/prayer-cities.php`. عدّل فيه ما تريد من مراكز المدن، فيُطبَّق تلقائيًا عند تشغيل التطبيق. ويعمل مع `php artisan config:cache`.

### التحقق من المدينة في الطلب

```php
use Illuminate\Validation\Rule;
use Ihsan\UmmAlQuraPrayerTimes\City;

$request->validate([
    'city' => ['required', Rule::enum(City::class)],
    'date' => ['nullable', 'date_format:Y-m-d'],
]);
```

### حفظ المدينة في Eloquent

```php
use Ihsan\UmmAlQuraPrayerTimes\City;

protected function casts(): array
{
    return ['city' => City::class];   // يُخزَّن 'sa.riyadh' ويُقرأ City::SA_Riyadh
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
        'city'  => $times->city->fullNameAr(),
        'hijri' => $times->hijri,
        'times' => $times->toArray(),
    ]);
}
```

### قائمة المدن في Blade

```blade
<select name="city">
    @foreach (\Ihsan\UmmAlQuraPrayerTimes\City::options() as $value => $label)
        <option value="{{ $value }}" @selected(old('city') === $value)>{{ $label }}</option>
    @endforeach
</select>
```

## ملف الإعدادات `config/cities.php`

مستويان فقط: **الدولة ← المدن**.

```php
return [
    'SA' => [
        'riyadh' => ['name_ar' => 'الرياض', 'name_en' => 'Riyadh', 'latitude' => 24.67, 'longitude' => 46.69],
        'jeddah' => ['name_ar' => 'جدة',    'name_en' => 'Jeddah', 'latitude' => 21.5,  'longitude' => 39.17],
        // ...
    ],
];
```

اسم الدولة وتوقيتها معرّفان في `Country` enum.

### تعديل مركز مدينة دون لمس ملف الحزمة

**في Laravel:** انشر الملف كما سبق وعدّله.

**في PHP:** أنشئ ملفًا يحتوي **ما تريد تعديله فقط**، ثم حمّله مرة واحدة عند بدء التطبيق:

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
// أو مباشرة من مصفوفة:
CityConfig::set(['SA' => ['riyadh' => ['latitude' => 24.6877, 'longitude' => 46.7219]]]);
```

يُدمج ملفك فوق الإعدادات الافتراضية، فالحقول والمدن التي لا تذكرها تبقى كما هي.
ويُرفض أي مفتاح غير معروف، أو مدينة غير موجودة في الـ enum، أو إحداثيات خارج النطاق.

### إضافة مدينة

1. أضف حالة في `src/City.php`، مثل: `case SA_Afif = 'sa.afif';`
2. أضف بياناتها في `config/cities.php` تحت `'SA'`:
   `'afif' => ['name_ar' => 'عفيف', 'name_en' => 'Afif', 'latitude' => 23.91, 'longitude' => 42.92],`

إن نسيت الخطوة الثانية، ترمي الحزمة خطأً واضحًا يذكر المدينة الناقصة.
ولإضافة دولة، أضف حالتها في `src/Country.php` (الاسم والتوقيت) ثم مفتاحها في الـ config.

### المدن المدمجة

مكة، جدة، الطائف، رابغ، القنفذة، المدينة، ينبع، العلا، الرياض، الخرج، المجمعة، الزلفي، الدوادمي، وادي الدواسر، الدمام، الخبر، الظهران، القطيف، الجبيل، الهفوف (الأحساء)، حفر الباطن، الخفجي، بريدة، عنيزة، الرس، حائل، تبوك، الوجه، أبها، خميس مشيط، بيشة، الباحة، جازان، صبيا، نجران، شرورة، عرعر، طريف، سكاكا، القريات.

## الدقة

قورنت الحزمة بالجداول الرسمية في تسع مدن موزعة على المملكة: الرياض، جدة، مكة، المدينة، الدمام، تبوك، جازان، عرعر، حائل. المجموع 3,285 يومًا و19,710 وقتًا:

| | النتيجة |
|---|---|
| تطابق حتى الدقيقة | **97.9%** (الفجر 98.2%، الشروق 96.4%، الظهر 98.5%، العصر 98.0%، المغرب والعشاء 98.1%) |
| أقصى فرق | دقيقة واحدة |
| التواريخ الهجرية | **3,285 من 3,285** |

الفروقات المتبقية تقع غالبًا في أوقات تبعد ثوانيَ قليلة عن حدّ الدقيقة، حيث تحسم فروقٌ حسابية دقيقة جدًا اتجاه التقريب.

**ملاحظة عن بيانات الموقع الرسمي:** مرة في كل شهر هجري تقريبًا (حول اليوم الثالث أو الرابع، 12 تاريخًا في 2026)، يكرّر الموقع الرسمي أوقات اليوم السابق كما هي في جميع المدن، وفي كل طرق العرض (السنوي والشهري والهجري). تحسب الحزمة هذه الأيام حسابًا فلكيًا عاديًا ولا تكرّر اليوم السابق، لذلك قد تختلف عن الموقع في تلك الأيام (وأحيانًا في اليوم التالي لها) بدقيقة أو دقيقتين. هذه التواريخ مستثناة من الأرقام أعلاه.

## الاختبارات

```bash
composer install && composer test
```

تقارن الاختبارات الحزمة بالجداول السنوية الرسمية لتقويم أم القرى لعام 2026 في تسع مدن، محفوظة في `tests/fixtures/official`. ويختبر CI الحزمة على PHP 8.1 إلى 8.4، دون Laravel ومع Laravel 12 و13.

## الترخيص

MIT

</div>
