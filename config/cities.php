<?php

/*
|--------------------------------------------------------------------------
| مراكز المدن — Umm al-Qura Prayer Times
|--------------------------------------------------------------------------
|
| مستويان: الدولة ← المدن.
| الإحداثيات هي نفسها التي يعتمدها تقويم أم القرى الرسمي (ummulqura.org.sa)،
| فتطابق أوقات الحزمة الجداول الرسمية لكل مدينة.
| اسم الدولة وتوقيتها معرّفان في Country enum.
|
| مفتاح الدولة + مفتاح المدينة يجب أن يطابقا قيمة حالة في City enum:
|     'SA' => ['riyadh' => [...]]   ⇐   City::SA_Riyadh = 'sa.riyadh'
|
| لتعديل مركز مدينة دون لمس هذا الملف، أنشئ ملفًا خاصًا بك يحتوي
| ما تريد تعديله فقط، ثم حمّله:
|
|     CityConfig::load(__DIR__ . '/config/prayer-cities.php');
|
|     // مثال على محتواه:
|     return ['SA' => ['riyadh' => ['latitude' => 24.6877, 'longitude' => 46.7219]]];
|
| لإضافة مدينة: أضف حالة في City enum ثم أضف بياناتها هنا.
| لإضافة دولة: أضف حالة في Country enum أيضًا.
|
*/

return [
    'SA' => [
        // ── منطقة مكة المكرمة
        'makkah'         => ['name_ar' => 'مكة المكرمة',     'name_en' => 'Makkah',          'latitude' => 21.426666, 'longitude' => 39.831666],
        'jeddah'         => ['name_ar' => 'جدة',             'name_en' => 'Jeddah',          'latitude' => 21.5, 'longitude' => 39.17],
        'taif'           => ['name_ar' => 'الطائف',          'name_en' => 'Taif',            'latitude' => 21.25, 'longitude' => 40.4],
        'rabigh'         => ['name_ar' => 'رابغ',            'name_en' => 'Rabigh',          'latitude' => 22.79, 'longitude' => 39.03],
        'qunfudhah'      => ['name_ar' => 'القنفذة',         'name_en' => 'Al Qunfudhah',    'latitude' => 19.13, 'longitude' => 41.08],

        // ── منطقة المدينة المنورة
        'madinah'        => ['name_ar' => 'المدينة المنورة', 'name_en' => 'Madinah',         'latitude' => 24.54, 'longitude' => 39.63],
        'yanbu'          => ['name_ar' => 'ينبع',            'name_en' => 'Yanbu',           'latitude' => 24.09, 'longitude' => 38.07],
        'alula'          => ['name_ar' => 'العلا',           'name_en' => 'AlUla',           'latitude' => 26.55, 'longitude' => 37.96],

        // ── منطقة الرياض
        'riyadh'         => ['name_ar' => 'الرياض',          'name_en' => 'Riyadh',          'latitude' => 24.67, 'longitude' => 46.69],
        'kharj'          => ['name_ar' => 'الخرج',           'name_en' => 'Al Kharj',        'latitude' => 24.15, 'longitude' => 47.32],
        'majmaah'        => ['name_ar' => 'المجمعة',         'name_en' => 'Al Majmaah',      'latitude' => 25.9, 'longitude' => 45.34],
        'zulfi'          => ['name_ar' => 'الزلفي',          'name_en' => 'Az Zulfi',        'latitude' => 26.3, 'longitude' => 44.82],
        'dawadmi'        => ['name_ar' => 'الدوادمي',        'name_en' => 'Ad Dawadmi',      'latitude' => 24.5, 'longitude' => 44.38],
        'wadi-aldawasir' => ['name_ar' => 'وادي الدواسر',    'name_en' => 'Wadi Al Dawasir', 'latitude' => 20.4722, 'longitude' => 44.783],

        // ── المنطقة الشرقية
        'dammam'         => ['name_ar' => 'الدمام',          'name_en' => 'Dammam',          'latitude' => 26.44, 'longitude' => 50.1],
        'khobar'         => ['name_ar' => 'الخبر',           'name_en' => 'Al Khobar',       'latitude' => 26.26, 'longitude' => 50.21],
        'dhahran'        => ['name_ar' => 'الظهران',         'name_en' => 'Dhahran',         'latitude' => 26.239291, 'longitude' => 50.040259],
        'qatif'          => ['name_ar' => 'القطيف',          'name_en' => 'Qatif',           'latitude' => 26.56, 'longitude' => 50.0],
        'jubail'         => ['name_ar' => 'الجبيل',          'name_en' => 'Jubail',          'latitude' => 27.0, 'longitude' => 49.66],
        'hofuf'          => ['name_ar' => 'الهفوف',          'name_en' => 'Al Hofuf',        'latitude' => 25.408, 'longitude' => 49.6132],
        'hafar-albatin'  => ['name_ar' => 'حفر الباطن',      'name_en' => 'Hafar Al-Batin',  'latitude' => 28.4, 'longitude' => 45.97],
        'khafji'         => ['name_ar' => 'الخفجي',          'name_en' => 'Khafji',          'latitude' => 28.42, 'longitude' => 48.49],

        // ── منطقة القصيم
        'buraydah'       => ['name_ar' => 'بريدة',           'name_en' => 'Buraydah',        'latitude' => 26.35, 'longitude' => 43.96],
        'unaizah'        => ['name_ar' => 'عنيزة',           'name_en' => 'Unaizah',         'latitude' => 26.09, 'longitude' => 43.97],
        'alrass'         => ['name_ar' => 'الرس',            'name_en' => 'Ar Rass',         'latitude' => 25.86, 'longitude' => 43.48],

        // ── منطقة حائل
        'hail'           => ['name_ar' => 'حائل',            'name_en' => 'Hail',            'latitude' => 27.52, 'longitude' => 41.7],

        // ── منطقة تبوك
        'tabuk'          => ['name_ar' => 'تبوك',            'name_en' => 'Tabuk',           'latitude' => 28.4, 'longitude' => 36.58],
        'alwajh'         => ['name_ar' => 'الوجه',           'name_en' => 'Al Wajh',         'latitude' => 26.23, 'longitude' => 36.47],

        // ── منطقة عسير
        'abha'           => ['name_ar' => 'أبها',            'name_en' => 'Abha',            'latitude' => 18.22, 'longitude' => 42.51],
        'khamis-mushait' => ['name_ar' => 'خميس مشيط',       'name_en' => 'Khamis Mushait',  'latitude' => 18.31, 'longitude' => 42.73],
        'bisha'          => ['name_ar' => 'بيشة',            'name_en' => 'Bisha',           'latitude' => 20.0, 'longitude' => 42.6],

        // ── منطقة الباحة
        'albaha'         => ['name_ar' => 'الباحة',          'name_en' => 'Al Baha',         'latitude' => 20.01, 'longitude' => 41.47],

        // ── منطقة جازان
        'jazan'          => ['name_ar' => 'جازان',           'name_en' => 'Jazan',           'latitude' => 16.89, 'longitude' => 42.54],
        'sabya'          => ['name_ar' => 'صبيا',            'name_en' => 'Sabya',           'latitude' => 17.15, 'longitude' => 42.62],

        // ── منطقة نجران
        'najran'         => ['name_ar' => 'نجران',           'name_en' => 'Najran',          'latitude' => 17.52, 'longitude' => 44.2],
        'sharurah'       => ['name_ar' => 'شرورة',           'name_en' => 'Sharurah',        'latitude' => 17.49, 'longitude' => 47.11],

        // ── منطقة الحدود الشمالية
        'arar'           => ['name_ar' => 'عرعر',            'name_en' => 'Arar',            'latitude' => 30.99, 'longitude' => 41.02],
        'turaif'         => ['name_ar' => 'طريف',            'name_en' => 'Turaif',          'latitude' => 31.68, 'longitude' => 38.66],

        // ── منطقة الجوف
        'sakaka'         => ['name_ar' => 'سكاكا',           'name_en' => 'Sakaka',          'latitude' => 29.97, 'longitude' => 40.2],
        'qurayyat'       => ['name_ar' => 'القريات',         'name_en' => 'Al Qurayyat',     'latitude' => 31.33, 'longitude' => 37.34],
    ],
];
