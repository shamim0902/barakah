# Barakah – Ramadan Prayer Times

A beautiful, animated Ramadan prayer times widget for WordPress. Powered by the free [Aladhan API](https://aladhan.com/prayer-times-api) — no API key required.

## Features

- Live countdown to the next prayer (Fajr, Dhuhr, Asr, Maghrib, Isha)
- Prominent Sehri & Iftar time cards with countdown badges
- Full daily prayer timetable with active/next prayer highlighted
- Full Ramadan month view (Hijri calendar) via modal
- Rotating Daily Dua & Dhikr section (English + Bangla duas)
- Dua at Iftar and Niyyah for Sehri cards
- Hijri date display with Gregorian date
- Dark & Light mode toggle (persisted in localStorage)
- Two-column layout option for large screens
- Server-side caching via WordPress Transients (configurable duration)
- Animated twinkling star background (dark mode)
- Fully responsive — works on mobile, tablet, and desktop

## Requirements

- WordPress 5.5+
- PHP 7.4+

## Installation

1. Upload the `barakah` folder to `/wp-content/plugins/`
2. Activate the plugin via **Plugins > Installed Plugins**
3. Go to **Barakah** in the admin sidebar to configure settings
4. Add the shortcode to any page, post, or widget

## Shortcode Usage

```
[barakah]
```

Uses the global settings from the admin page (city, country, method).

### Override per-instance

```
[barakah city="London" country="UK"]
```

```
[barakah city="Istanbul" country="Turkey" method="13"]
```

### Display mode

```
[barakah mode="light"]
[barakah mode="dark"]
```

Default is `dark`. Users can also toggle between modes using the button in the widget header.

## Admin Settings

Navigate to **Barakah** in the WordPress admin sidebar.

### Location Settings

| Setting   | Description                          | Default      |
|-----------|--------------------------------------|--------------|
| City      | City name for prayer times           | Dhaka        |
| Country   | Country name or code                 | Bangladesh   |
| Method    | Calculation method (see list below)  | 1            |

### Cache Settings

| Setting        | Description                              | Default |
|----------------|------------------------------------------|---------|
| Cache Duration | Hours to cache prayer times (1–168)      | 6       |

Prayer times are fetched server-side and cached using WordPress Transients. Cache is automatically cleared when settings are saved.

### Layout Settings

| Setting            | Description                                          | Default |
|--------------------|------------------------------------------------------|---------|
| Show 2 Column      | Side-by-side layout on screens >= 768px              | Off     |

When enabled, prayer times appear on the left and dua cards on the right. Mobile devices always use single column.

## Calculation Methods

| ID | Method                                           |
|----|--------------------------------------------------|
| 1  | Muslim World League                              |
| 2  | Islamic Society of North America (ISNA)          |
| 3  | Egyptian General Authority of Survey             |
| 4  | Umm Al-Qura University, Makkah                  |
| 5  | University of Islamic Sciences, Karachi          |
| 7  | Institute of Geophysics, University of Tehran    |
| 8  | Gulf Region                                      |
| 9  | Kuwait                                           |
| 10 | Qatar                                            |
| 11 | Majlis Ugama Islam Singapura (Singapore)         |
| 12 | Union Organization Islamic de France             |
| 13 | Diyanet Isleri Baskanligi, Turkey                |
| 14 | Spiritual Administration of Muslims of Russia    |
| 15 | Moonsighting Committee Worldwide                 |

### Regional recommendations

- **Bangladesh, India, Pakistan** — Method 1 (Muslim World League) or Method 5 (Karachi)
- **Saudi Arabia** — Method 4 (Umm Al-Qura)
- **North America** — Method 2 (ISNA)
- **Turkey** — Method 13
- **Egypt** — Method 3

## File Structure

```
barakah/
  barakah.php                  # Main plugin file (hooks, activation, assets)
  readme.txt                   # WordPress.org readme
  README.md                    # This file
  assets/
    css/barakah.css             # All widget styles (dark, light, modal, two-col)
    js/barakah.js               # Widget logic (render, countdown, dua rotation, month modal)
  data/
    bangla_duas.json            # 20 Bangla duas (Arabic, pronunciation, meaning)
  includes/
    admin.php                   # Admin settings page
    class-barakah-api.php       # Server-side API handler with transient caching
    shortcode.php               # [barakah] shortcode registration
```

## How It Works

1. On page load, the shortcode renders the widget container with `data-*` attributes
2. The PHP API class fetches today's prayer times from Aladhan (cached via Transients)
3. Data is passed to JavaScript via `wp_localize_script`
4. JavaScript renders the full widget — prayer times, countdown timer, dua rotation
5. If server-side fetch fails, JavaScript falls back to a client-side `fetch()` call

## Changelog

### 1.0.2
- Fixed: Correct Aladhan API endpoint (removed date from URL path)
- Added: Full Ramadan month view modal (`hijriCalendarByCity` endpoint)
- Added: Two-column layout setting for large screens
- Added: Server-side caching with configurable duration
- Added: Bangla duas (20 duas with Arabic, pronunciation, meaning)
- Added: Both Gregorian and Hijri dates in month view
- Added: Dark/Light mode toggle
- Added: Auto cache flush on version upgrade

### 1.0.1
- Added: Cache settings in admin
- Added: Server-side prayer times caching via Transients

### 1.0.0
- Initial release

## License

GPL-2.0+
