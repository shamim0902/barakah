=== Barakah - Ramadan Prayer Times ===
Contributors: barakahteam
Tags: ramadan, prayer times, islamic, salah, iftar, sehri, muslim
Requires at least: 5.5
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A Ramadan prayer times plugin with shortcode widget, greeting popup, and sticky prayer bar.

== Description ==

Barakah provides a complete Ramadan experience for WordPress sites:

* Prayer times widget via shortcode
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

= How do popup/sticky page targeting work? =
In plugin settings, choose **All pages** or **Specific pages only**, then select pages.

= Does it need an API key? =
No. It uses Aladhan public endpoints.

= Why are times not updating instantly? =
Times are cached using transients. Reduce cache hours in settings if needed.

== Shortcode ==

Basic:
`[barakah]`

City/Country override:
`[barakah city="Istanbul" country="Turkey"]`

Method override:
`[barakah city="Istanbul" country="Turkey" method="13"]`

Mode override:
`[barakah mode="dark"]`
`[barakah mode="light"]`

== Changelog ==

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

= 1.0.0 =
Initial public release.
