# Official Umm al-Qura timetables (2026)

Yearly prayer times published by the official Umm al-Qura Calendar
(https://www.ummulqura.org.sa, King Abdulaziz City for Science and Technology),
read from its yearly view on 5 November 2026 at the coordinates the site uses for each city.

One line per day:

    gregorian-date|hijri-date (Y-M-D)|fajr|sunrise|dhuhr|asr|maghrib|isha

Times are 24-hour, Asia/Riyadh (UTC+3).

Known artifact in the source data: once per Hijri month (12 dates in 2026, listed in
`PrayerTimesTest::REPEATED_DATES`) the official table repeats the previous day's row in
every city. The package computes those days normally, so the tests exclude them from
the exact-match rate.
