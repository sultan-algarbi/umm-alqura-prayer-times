<?php

/*
|--------------------------------------------------------------------------
| مراكز المدن — Umm al-Qura Prayer Times
|--------------------------------------------------------------------------
|
| مستويان: الدولة ← المدن.
| إحداثيات كل مدينة هي مركزها (أو جامعها الرئيسي) الذي تُحسب منه أوقات الأذان.
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
        'makkah'         => ['name_ar' => 'مكة المكرمة',     'name_en' => 'Makkah',          'latitude' => 21.4225, 'longitude' => 39.8262],
        'jeddah'         => ['name_ar' => 'جدة',             'name_en' => 'Jeddah',          'latitude' => 21.4858, 'longitude' => 39.1925],
        'taif'           => ['name_ar' => 'الطائف',          'name_en' => 'Taif',            'latitude' => 21.2703, 'longitude' => 40.4158],
        'rabigh'         => ['name_ar' => 'رابغ',            'name_en' => 'Rabigh',          'latitude' => 22.7986, 'longitude' => 39.0349],
        'qunfudhah'      => ['name_ar' => 'القنفذة',         'name_en' => 'Al Qunfudhah',    'latitude' => 19.1264, 'longitude' => 41.0789],

        // ── منطقة المدينة المنورة
        'madinah'        => ['name_ar' => 'المدينة المنورة', 'name_en' => 'Madinah',         'latitude' => 24.4672, 'longitude' => 39.6111],
        'yanbu'          => ['name_ar' => 'ينبع',            'name_en' => 'Yanbu',           'latitude' => 24.0890, 'longitude' => 38.0618],
        'alula'          => ['name_ar' => 'العلا',           'name_en' => 'AlUla',           'latitude' => 26.6085, 'longitude' => 37.9232],

        // ── منطقة الرياض
        'riyadh'         => ['name_ar' => 'الرياض',          'name_en' => 'Riyadh',          'latitude' => 24.7136, 'longitude' => 46.6753],
        'kharj'          => ['name_ar' => 'الخرج',           'name_en' => 'Al Kharj',        'latitude' => 24.1556, 'longitude' => 47.3120],
        'majmaah'        => ['name_ar' => 'المجمعة',         'name_en' => 'Al Majmaah',      'latitude' => 25.9046, 'longitude' => 45.3449],
        'zulfi'          => ['name_ar' => 'الزلفي',          'name_en' => 'Az Zulfi',        'latitude' => 26.2992, 'longitude' => 44.8155],
        'dawadmi'        => ['name_ar' => 'الدوادمي',        'name_en' => 'Ad Dawadmi',      'latitude' => 24.5077, 'longitude' => 44.3924],
        'wadi-aldawasir' => ['name_ar' => 'وادي الدواسر',    'name_en' => 'Wadi Al Dawasir', 'latitude' => 20.4607, 'longitude' => 44.7845],

        // ── المنطقة الشرقية
        'dammam'         => ['name_ar' => 'الدمام',          'name_en' => 'Dammam',          'latitude' => 26.4207, 'longitude' => 50.0888],
        'khobar'         => ['name_ar' => 'الخبر',           'name_en' => 'Al Khobar',       'latitude' => 26.2172, 'longitude' => 50.1971],
        'dhahran'        => ['name_ar' => 'الظهران',         'name_en' => 'Dhahran',         'latitude' => 26.2361, 'longitude' => 50.0393],
        'qatif'          => ['name_ar' => 'القطيف',          'name_en' => 'Qatif',           'latitude' => 26.5560, 'longitude' => 50.0080],
        'jubail'         => ['name_ar' => 'الجبيل',          'name_en' => 'Jubail',          'latitude' => 27.0046, 'longitude' => 49.6460],
        'hofuf'          => ['name_ar' => 'الأحساء',         'name_en' => 'Al Ahsa',         'latitude' => 25.3833, 'longitude' => 49.5869],
        'hafar-albatin'  => ['name_ar' => 'حفر الباطن',      'name_en' => 'Hafar Al-Batin',  'latitude' => 28.4328, 'longitude' => 45.9708],
        'khafji'         => ['name_ar' => 'الخفجي',          'name_en' => 'Khafji',          'latitude' => 28.4390, 'longitude' => 48.4913],

        // ── منطقة القصيم
        'buraydah'       => ['name_ar' => 'بريدة',           'name_en' => 'Buraydah',        'latitude' => 26.3260, 'longitude' => 43.9750],
        'unaizah'        => ['name_ar' => 'عنيزة',           'name_en' => 'Unaizah',         'latitude' => 26.0843, 'longitude' => 43.9935],
        'alrass'         => ['name_ar' => 'الرس',            'name_en' => 'Ar Rass',         'latitude' => 25.8694, 'longitude' => 43.4973],

        // ── منطقة حائل
        'hail'           => ['name_ar' => 'حائل',            'name_en' => 'Hail',            'latitude' => 27.5114, 'longitude' => 41.7208],

        // ── منطقة تبوك
        'tabuk'          => ['name_ar' => 'تبوك',            'name_en' => 'Tabuk',           'latitude' => 28.3835, 'longitude' => 36.5662],
        'alwajh'         => ['name_ar' => 'الوجه',           'name_en' => 'Al Wajh',         'latitude' => 26.2455, 'longitude' => 36.4525],

        // ── منطقة عسير
        'abha'           => ['name_ar' => 'أبها',            'name_en' => 'Abha',            'latitude' => 18.2164, 'longitude' => 42.5053],
        'khamis-mushait' => ['name_ar' => 'خميس مشيط',       'name_en' => 'Khamis Mushait',  'latitude' => 18.3000, 'longitude' => 42.7333],
        'bisha'          => ['name_ar' => 'بيشة',            'name_en' => 'Bisha',           'latitude' => 19.9844, 'longitude' => 42.6052],

        // ── منطقة الباحة
        'albaha'         => ['name_ar' => 'الباحة',          'name_en' => 'Al Baha',         'latitude' => 20.0129, 'longitude' => 41.4677],

        // ── منطقة جازان
        'jazan'          => ['name_ar' => 'جازان',           'name_en' => 'Jazan',           'latitude' => 16.8892, 'longitude' => 42.5511],
        'sabya'          => ['name_ar' => 'صبيا',            'name_en' => 'Sabya',           'latitude' => 17.1495, 'longitude' => 42.6254],

        // ── منطقة نجران
        'najran'         => ['name_ar' => 'نجران',           'name_en' => 'Najran',          'latitude' => 17.5656, 'longitude' => 44.2289],
        'sharurah'       => ['name_ar' => 'شرورة',           'name_en' => 'Sharurah',        'latitude' => 17.4667, 'longitude' => 47.1167],

        // ── منطقة الحدود الشمالية
        'arar'           => ['name_ar' => 'عرعر',            'name_en' => 'Arar',            'latitude' => 30.9753, 'longitude' => 41.0381],
        'turaif'         => ['name_ar' => 'طريف',            'name_en' => 'Turaif',          'latitude' => 31.6725, 'longitude' => 38.6637],

        // ── منطقة الجوف
        'sakaka'         => ['name_ar' => 'سكاكا',           'name_en' => 'Sakaka',          'latitude' => 29.9697, 'longitude' => 40.2064],
        'qurayyat'       => ['name_ar' => 'القريات',         'name_en' => 'Al Qurayyat',     'latitude' => 31.3317, 'longitude' => 37.3428],
    ],
];
