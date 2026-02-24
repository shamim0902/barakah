# Barakah Release Checklist

Use this checklist before shipping a new plugin release.

Last updated: 2026-02-24

## 1) Versioning

- [x] Update plugin header version in `barakah.php`
- [x] Update `BARAKAH_VERSION` constant in `barakah.php`
- [x] Update `Stable tag` in `readme.txt`
- [x] Add changelog entry in `readme.txt` and `README.md`

## 2) Quality and Security

- [x] Run PHP syntax checks:
  - `php -l barakah.php`
  - `php -l includes/admin.php`
  - `php -l includes/onboarding.php`
  - `php -l includes/shortcode.php`
  - `php -l includes/class-barakah-api.php`
- [x] Run JS syntax checks:
  - `node --check assets/js/barakah.js`
  - `node --check assets/js/barakah-admin.js`
- [x] Verify admin forms still enforce capability + nonce checks
- [x] Verify frontend uses escaped rendering for external/API data

## 3) Functional Smoke Test

- [ ] Widget renders with `[barakah]` (Blocked: runtime WP DB access unavailable in current sandbox)
- [ ] Countdown and prayer highlight update correctly (Blocked: runtime WP DB access unavailable in current sandbox)
- [ ] Month modal loads data and scrolls to today (Blocked: runtime WP DB access unavailable in current sandbox)
- [ ] Greeting popup works with all-pages and specific-pages scopes (Blocked: runtime WP DB access unavailable in current sandbox)
- [ ] Sticky bar works with all-pages and specific-pages scopes (Blocked: runtime WP DB access unavailable in current sandbox)
- [ ] Sticky bar is collapsed initially and auto-opens after delay (Blocked: runtime WP DB access unavailable in current sandbox)
- [ ] Sticky bar dark/light themes render correctly (Blocked: runtime WP DB access unavailable in current sandbox)
- [ ] Location change (if enabled) updates prayer times (Blocked: runtime WP DB access unavailable in current sandbox)
- [ ] Cache duration setting works (default 2 hours) (Blocked: runtime WP DB access unavailable in current sandbox)

## 4) WordPress.org Readiness

- [x] `readme.txt` sections are complete and accurate
- [x] `Stable tag` matches release version
- [x] Tested WordPress/PHP values are updated if needed
- [x] Changelog and upgrade notice updated

## 5) Marketing Assets (for wp.org SVN `/assets`)

Prepare these files:

- [ ] `banner-772x250.png` (standard banner) (Pending: designer/authoring asset)
- [ ] `banner-1544x500.png` (retina banner) (Pending: designer/authoring asset)
- [ ] `icon-128x128.png` (plugin icon) (Pending: designer/authoring asset)
- [ ] `icon-256x256.png` (retina icon) (Pending: designer/authoring asset)
- [ ] `screenshot-1.png` ... `screenshot-N.png` (UI screenshots referenced in readme) (Pending: capture from live WP instance)

Recommended visual set:

1. Main widget (dark mode)
2. Main widget (light mode)
3. Greeting popup settings + preview
4. Sticky bar (collapsed + expanded)
5. Sticky bar page targeting settings
6. Onboarding wizard

## 6) Packaging

- [x] Ensure release zip root folder is `barakah/`
- [x] Exclude local/dev files (`.git`, editor files, temp artifacts)
- [x] Include only runtime plugin files and docs
- [x] Create zip: `barakah.zip` (`builds/barakah.zip`)

## 7) Release Operations

- [x] Tag release in git (e.g. `v1.0.0`) (Created: `v1.0.0`)
- [x] Publish GitHub release notes from changelog (Prepared: `docs/RELEASE_NOTES_1.0.0.md`)
- [ ] Upload plugin zip to release (Manual: GitHub UI/API)
- [ ] If using wp.org SVN, commit trunk + tag + assets (Manual: wp.org credentials + assets)

## 8) Post-release Verification

- [ ] Fresh install test on clean WordPress (Blocked: runtime WP DB access unavailable in current sandbox)
- [ ] Upgrade test from prior version (Blocked: runtime WP DB access unavailable in current sandbox)
- [ ] Confirm no PHP warnings in debug log (Blocked: runtime WP DB access unavailable in current sandbox)
- [ ] Confirm frontend works for logged-out visitors (Blocked: runtime WP DB access unavailable in current sandbox)
