=== Barakah - Ramadan Prayer Times ===
Contributors: barakahteam
Tags: ramadan, prayer times, islamic, salah, iftar, sehri, muslim
Requires at least: 5.5
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A Ramadan prayer times plugin with shortcode widget, greeting popup, and sticky prayer bar.

== Description ==

Barakah provides a complete Ramadan experience for WordPress sites:

* Prayer times widget via shortcode
* New widget variants via shortcode: prayer_times, ramadan, hadith, dua, date
* Live countdown to next prayer
* Sehri and Iftar cards
* Ramadan month modal (Hijri calendar style)
* Daily dua/zikr rotation (with Bangla support)
* Greeting popup with page targeting
* Sticky prayer bar with page targeting
* Dark/light sticky bar themes
* Hijri date and caution-minute adjustments
* Onboarding wizard for first setup
* Server-side caching with configurable cache duration (default: 2 hours)

Powered by the free Aladhan API (no API key required).

== Installation ==

1. Upload the `barakah` folder to `/wp-content/plugins/`
2. Activate the plugin in **Plugins**
3. Open **Barakah** settings in wp-admin
4. Add shortcode on any page/post:

`[barakah]`

== Frequently Asked Questions ==

= How do I show the widget? =
Use `[barakah]` on any page or post.

= Can I set city/country per page? =
Yes:
`[barakah city="London" country="UK"]`

= Can I choose light mode for one shortcode? =
Yes:
`[barakah mode="light"]`

= Can I use different widget types? =
Yes. Use the `widget` attribute:
`[barakah widget="prayer_times"]`
`[barakah widget="ramadan"]`
`[barakah widget="hadith"]`
`[barakah widget="dua"]`
`[barakah widget="date"]`

= How do popup/sticky page targeting work? =
In plugin settings, choose **All pages** or **Specific pages only**, then select pages.

= How do I use Greeting Popup? =
Go to **Barakah -> Greeting Popup**, enable it, set title/message, choose page scope, then save.
To quickly test popup display on frontend, open:
`https://your-site-url/?barakah_test_popup=1`

= How do I use Sticky Prayer Bar? =
Go to **Barakah -> Sticky Bar**, enable it, choose header/footer position, theme, greeting text, and page scope.
The sticky bar shows Sehri and Iftar from your configured location.
If you choose **Specific pages only**, it appears only on selected pages.

= Does it need an API key? =
No. It uses Aladhan public endpoints.

= Why are times not updating instantly? =
Times are cached using transients. Reduce cache hours in settings if needed.

== Shortcode ==

Basic:
`[barakah]`

All supported attributes:
* `city` (string)
* `country` (string)
* `method` (number/string; Aladhan method id)
* `mode` (`dark` or `light`)
* `widget` (`full`, `prayer_times`, `ramadan`, `hadith`, `dua`, `date`)
* `columns` (`1` single-column, `2` two-column; full widget only)

Available shortcode variants:

`[barakah]`
`[barakah widget="full"]`
`[barakah widget="prayer_times"]`
`[barakah widget="ramadan"]`
`[barakah widget="hadith"]`
`[barakah widget="dua"]`
`[barakah widget="date"]`

Examples with combinations:

`[barakah city="Istanbul" country="Turkey"]`
`[barakah city="Istanbul" country="Turkey" method="13"]`
`[barakah mode="dark"]`
`[barakah mode="light"]`
`[barakah widget="prayer_times" mode="light"]`
`[barakah widget="ramadan" city="London" country="UK" method="2" mode="dark"]`
`[barakah widget="hadith" mode="light"]`
`[barakah widget="dua" mode="dark"]`
`[barakah widget="date" city="Dhaka" country="Bangladesh"]`
`[barakah widget="full" columns="1"]`
`[barakah widget="full" columns="2"]`

== Greeting Popup ==

Setup steps:
1. Open **Barakah -> Greeting Popup**
2. Enable popup
3. Set title and message
4. Choose scope: all pages or specific pages
5. Save settings

Quick check link:
`https://your-site-url/?barakah_test_popup=1`

== Sticky Prayer Bar ==

Setup steps:
1. Open **Barakah -> Sticky Bar**
2. Enable sticky bar
3. Select position (`Header` or `Footer`)
4. Select theme (`Dark` or `Light`)
5. Set greeting text
6. Select scope (all pages or specific pages)
7. Save settings

== Changelog ==

= 1.0.1 =
* Added 5 widget variants via shortcode `widget` attribute:
* `prayer_times` (full daily prayer list)
* `ramadan` (Sehri/Iftar focused)
* `hadith` (random hadith from API with local fallback)
* `dua` (random daily dua)
* `date` (Islamic date + next prayer)
* Added compact widget styles for dark/light modes

= 1.0.0 =
* Added tabbed settings UI and onboarding wizard improvements
* Added greeting popup page targeting
* Added sticky bar page targeting
* Added sticky delayed-open and enhanced collapse/expand transitions
* Set sticky theme default to dark for new installs
* Changed default cache duration to 2 hours
* Hardened frontend escaping for API-derived values
* Prevented duplicated dua rotation intervals
* Updated documentation

== Upgrade Notice ==

= 1.0.1 =
Adds multiple widget variants and compact Ramadan cards via shortcode.
