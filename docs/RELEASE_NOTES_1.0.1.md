# Barakah v1.0.1 Release Notes

## Highlights

- Added 5 new shortcode widget variants:
  - `prayer_times` - full day prayer timetable
  - `ramadan` - Sehri/Iftar-focused Ramadan card
  - `hadith` - random hadith from public API (with local fallback)
  - `dua` - random daily dua
  - `date` - Islamic date with next prayer summary
- Added compact widget design system for dark and light modes

## Stability and Security

- Kept admin protection model (capability + nonce)
- Continued escaped frontend rendering for external/API-derived data

## Notes

- Main full widget remains default: `[barakah]`
- New variants are available using `widget` attribute
