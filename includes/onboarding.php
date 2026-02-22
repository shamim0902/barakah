<?php
/**
 * Barakah – First-time onboarding setup wizard.
 *
 * @package Barakah
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ── Register hidden onboarding page ──────────────────────────────────── */

add_action( 'admin_menu', 'barakah_onboarding_menu' );
function barakah_onboarding_menu() {
    add_submenu_page(
        'barakah-settings',
        __( 'Barakah Setup Wizard', 'barakah' ),
        __( 'Barakah Setup', 'barakah' ),
        'manage_options',
        'barakah-onboarding',
        'barakah_onboarding_page'
    );
}

// Hide the submenu link from the sidebar via CSS (keeps $submenu intact for capability checks)
add_action( 'admin_head', 'barakah_hide_onboarding_menu_item' );
function barakah_hide_onboarding_menu_item() {
    echo '<style>#adminmenu a[href="admin.php?page=barakah-onboarding"]{display:none!important}</style>';
}

/* ── Render onboarding page ───────────────────────────────────────────── */

function barakah_onboarding_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $saved = false;
    $error = '';

    /* ── Handle form submission ─────────────────────────────────────── */
    if ( isset( $_POST['barakah_onboarding_save'] ) ) {
        if ( ! check_admin_referer( 'barakah_onboarding_nonce', 'barakah_onboarding_nonce_field' ) ) {
            $error = 'Security check failed.';
        } else {
            $city    = sanitize_text_field( wp_unslash( $_POST['barakah_city'] ?? '' ) );
            $country = sanitize_text_field( wp_unslash( $_POST['barakah_country'] ?? '' ) );

            if ( empty( $city ) || empty( $country ) ) {
                $error = 'City and Country are required.';
            } else {
                update_option( 'barakah_city',    $city );
                update_option( 'barakah_country', $country );

                $method = sanitize_text_field( wp_unslash( $_POST['barakah_method'] ?? '1' ) );
                update_option( 'barakah_method', $method );

                $hijri_dir = sanitize_text_field( wp_unslash( $_POST['barakah_hijri_adjust_direction'] ?? 'none' ) );
                if ( ! in_array( $hijri_dir, [ 'none', 'before', 'after' ], true ) ) {
                    $hijri_dir = 'none';
                }
                update_option( 'barakah_hijri_adjust_direction', $hijri_dir );

                $hijri_days = (int) sanitize_text_field( wp_unslash( $_POST['barakah_hijri_adjust_days'] ?? '0' ) );
                $hijri_days = max( 0, min( 3, $hijri_days ) );
                update_option( 'barakah_hijri_adjust_days', $hijri_days );

                $sehri = (int) sanitize_text_field( wp_unslash( $_POST['barakah_sehri_caution_minutes'] ?? '0' ) );
                $sehri = max( 0, min( 60, $sehri ) );
                update_option( 'barakah_sehri_caution_minutes', $sehri );

                $iftar = (int) sanitize_text_field( wp_unslash( $_POST['barakah_iftar_caution_minutes'] ?? '0' ) );
                $iftar = max( 0, min( 60, $iftar ) );
                update_option( 'barakah_iftar_caution_minutes', $iftar );

                update_option( 'barakah_onboarding_complete', '1' );
                Barakah_API::flush_cache();
                $saved = true;
            }
        }
    }

    /* ── Load current values ────────────────────────────────────────── */
    $city       = get_option( 'barakah_city',        'Dhaka' );
    $country    = get_option( 'barakah_country',     'Bangladesh' );
    $method     = get_option( 'barakah_method',      '1' );
    $hijri_dir  = get_option( 'barakah_hijri_adjust_direction', 'none' );
    $hijri_days = (int) get_option( 'barakah_hijri_adjust_days', 0 );
    $sehri      = (int) get_option( 'barakah_sehri_caution_minutes', 0 );
    $iftar      = (int) get_option( 'barakah_iftar_caution_minutes', 0 );
    $methods    = barakah_get_methods();
    ?>
    <style>
        /* Hide all third-party admin notices on onboarding page */
        .wrap > .notice,
        .wrap > .updated,
        .wrap > .error,
        .wrap > .update-nag,
        #wpbody-content > .notice,
        #wpbody-content > .updated,
        #wpbody-content > .error,
        #wpbody-content > .update-nag,
        div.notice:not(.bk-notice),
        div.updated:not(.bk-notice),
        div.error:not(.bk-notice) {
            display: none !important;
        }

        /* ── Wizard Layout ── */
        .bk-wizard-wrap {
            max-width: 680px;
            margin: 30px auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        /* Hero — reuse admin pattern */
        .bk-wiz-hero {
            background: linear-gradient(135deg, #0d0d2b 0%, #1a0a3e 100%);
            border-radius: 14px;
            padding: 28px 32px;
            margin: 0 0 28px;
            display: flex;
            align-items: center;
            gap: 20px;
            border: 1px solid rgba(245,200,66,0.25);
            box-shadow: 0 4px 32px rgba(0,0,0,0.35);
        }
        .bk-wiz-hero-icon { font-size: 3rem; line-height: 1; }
        .bk-wiz-hero h1 {
            color: #F5C842;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 4px;
            padding: 0;
        }
        .bk-wiz-hero p {
            color: rgba(255,235,180,0.65);
            margin: 0;
            font-size: 0.88rem;
        }

        /* Progress bar */
        .bk-wizard-progress {
            display: flex;
            align-items: flex-start;
            margin-bottom: 24px;
            padding: 0 8px;
        }
        .bk-progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            z-index: 1;
        }
        .bk-progress-dot {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.82rem; font-weight: 700; color: #999;
            transition: all 0.3s;
        }
        .bk-progress-dot-active {
            background: linear-gradient(135deg, #2c1a6e, #1a0a3e);
            color: #F5C842;
            box-shadow: 0 0 0 4px rgba(245,200,66,0.18);
        }
        .bk-progress-dot-done {
            background: #2ecc71; color: #fff;
        }
        .bk-progress-label {
            font-size: 0.68rem; color: #999; font-weight: 600; text-align: center;
        }
        .bk-progress-label-active { color: #1e1e3a; }
        .bk-progress-line {
            flex: 1; height: 2px; background: #e0e0e0;
            margin: 16px -4px 0; transition: background 0.3s;
        }
        .bk-progress-line-done { background: #2ecc71; }

        /* Step visibility */
        .bk-step { display: none; }
        .bk-step-active { display: block; }

        /* Card — reuse admin pattern */
        .bk-wiz-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 24px 28px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .bk-wiz-card h2 {
            font-size: 1rem; font-weight: 600; color: #1e1e3a;
            margin: 0 0 6px; padding: 0;
            display: flex; align-items: center; gap: 8px;
        }
        .bk-wiz-card h2 .dashicons { color: #6B8CDE; }
        .bk-wiz-card .bk-step-desc {
            color: #777; font-size: 0.84rem; margin: 0 0 20px; line-height: 1.5;
        }

        /* Fields — reuse admin pattern */
        .bk-field { margin-bottom: 18px; }
        .bk-field:last-child { margin-bottom: 0; }
        .bk-field label {
            display: block; font-weight: 600; font-size: 0.85rem; color: #333; margin-bottom: 6px;
        }
        .bk-field input[type="text"],
        .bk-field input[type="number"],
        .bk-field select {
            width: 100%; max-width: 420px;
            padding: 9px 13px; border: 1.5px solid #ddd; border-radius: 8px;
            font-size: 0.9rem; transition: border-color 0.2s; outline: none; background: #fafafa;
        }
        .bk-field input:focus, .bk-field select:focus {
            border-color: #6B8CDE; background: #fff;
            box-shadow: 0 0 0 3px rgba(107,140,222,0.12);
        }
        .bk-field .description { font-size: 0.78rem; color: #888; margin-top: 5px; }
        .bk-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 600px) { .bk-field-row { grid-template-columns: 1fr; } }

        /* Method tags */
        .bk-methods-info {
            background: #f8f7ff; border: 1px solid #e0dcff; border-radius: 8px; padding: 12px 16px; margin-top: 8px;
        }
        .bk-methods-info p { margin: 0 0 6px; font-size: 0.8rem; color: #555; }
        .bk-method-tag {
            display: inline-block; background: rgba(107,140,222,0.12); border: 1px solid rgba(107,140,222,0.25);
            border-radius: 6px; padding: 2px 8px; font-size: 0.72rem; color: #4a6abf; margin: 2px;
        }

        /* Navigation */
        .bk-wizard-nav {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: 20px; padding-top: 16px;
        }
        .bk-btn-back {
            background: #f0f0f0; border: 1px solid #ddd; border-radius: 8px;
            padding: 10px 24px; font-size: 0.88rem; font-weight: 600; color: #555; cursor: pointer;
            transition: all 0.2s;
        }
        .bk-btn-back:hover { background: #e5e5e5; }
        .bk-btn-next, .bk-btn-finish {
            background: linear-gradient(135deg, #2c1a6e, #1a0a3e);
            color: #F5C842; border: 1px solid rgba(245,200,66,0.4); border-radius: 8px;
            padding: 10px 28px; font-size: 0.88rem; font-weight: 600; cursor: pointer;
            transition: all 0.2s;
        }
        .bk-btn-next:hover, .bk-btn-finish:hover {
            background: linear-gradient(135deg, #3a2480, #250f52);
            box-shadow: 0 4px 16px rgba(107,140,222,0.3);
        }
        .bk-skip-link {
            color: #999; font-size: 0.78rem; text-decoration: none; transition: color 0.2s;
        }
        .bk-skip-link:hover { color: #555; text-decoration: underline; }

        /* Notices */
        .bk-notice {
            border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; font-weight: 600; font-size: 0.88rem;
        }
        .bk-notice-error { background: #fef0f0; border-left: 4px solid #e74c3c; color: #7a1a1a; }

        /* Success screen */
        .bk-success-wrap { text-align: center; padding: 48px 28px; position: relative; overflow: hidden; }
        #bk-ob-confetti { position: fixed; inset: 0; width: 100%; height: 100%; pointer-events: none; z-index: 99999; }
        .bk-success-icon { font-size: 4rem; margin-bottom: 12px; }
        .bk-success-title { font-size: 1.4rem; font-weight: 700; color: #1e1e3a; margin-bottom: 10px; }
        .bk-success-msg { color: #555; font-size: 0.88rem; line-height: 1.8; margin-bottom: 24px; }
        .bk-shortcode-box {
            background: rgba(245,200,66,0.12); border: 1px solid rgba(245,200,66,0.35);
            border-radius: 8px; padding: 3px 12px; color: #b8920a; font-family: monospace;
            font-size: 1rem; display: inline-block; margin: 4px 0;
        }
        .bk-success-btn {
            display: inline-block; text-decoration: none;
            background: linear-gradient(135deg, #2c1a6e, #1a0a3e);
            color: #F5C842; border: 1px solid rgba(245,200,66,0.4); border-radius: 8px;
            padding: 12px 32px; font-size: 0.95rem; font-weight: 600; transition: all 0.2s;
        }
        .bk-success-btn:hover, .bk-success-btn:focus {
            background: linear-gradient(135deg, #3a2480, #250f52); color: #F5C842;
            box-shadow: 0 4px 16px rgba(107,140,222,0.3);
        }

        /* Validation highlight */
        .bk-field-error { border-color: #e74c3c !important; }
    </style>

    <div class="wrap bk-wizard-wrap">

        <!-- Hero Banner -->
        <div class="bk-wiz-hero">
            <div class="bk-wiz-hero-icon">🌙</div>
            <div>
                <h1>Welcome to Barakah!</h1>
                <p>Let's set up your Ramadan prayer times in a few quick steps.</p>
            </div>
        </div>

        <?php if ( $error ) : ?>
            <div class="bk-notice bk-notice-error"><?php echo esc_html( $error ); ?></div>
        <?php endif; ?>

        <?php if ( $saved ) : ?>

        <canvas id="bk-ob-confetti"></canvas>

        <!-- ── Success Screen ──────────────────────────────────────────── -->
        <div class="bk-wiz-card bk-success-wrap">
            <div class="bk-success-icon">🎉</div>
            <div class="bk-success-title">Barakah is ready!</div>
            <div class="bk-success-msg">
                Your prayer times are now configured.<br>
                Place this shortcode on any page or post:<br>
                <div class="bk-shortcode-box">[barakah]</div><br>
                You can also override per-page:<br>
                <div class="bk-shortcode-box">[barakah city="London" country="UK"]</div>
            </div>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=barakah-settings' ) ); ?>" class="bk-success-btn">
                Go to Full Settings &rarr;
            </a>
        </div>

        <?php else : ?>

        <!-- ── Progress Bar ────────────────────────────────────────────── -->
        <div class="bk-wizard-progress">
            <div class="bk-progress-step">
                <div class="bk-progress-dot bk-progress-dot-active" id="bk-pdot-1">1</div>
                <div class="bk-progress-label bk-progress-label-active" id="bk-plbl-1">Location</div>
            </div>
            <div class="bk-progress-line" id="bk-pline-1"></div>
            <div class="bk-progress-step">
                <div class="bk-progress-dot" id="bk-pdot-2">2</div>
                <div class="bk-progress-label" id="bk-plbl-2">Method</div>
            </div>
            <div class="bk-progress-line" id="bk-pline-2"></div>
            <div class="bk-progress-step">
                <div class="bk-progress-dot" id="bk-pdot-3">3</div>
                <div class="bk-progress-label" id="bk-plbl-3">Calendar</div>
            </div>
            <div class="bk-progress-line" id="bk-pline-3"></div>
            <div class="bk-progress-step">
                <div class="bk-progress-dot" id="bk-pdot-4">4</div>
                <div class="bk-progress-label" id="bk-plbl-4">Caution</div>
            </div>
        </div>

        <!-- ── Wizard Form ─────────────────────────────────────────────── -->
        <form method="post" id="bk-wizard-form">
            <?php wp_nonce_field( 'barakah_onboarding_nonce', 'barakah_onboarding_nonce_field' ); ?>

            <!-- STEP 1: Location -->
            <div class="bk-step bk-step-active" id="bk-step-1">
                <div class="bk-wiz-card">
                    <h2><span class="dashicons dashicons-location"></span> Where are you?</h2>
                    <p class="bk-step-desc">Enter your city and country so we can fetch accurate prayer times for your location.</p>
                    <div class="bk-field-row">
                        <div class="bk-field">
                            <label for="barakah_city">City</label>
                            <input
                                type="text"
                                id="barakah_city"
                                name="barakah_city"
                                value="<?php echo esc_attr( $city ); ?>"
                                placeholder="e.g. London, New York, Karachi"
                            />
                        </div>
                        <div class="bk-field">
                            <label for="barakah_country">Country</label>
                            <input
                                type="text"
                                id="barakah_country"
                                name="barakah_country"
                                value="<?php echo esc_attr( $country ); ?>"
                                placeholder="e.g. UK, USA, Pakistan"
                            />
                        </div>
                    </div>
                </div>
                <div class="bk-wizard-nav">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=barakah-settings' ) ); ?>" class="bk-skip-link">
                        Skip wizard, go to settings &rarr;
                    </a>
                    <button type="button" class="bk-btn-next" onclick="bkWizardNext()">Next &rarr;</button>
                </div>
            </div>

            <!-- STEP 2: Calculation Method -->
            <div class="bk-step" id="bk-step-2">
                <div class="bk-wiz-card">
                    <h2><span class="dashicons dashicons-calculator"></span> Calculation Method</h2>
                    <p class="bk-step-desc">Choose the calculation method used in your region for accurate prayer times.</p>
                    <div class="bk-field">
                        <label for="barakah_method">Prayer Time Calculation Method</label>
                        <select id="barakah_method" name="barakah_method">
                            <?php foreach ( $methods as $val => $label ) : ?>
                                <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $method, $val ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="bk-methods-info">
                        <p><strong>Quick guide by region:</strong></p>
                        <span class="bk-method-tag">South Asia &rarr; Method 1 or 5</span>
                        <span class="bk-method-tag">Middle East &rarr; Method 4 or 8</span>
                        <span class="bk-method-tag">North America &rarr; Method 2</span>
                        <span class="bk-method-tag">Europe &rarr; Method 1 or 12</span>
                        <span class="bk-method-tag">Turkey &rarr; Method 13</span>
                        <span class="bk-method-tag">Egypt &rarr; Method 3</span>
                    </div>
                </div>
                <div class="bk-wizard-nav">
                    <button type="button" class="bk-btn-back" onclick="bkWizardBack()">&larr; Back</button>
                    <button type="button" class="bk-btn-next" onclick="bkWizardNext()">Next &rarr;</button>
                </div>
            </div>

            <!-- STEP 3: Hijri Calendar Adjustment -->
            <div class="bk-step" id="bk-step-3">
                <div class="bk-wiz-card">
                    <h2><span class="dashicons dashicons-calendar-alt"></span> Hijri Calendar Adjustment</h2>
                    <p class="bk-step-desc">
                        In some regions (e.g. Bangladesh, parts of South Asia), the moon is sighted a day later than the standard calendar. Adjust the Hijri day count for your location.
                    </p>
                    <div class="bk-field">
                        <label>Adjust Arabic calendar for my location</label>
                        <div style="display:flex;flex-direction:column;gap:8px;margin-top:4px;">
                            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-weight:400;">
                                <input type="radio" name="barakah_hijri_adjust_direction" value="none" <?php checked( $hijri_dir, 'none' ); ?> />
                                No adjustment (use standard calendar)
                            </label>
                            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-weight:400;">
                                <input type="radio" name="barakah_hijri_adjust_direction" value="before" <?php checked( $hijri_dir, 'before' ); ?> />
                                Day(s) before — moon sighted earlier in my region
                            </label>
                            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-weight:400;">
                                <input type="radio" name="barakah_hijri_adjust_direction" value="after" <?php checked( $hijri_dir, 'after' ); ?> />
                                Day(s) after — moon sighted later in my region (e.g. Bangladesh)
                            </label>
                        </div>
                    </div>
                    <div class="bk-field">
                        <label for="barakah_hijri_adjust_days">Number of days to adjust</label>
                        <input
                            type="number"
                            id="barakah_hijri_adjust_days"
                            name="barakah_hijri_adjust_days"
                            value="<?php echo esc_attr( $hijri_days ); ?>"
                            min="0"
                            max="3"
                            style="max-width: 120px;"
                        />
                        <div class="description">
                            For Bangladesh, select "Day(s) after" with value 1. Range: 0–3 days.
                        </div>
                    </div>
                </div>
                <div class="bk-wizard-nav">
                    <button type="button" class="bk-btn-back" onclick="bkWizardBack()">&larr; Back</button>
                    <button type="button" class="bk-btn-next" onclick="bkWizardNext()">Next &rarr;</button>
                </div>
            </div>

            <!-- STEP 4: Caution Times -->
            <div class="bk-step" id="bk-step-4">
                <div class="bk-wiz-card">
                    <h2><span class="dashicons dashicons-warning"></span> Caution Times</h2>
                    <p class="bk-step-desc">
                        Add a safety margin to Sehri and Iftar times. Sehri time will be moved earlier, Iftar time will be moved later by the specified minutes.
                    </p>
                    <div class="bk-field-row">
                        <div class="bk-field">
                            <label for="barakah_sehri_caution_minutes">Sehri time adjustment (minutes)</label>
                            <input
                                type="number"
                                id="barakah_sehri_caution_minutes"
                                name="barakah_sehri_caution_minutes"
                                value="<?php echo esc_attr( $sehri ); ?>"
                                min="0"
                                max="60"
                                style="max-width: 120px;"
                            />
                            <div class="description">
                                Minutes to subtract from Fajr/Sehri time. E.g. 5 means Sehri ends 5 min before Fajr adhan.
                            </div>
                        </div>
                        <div class="bk-field">
                            <label for="barakah_iftar_caution_minutes">Iftar time adjustment (minutes)</label>
                            <input
                                type="number"
                                id="barakah_iftar_caution_minutes"
                                name="barakah_iftar_caution_minutes"
                                value="<?php echo esc_attr( $iftar ); ?>"
                                min="0"
                                max="60"
                                style="max-width: 120px;"
                            />
                            <div class="description">
                                Minutes to add to Maghrib/Iftar time. E.g. 3 means Iftar starts 3 min after Maghrib adhan.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bk-wizard-nav">
                    <button type="button" class="bk-btn-back" onclick="bkWizardBack()">&larr; Back</button>
                    <button type="submit" name="barakah_onboarding_save" class="bk-btn-finish">Finish Setup &check;</button>
                </div>
            </div>

        </form>
        <?php endif; ?>

    </div>

    <?php if ( $saved ) : ?>
    <script>
    (function() {
        var canvas  = document.getElementById('bk-ob-confetti');
        if (!canvas) return;
        var ctx     = canvas.getContext('2d');
        var pieces  = [];
        var raf     = null;
        var W       = canvas.width  = window.innerWidth;
        var H       = canvas.height = window.innerHeight;
        var colors  = ['#F5C842','#6B8CDE','#2ecc71','#e74c3c','#9b59b6','#e67e22','#fff'];

        function rand(a, b) { return a + Math.random() * (b - a); }

        for (var i = 0; i < 160; i++) {
            pieces.push({
                x:  rand(0, W),
                y:  rand(-H, 0),
                w:  rand(6, 14),
                h:  rand(4, 10),
                r:  rand(0, Math.PI * 2),
                dr: rand(-0.15, 0.15),
                vx: rand(-1.5, 1.5),
                vy: rand(2.5, 6),
                color: colors[Math.floor(Math.random() * colors.length)],
                alpha: 1
            });
        }

        function draw() {
            ctx.clearRect(0, 0, W, H);
            var alive = false;
            for (var i = 0; i < pieces.length; i++) {
                var p = pieces[i];
                if (p.y > H + 20) continue;
                alive = true;
                p.x  += p.vx;
                p.y  += p.vy;
                p.r  += p.dr;
                p.vy += 0.05; // gravity
                p.alpha = Math.max(0, 1 - (p.y / H) * 0.4);
                ctx.save();
                ctx.globalAlpha = p.alpha;
                ctx.translate(p.x, p.y);
                ctx.rotate(p.r);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                ctx.restore();
            }
            if (alive) {
                raf = requestAnimationFrame(draw);
            } else {
                canvas.style.display = 'none';
            }
        }

        raf = requestAnimationFrame(draw);

        window.addEventListener('resize', function() {
            W = canvas.width  = window.innerWidth;
            H = canvas.height = window.innerHeight;
        });
    })();
    </script>
    <?php endif; ?>

    <?php if ( ! $saved ) : ?>
    <script>
    (function() {
        'use strict';

        var currentStep = 1;
        var totalSteps  = 4;

        function showStep(n) {
            for (var i = 1; i <= totalSteps; i++) {
                var el = document.getElementById('bk-step-' + i);
                if (el) el.classList.remove('bk-step-active');
            }
            var target = document.getElementById('bk-step-' + n);
            if (target) target.classList.add('bk-step-active');

            /* Update progress dots & lines */
            for (var j = 1; j <= totalSteps; j++) {
                var dot  = document.getElementById('bk-pdot-' + j);
                var lbl  = document.getElementById('bk-plbl-' + j);
                var line = document.getElementById('bk-pline-' + (j - 1));

                if (!dot) continue;
                dot.classList.remove('bk-progress-dot-active', 'bk-progress-dot-done');
                if (lbl) lbl.classList.remove('bk-progress-label-active');

                if (j < n) {
                    dot.classList.add('bk-progress-dot-done');
                    dot.innerHTML = '&#10003;';
                } else if (j === n) {
                    dot.classList.add('bk-progress-dot-active');
                    dot.textContent = j;
                    if (lbl) lbl.classList.add('bk-progress-label-active');
                } else {
                    dot.textContent = j;
                }

                if (line) {
                    if (j <= n) line.classList.add('bk-progress-line-done');
                    else        line.classList.remove('bk-progress-line-done');
                }
            }

            var wrap = document.querySelector('.bk-wizard-wrap');
            if (wrap) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });

            currentStep = n;
        }

        window.bkWizardNext = function() {
            /* Validate step 1 */
            if (currentStep === 1) {
                var city    = document.getElementById('barakah_city');
                var country = document.getElementById('barakah_country');
                var valid   = true;

                if (city && !city.value.trim()) {
                    city.classList.add('bk-field-error');
                    city.focus();
                    valid = false;
                } else if (city) {
                    city.classList.remove('bk-field-error');
                }

                if (country && !country.value.trim()) {
                    country.classList.add('bk-field-error');
                    if (valid) country.focus();
                    valid = false;
                } else if (country) {
                    country.classList.remove('bk-field-error');
                }

                if (!valid) return;
            }

            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
            }
        };

        window.bkWizardBack = function() {
            if (currentStep > 1) {
                showStep(currentStep - 1);
            }
        };

        /* Clear error highlight on input */
        var cityEl    = document.getElementById('barakah_city');
        var countryEl = document.getElementById('barakah_country');
        if (cityEl)    cityEl.addEventListener('input',    function() { this.classList.remove('bk-field-error'); });
        if (countryEl) countryEl.addEventListener('input', function() { this.classList.remove('bk-field-error'); });
    })();
    </script>
    <?php endif; ?>
    <?php
}
