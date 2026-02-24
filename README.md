# Barakah - Ramadan Prayer Times

Barakah is a WordPress plugin for Ramadan prayer times, Sehri/Iftar highlights, dua content, greeting popup, and a configurable sticky prayer bar.

It is powered by the free [Aladhan API](https://aladhan.com/prayer-times-api) (no API key required).

## Current Version

`1.0.1`

## Features

- Prayer times widget via shortcode: `[barakah]`
- New widget variants via shortcode:
  - `[barakah widget="prayer_times"]`
  - `[barakah widget="ramadan"]`
  - `[barakah widget="hadith"]`
  - `[barakah widget="dua"]`
  - `[barakah widget="date"]`
- Live clock and countdown to next prayer
- Sehri and Iftar highlight cards
- Full daily prayer list with active/next states
- Ramadan month modal (Hijri calendar + Sehri/Iftar times)
- Daily dua/zikr rotation (English + Bangla data support)
- Header/footer greetings
- Greeting popup with page targeting:
  - all pages
  - specific selected pages
- Sticky prayer bar with:
  - header/footer position
  - dark/light theme
  - page targeting (all/specific pages)
  - delayed open behavior and collapsible side tab
- Optional visitor location change
- Hijri adjustment and caution-minute adjustments
- Transient caching with configurable cache hours (default: 2)
- Onboarding wizard for first-time setup

## Requirements

- WordPress `5.5+`
- PHP `7.4+`

## Installation

1. Upload the `barakah` folder to `/wp-content/plugins/`
2. Activate the plugin from **Plugins**
3. Open **Barakah** admin menu and configure settings
4. Add shortcode to any page/post:

```txt
[barakah]
```

## Shortcode

Use global settings:

```txt
[barakah]
```

Supported attributes:

- `city` (string)
- `country` (string)
- `method` (number/string; Aladhan method id)
- `mode` (`dark` or `light`)
- `widget` (`full`, `prayer_times`, `ramadan`, `hadith`, `dua`, `date`)

All available shortcode variants:

```txt
[barakah]
[barakah widget="full"]
[barakah widget="prayer_times"]
[barakah widget="ramadan"]
[barakah widget="hadith"]
[barakah widget="dua"]
[barakah widget="date"]
```

Common combinations:

```txt
[barakah city="London" country="UK"]
[barakah city="Istanbul" country="Turkey" method="13"]
[barakah mode="dark"]
[barakah mode="light"]
[barakah widget="prayer_times" mode="light"]
[barakah widget="ramadan" city="London" country="UK" method="2" mode="dark"]
[barakah widget="hadith" mode="light"]
[barakah widget="dua" mode="dark"]
[barakah widget="date" city="Dhaka" country="Bangladesh"]
```

## Admin Settings Overview

- **General**: city, country, calculation method, cache duration
- **Calendar & Timings**: Hijri adjustment, Sehri/Iftar caution minutes
- **Widget Display**: layout, greetings, location change toggle
- **Greeting Popup**: enable, message, page scope, selected pages
- **Sticky Bar**: enable, position, theme, greeting text, page scope, selected pages
- **Shortcode**: quick usage examples

## Caching

- Prayer times are cached with WordPress transients.
- Default cache duration: **2 hours**.
- Cache is flushed when relevant settings change.

## Security Notes

- Admin settings and onboarding are protected by capability checks and nonces.
- Plugin sanitizes and validates settings input.
- Frontend dynamic rendering escapes external/API-derived text before HTML insertion.

## Project Structure

```txt
barakah/
  barakah.php
  readme.txt
  README.md
  assets/
    css/
      barakah.css
      barakah-admin.css
    js/
      barakah.js
      barakah-admin.js
  includes/
    admin.php
    onboarding.php
    shortcode.php
    class-barakah-api.php
  data/
    bangla_duas.json
```

## Changelog

### 1.0.1

- Added five new shortcode widget variants:
  - `prayer_times` (full day prayer table)
  - `ramadan` (Sehri/Iftar essentials)
  - `hadith` (random hadith from API + fallback)
  - `dua` (random daily dua)
  - `date` (Islamic date + next prayer)
- Added compact widget card/table styles for dark and light modes

### 1.0.0

- Added settings tabs and refreshed admin UX
- Added onboarding wizard flow
- Added greeting popup page targeting
- Added sticky bar page targeting
- Added sticky bar delayed open + enhanced collapse/expand transitions
- Set sticky theme default to dark for new installs
- Updated cache default to 2 hours
- Improved frontend escaping for API-derived values
- Prevented duplicated dua interval timers on re-render
- Updated docs and release-readiness guidance

## License

GPL-2.0+
