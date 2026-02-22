=== Barakah – Ramadan Prayer Times ===
Contributors: barakahteam
Tags: ramadan, prayer times, islamic, salah, iftar, sehri, muslim
Requires at least: 5.5
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0+

A beautiful dark-themed Ramadan prayer times widget powered by the Aladhan API.
Use [barakah] shortcode on any page or post.

== Description ==

Barakah displays a fully responsive, animated prayer times widget featuring:

* Live countdown to the next prayer (Fajr, Dhuhr, Asr, Maghrib, Isha)
* Prominent Sehri & Iftar time cards
* Full daily prayer timetable with active prayer highlighted
* Animated twinkling star background
* Rotating Dua / Dhikr section (8 authentic duas)
* Dua at Iftar and Niyyah for Sehri
* Hijri date display
* Powered by the free Aladhan API — no API key required

== Installation ==

1. Upload the `barakah` folder to `/wp-content/plugins/`
2. Activate the plugin via **Plugins → Installed Plugins**
3. Go to **Barakah** in the admin sidebar and configure your city, country, and calculation method
4. Add [barakah] to any page, post, or widget

== Shortcode Usage ==

Basic (uses admin settings):
  [barakah]

Override city & country inline:
  [barakah city="London" country="UK"]

Override everything:
  [barakah city="Istanbul" country="Turkey" method="13"]

== Calculation Methods ==

1  – Muslim World League
2  – Islamic Society of North America (ISNA)
3  – Egyptian General Authority of Survey
4  – Umm Al-Qura University, Makkah
5  – University of Islamic Sciences, Karachi
7  – Institute of Geophysics, Tehran
8  – Gulf Region
9  – Kuwait
10 – Qatar
11 – Singapore
12 – Union Organization Islamic de France
13 – Diyanet İşleri Başkanlığı, Turkey
14 – Spiritual Administration of Muslims of Russia
15 – Moonsighting Committee Worldwide

== Changelog ==

= 1.0.0 =
* Initial release

== Notes ==

Prayer times are fetched client-side from https://api.aladhan.com.
The site must be able to reach this API. No API key is required.
Times are displayed in the local time of the configured city.
