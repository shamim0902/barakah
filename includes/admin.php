<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ── Register admin menu ─────────────────────────────────────────────────── */

add_action( 'admin_menu', 'barakah_admin_menu' );
function barakah_admin_menu() {
    add_menu_page(
        __( 'Barakah Settings', 'barakah' ),
        __( 'Barakah', 'barakah' ),
        'manage_options',
        'barakah-settings',
        'barakah_settings_page',
        'dashicons-clock',
        30
    );
}

/* ── Calculation methods ─────────────────────────────────────────────────── */

function barakah_get_methods() {
    return [
        '1'  => 'Muslim World League',
        '2'  => 'Islamic Society of North America (ISNA)',
        '3'  => 'Egyptian General Authority of Survey',
        '4'  => 'Umm Al-Qura University, Makkah',
        '5'  => 'University of Islamic Sciences, Karachi',
        '7'  => 'Institute of Geophysics, University of Tehran',
        '8'  => 'Gulf Region',
        '9'  => 'Kuwait',
        '10' => 'Qatar',
        '11' => 'Majlis Ugama Islam Singapura (Singapore)',
        '12' => 'Union Organization Islamic de France',
        '13' => 'Diyanet İşleri Başkanlığı, Turkey',
        '14' => 'Spiritual Administration of Muslims of Russia',
        '15' => 'Moonsighting Committee Worldwide (Shaukat)',
    ];
}

/* ── Settings page ───────────────────────────────────────────────────────── */

function barakah_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $saved   = false;
    $error   = '';

    if ( isset( $_POST['barakah_save'] ) ) {
        if ( ! check_admin_referer( 'barakah_settings_nonce', 'barakah_nonce' ) ) {
            $error = 'Security check failed.';
        } else {
            $city    = sanitize_text_field( wp_unslash( $_POST['barakah_city']    ?? '' ) );
            $country = sanitize_text_field( wp_unslash( $_POST['barakah_country'] ?? '' ) );
            $method  = sanitize_text_field( wp_unslash( $_POST['barakah_method']  ?? '1' ) );

            if ( empty( $city ) || empty( $country ) ) {
                $error = 'City and Country cannot be empty.';
            } else {
                update_option( 'barakah_city',    $city );
                update_option( 'barakah_country', $country );
                update_option( 'barakah_method',  $method );

                $cache_hours = (int) sanitize_text_field( wp_unslash( $_POST['barakah_cache_hours'] ?? '6' ) );
                $cache_hours = max( 1, min( 168, $cache_hours ) );
                update_option( 'barakah_cache_hours', $cache_hours );

                $two_column = isset( $_POST['barakah_two_column'] ) ? '1' : '0';
                update_option( 'barakah_two_column', $two_column );

                $allow_location_change = isset( $_POST['barakah_allow_location_change'] ) ? '1' : '0';
                update_option( 'barakah_allow_location_change', $allow_location_change );

                $header_greeting = sanitize_text_field( wp_unslash( $_POST['barakah_header_greeting'] ?? '' ) );
                update_option( 'barakah_header_greeting', $header_greeting );

                $greeting = sanitize_text_field( wp_unslash( $_POST['barakah_greeting'] ?? '' ) );
                update_option( 'barakah_greeting', $greeting );

                $greeting_popup = isset( $_POST['barakah_greeting_popup'] ) ? '1' : '0';
                update_option( 'barakah_greeting_popup', $greeting_popup );

                $greeting_popup_title = sanitize_text_field( wp_unslash( $_POST['barakah_greeting_popup_title'] ?? '' ) );
                update_option( 'barakah_greeting_popup_title', $greeting_popup_title );

                $greeting_popup_msg = sanitize_textarea_field( wp_unslash( $_POST['barakah_greeting_popup_msg'] ?? '' ) );
                update_option( 'barakah_greeting_popup_msg', $greeting_popup_msg );

                $greeting_popup_scope = sanitize_text_field( wp_unslash( $_POST['barakah_greeting_popup_scope'] ?? 'all' ) );
                if ( ! in_array( $greeting_popup_scope, [ 'all', 'specific' ], true ) ) {
                    $greeting_popup_scope = 'all';
                }
                update_option( 'barakah_greeting_popup_scope', $greeting_popup_scope );

                $raw_ids = isset( $_POST['barakah_greeting_popup_page_ids'] ) && is_array( $_POST['barakah_greeting_popup_page_ids'] )
                    ? $_POST['barakah_greeting_popup_page_ids']
                    : [];
                $clean_ids = array_map( 'absint', $raw_ids );
                $clean_ids = array_filter( $clean_ids );
                update_option( 'barakah_greeting_popup_page_ids', implode( ',', $clean_ids ) );

                $hijri_adjust_dir  = sanitize_text_field( wp_unslash( $_POST['barakah_hijri_adjust_direction'] ?? 'none' ) );
                if ( ! in_array( $hijri_adjust_dir, [ 'none', 'before', 'after' ], true ) ) {
                    $hijri_adjust_dir = 'none';
                }
                update_option( 'barakah_hijri_adjust_direction', $hijri_adjust_dir );

                $hijri_adjust_days = (int) sanitize_text_field( wp_unslash( $_POST['barakah_hijri_adjust_days'] ?? '0' ) );
                $hijri_adjust_days = max( 0, min( 3, $hijri_adjust_days ) );
                update_option( 'barakah_hijri_adjust_days', $hijri_adjust_days );

                $sehri_caution = (int) sanitize_text_field( wp_unslash( $_POST['barakah_sehri_caution_minutes'] ?? '0' ) );
                $sehri_caution = max( 0, min( 60, $sehri_caution ) );
                update_option( 'barakah_sehri_caution_minutes', $sehri_caution );

                $iftar_caution = (int) sanitize_text_field( wp_unslash( $_POST['barakah_iftar_caution_minutes'] ?? '0' ) );
                $iftar_caution = max( 0, min( 60, $iftar_caution ) );
                update_option( 'barakah_iftar_caution_minutes', $iftar_caution );

                Barakah_API::flush_cache();
                $saved = true;
            }
        }
    }

    $city        = get_option( 'barakah_city',        'Dhaka' );
    $country     = get_option( 'barakah_country',     'Bangladesh' );
    $method      = get_option( 'barakah_method',      '1' );
    $cache_hours = (int) get_option( 'barakah_cache_hours', 6 );
    $two_column  = get_option( 'barakah_two_column', '0' );
    $allow_location_change = get_option( 'barakah_allow_location_change', '0' );
    $header_greeting = get_option( 'barakah_header_greeting', '' );
    $greeting        = get_option( 'barakah_greeting', '' );
    $greeting_popup            = get_option( 'barakah_greeting_popup', '0' );
    $greeting_popup_title      = get_option( 'barakah_greeting_popup_title', 'رمضان مبارك · Ramadan Mubarak 🌙' );
    $greeting_popup_msg        = get_option( 'barakah_greeting_popup_msg', 'Wishing you and your family a blessed month of Ramadan!' );
    $greeting_popup_scope      = get_option( 'barakah_greeting_popup_scope', 'all' );
    $greeting_popup_page_ids   = get_option( 'barakah_greeting_popup_page_ids', '' );
    $selected_page_ids         = array_filter( array_map( 'absint', explode( ',', $greeting_popup_page_ids ) ) );
    $all_pages                 = get_pages( [ 'sort_column' => 'post_title', 'sort_order' => 'ASC' ] );
    $hijri_adjust_dir     = get_option( 'barakah_hijri_adjust_direction', 'none' );
    $hijri_adjust_days    = (int) get_option( 'barakah_hijri_adjust_days', 0 );
    $sehri_caution        = (int) get_option( 'barakah_sehri_caution_minutes', 0 );
    $iftar_caution        = (int) get_option( 'barakah_iftar_caution_minutes', 0 );
    $methods     = barakah_get_methods();
    ?>
    <style>
        /* ── Barakah Admin Styles ── */
        .bk-admin-wrap {
            max-width: 720px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 0.88rem;
        }
        .bk-admin-wrap .notice.notice-error { display: none; }
        .bk-admin-hero {
            background: linear-gradient(135deg, #0d0d2b 0%, #1a0a3e 100%);
            border-radius: 10px;
            padding: 16px 20px;
            margin: 14px 0 14px;
            display: flex;
            align-items: center;
            gap: 14px;
            border: 1px solid rgba(245,200,66,0.25);
            box-shadow: 0 2px 16px rgba(0,0,0,0.25);
        }
        .bk-admin-hero-icon { font-size: 2rem; line-height: 1; }
        .bk-admin-hero h1 {
            color: #F5C842;
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0 0 2px;
            padding: 0;
        }
        .bk-admin-hero p { color: rgba(255,235,180,0.65); margin: 0; font-size: 0.78rem; }
        .bk-shortcode-box {
            background: rgba(245,200,66,0.12);
            border: 1px solid rgba(245,200,66,0.35);
            border-radius: 6px;
            padding: 1px 8px;
            color: #F5C842;
            font-family: monospace;
            font-size: 0.85rem;
            display: inline-block;
            margin-top: 4px;
        }
        .bk-card-wrap {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 10px;
            box-shadow: none;
        }
        .bk-card-wrap h2 {
            font-size: 0.82rem;
            font-weight: 700;
            color: #1e1e3a;
            margin: 0 0 12px;
            padding: 0 0 8px;
            display: flex;
            align-items: center;
            gap: 6px;
            border-bottom: 1px solid #f0eefc;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .bk-card-wrap h2 .dashicons { color: #6B8CDE; font-size: 16px; width: 16px; height: 16px; }
        .bk-field { margin-bottom: 12px; }
        .bk-field:last-child { margin-bottom: 0; }
        .bk-field label {
            display: block;
            font-weight: 600;
            font-size: 0.8rem;
            color: #444;
            margin-bottom: 4px;
        }
        .bk-field input[type="text"],
        .bk-field input[type="number"],
        .bk-field select {
            width: 100%;
            max-width: 380px;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.84rem;
            transition: border-color 0.2s;
            outline: none;
            background: #fafafa;
        }
        .bk-field input[type="text"]:focus,
        .bk-field input[type="number"]:focus,
        .bk-field select:focus {
            border-color: #6B8CDE;
            background: #fff;
            box-shadow: 0 0 0 2px rgba(107,140,222,0.1);
        }
        .bk-field .description { font-size: 0.74rem; color: #999; margin-top: 3px; }
        .bk-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 600px) { .bk-field-row { grid-template-columns: 1fr; } }
        .bk-save-btn {
            background: linear-gradient(135deg, #2c1a6e, #1a0a3e);
            color: #F5C842;
            border: 1px solid rgba(245,200,66,0.4);
            border-radius: 6px;
            padding: 8px 22px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .bk-save-btn:hover {
            background: linear-gradient(135deg, #3a2480, #250f52);
            box-shadow: 0 3px 12px rgba(107,140,222,0.3);
        }
        .bk-notice {
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 0.82rem;
        }
        .bk-notice-success { background: #edfaf1; border-left: 3px solid #2ecc71; color: #1a6e3a; }
        .bk-notice-error   { background: #fef0f0; border-left: 3px solid #e74c3c; color: #7a1a1a; }
        .bk-methods-info {
            background: #f8f7ff;
            border: 1px solid #e0dcff;
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 6px;
        }
        .bk-methods-info p { margin: 0 0 4px; font-size: 0.74rem; color: #555; }
        .bk-method-tag {
            display: inline-block;
            background: rgba(107,140,222,0.1);
            border: 1px solid rgba(107,140,222,0.2);
            border-radius: 4px;
            padding: 1px 6px;
            font-size: 0.68rem;
            color: #4a6abf;
            margin: 1px;
        }
        .bk-preview-code {
            background: #1e1e3a;
            color: #F5C842;
            border-radius: 6px;
            padding: 10px 14px;
            font-family: monospace;
            font-size: 0.8rem;
            line-height: 1.6;
            margin-top: 6px;
        }
        .bk-preview-code .comment { color: rgba(255,255,255,0.35); }
        /* Two-column inner layout for card fields */
        .bk-card-wrap {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 16px;
            align-items: start;
        }
        /* Full-width items inside the card grid */
        .bk-card-wrap > h2,
        .bk-card-wrap > .bk-field-row,
        .bk-card-wrap > .description,
        .bk-card-wrap > p,
        .bk-card-wrap > .bk-preview-code,
        .bk-card-wrap > .bk-methods-info,
        .bk-card-wrap > .bk-field.bk-field-full,
        #bk-popup-settings > .bk-field.bk-field-full { grid-column: span 2; }
        @media (max-width: 600px) { .bk-card-wrap { grid-template-columns: 1fr; } }
        /* Preview button */
        .bk-btn-preview {
            display: inline-flex; align-items: center; gap: 6px;
            background: linear-gradient(135deg, #0d0d2b, #1a0a3e);
            color: #F5C842; border: 1px solid rgba(245,200,66,0.4);
            border-radius: 6px; padding: 6px 16px; font-size: 0.8rem; font-weight: 600;
            cursor: pointer; transition: all 0.2s; text-decoration: none;
        }
        .bk-btn-preview:hover { background: linear-gradient(135deg, #2c1a6e, #1a0a3e); color: #F5C842; }
        /* Greeting popup preview styles */
        #bk-admin-popup-overlay {
            position: fixed; inset: 0; z-index: 999999;
            display: flex; align-items: center; justify-content: center;
            background: rgba(5,5,20,0.7); padding: 24px;
            animation: bkAdminFadeIn 0.3s ease;
        }
        @keyframes bkAdminFadeIn { from { opacity: 0; } to { opacity: 1; } }
        #bk-admin-popup-overlay .bk-greeting-panel {
            position: relative; z-index: 1;
            background: radial-gradient(ellipse at 50% 20%, rgba(20,12,60,0.95) 0%, rgba(10,8,30,0.92) 50%, rgba(5,5,18,0.90) 100%);
            border: 1px solid rgba(245,200,66,0.22); border-radius: 28px;
            width: 100%; max-width: 480px; min-height: 320px; padding: 0 0 36px; overflow: hidden;
            box-shadow: 0 40px 100px rgba(0,0,0,0.5); font-family: 'Nunito', sans-serif; text-align: center;
        }
        #bk-admin-popup-overlay .bk-greeting-deco {
            background: radial-gradient(ellipse at 50% 0%, rgba(245,200,66,0.12) 0%, transparent 60%),
                        linear-gradient(180deg, rgba(107,60,200,0.3) 0%, transparent 100%);
            padding: 36px 20px 20px;
        }
        #bk-admin-popup-overlay .bk-greeting-moon {
            font-size: 3.5rem; line-height: 1;
            filter: drop-shadow(0 0 24px rgba(245,200,66,0.6));
        }
        #bk-admin-popup-overlay .bk-greeting-bismillah {
            font-size: 0.9rem; color: rgba(255,235,180,0.35);
            letter-spacing: 0.06em; padding: 12px 24px 0; direction: rtl;
        }
        #bk-admin-popup-overlay .bk-greeting-kareem {
            font-size: 1.8rem; font-weight: 700; color: #F5C842;
            padding: 6px 32px 0; margin-bottom: 10px;
            text-shadow: 0 2px 20px rgba(245,200,66,0.5);
        }
        #bk-admin-popup-overlay .bk-greeting-title {
            font-size: 1rem; font-weight: 700; color: rgba(255,235,180,0.95);
            padding: 0 24px; line-height: 1.5; margin-bottom: 8px;
        }
        #bk-admin-popup-overlay .bk-greeting-msg {
            font-size: 0.84rem; color: rgba(255,235,180,0.52);
            padding: 0 28px; line-height: 1.6;
        }
        #bk-admin-popup-overlay .bk-greeting-border-art {
            margin: 20px auto 0; width: 60%; height: 2px;
            background: linear-gradient(90deg, transparent 0%, rgba(245,200,66,0.3) 30%, rgba(245,200,66,0.5) 50%, rgba(245,200,66,0.3) 70%, transparent 100%);
        }
        #bk-admin-popup-overlay .bk-popup-close {
            position: absolute; top: 10px; right: 14px;
            color: rgba(255,235,180,0.4); background: none; border: none;
            font-size: 1.4rem; cursor: pointer; line-height: 1;
        }
        #bk-admin-popup-overlay .bk-popup-close:hover { color: rgba(255,235,180,0.8); }
        .bk-admin-confetti {
            position: fixed; top: -50px; user-select: none; pointer-events: none;
            z-index: 1000000; animation: bkAdminConfettiFall linear forwards;
        }
        @keyframes bkAdminConfettiFall { to { transform: translateY(110vh) rotate(720deg); } }
    </style>

    <div class="wrap bk-admin-wrap">

        <!-- Hero Banner -->
        <div class="bk-admin-hero">
            <div class="bk-admin-hero-icon">🌙</div>
            <div>
                <h1>Barakah — Ramadan Prayer Times</h1>
                <p>Place this shortcode anywhere on your site to display the prayer times widget.</p>
                <div class="bk-shortcode-box">[barakah]</div>
                &nbsp;
                <div class="bk-shortcode-box">[barakah city="London" country="UK"]</div>
            </div>
        </div>

        <div class="bk-notice" style="background:#f8f7ff;border-left:4px solid #6B8CDE;color:#1e1e3a;">
            <strong>Setup Wizard:</strong>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=barakah-onboarding' ) ); ?>">
                Run the setup wizard
            </a> to quickly configure your prayer times step by step.
        </div>

        <?php if ( $saved ) : ?>
            <div class="bk-notice bk-notice-success">✅ Settings saved successfully! The widget will now use the updated location.</div>
        <?php endif; ?>
        <?php if ( $error ) : ?>
            <div class="bk-notice bk-notice-error">⚠️ <?php echo esc_html( $error ); ?></div>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field( 'barakah_settings_nonce', 'barakah_nonce' ); ?>

            <!-- Location Settings -->
            <div class="bk-card-wrap">
                <h2><span class="dashicons dashicons-location"></span> Location Settings</h2>

                <div class="bk-field-row">
                    <div class="bk-field">
                        <label for="barakah_city">City</label>
                        <input
                            type="text"
                            id="barakah_city"
                            name="barakah_city"
                            value="<?php echo esc_attr( $city ); ?>"
                            placeholder="e.g. London, New York, Karachi"
                            required
                        />
                        <div class="description">Enter the city where prayer times should be calculated.</div>
                    </div>
                    <div class="bk-field">
                        <label for="barakah_country">Country</label>
                        <input
                            type="text"
                            id="barakah_country"
                            name="barakah_country"
                            value="<?php echo esc_attr( $country ); ?>"
                            placeholder="e.g. UK, USA, Pakistan"
                            required
                        />
                        <div class="description">Enter the country to help the API resolve the city.</div>
                    </div>
                </div>
            </div>

            <!-- Calculation Method -->
            <div class="bk-card-wrap">
                <h2><span class="dashicons dashicons-calculator"></span> Calculation Method</h2>

                <div class="bk-field">
                    <label for="barakah_method">Prayer Time Calculation Method</label>
                    <select id="barakah_method" name="barakah_method">
                        <?php foreach ( $methods as $val => $label ) : ?>
                            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $method, $val ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="description">Choose the jurisprudence-approved method for your region.</div>
                </div>

                <div class="bk-methods-info">
                    <p><strong>Quick guide by region:</strong></p>
                    <span class="bk-method-tag">South Asia → Method 1 or 5</span>
                    <span class="bk-method-tag">Middle East → Method 4 or 8</span>
                    <span class="bk-method-tag">North America → Method 2</span>
                    <span class="bk-method-tag">Europe → Method 1 or 12</span>
                    <span class="bk-method-tag">Turkey → Method 13</span>
                    <span class="bk-method-tag">Egypt → Method 3</span>
                </div>
            </div>

            <!-- Hijri Calendar Adjustment -->
            <div class="bk-card-wrap">
                <h2><span class="dashicons dashicons-calendar-alt"></span> Hijri Calendar Adjustment</h2>
                <div class="bk-field bk-field-full">
                    <label>Adjust Arabic calendar for my location</label>
                    <div class="description" style="margin-bottom:10px;">
                        In some regions (e.g. Bangladesh, parts of South Asia), the moon is sighted a day later than the standard calendar. Use this setting to adjust the Hijri day count accordingly.
                    </div>
                    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px;">
                        <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-weight:400;">
                            <input type="radio" name="barakah_hijri_adjust_direction" value="none" <?php checked( $hijri_adjust_dir, 'none' ); ?> />
                            No adjustment (use standard calendar)
                        </label>
                        <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-weight:400;">
                            <input type="radio" name="barakah_hijri_adjust_direction" value="before" <?php checked( $hijri_adjust_dir, 'before' ); ?> />
                            Day(s) before — moon sighted earlier in my region
                        </label>
                        <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-weight:400;">
                            <input type="radio" name="barakah_hijri_adjust_direction" value="after" <?php checked( $hijri_adjust_dir, 'after' ); ?> />
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
                        value="<?php echo esc_attr( $hijri_adjust_days ); ?>"
                        min="0"
                        max="3"
                        style="max-width: 120px;"
                    />
                    <div class="description">
                        How many days to shift the Hijri calendar. For Bangladesh, select "Day(s) after" with value 1.
                        Range: 0–3 days.
                    </div>
                </div>
            </div>

            <!-- Caution Time Adjustment -->
            <div class="bk-card-wrap">
                <h2><span class="dashicons dashicons-warning"></span> Caution Time for My Region</h2>
                <div class="description" style="margin-bottom:16px;">
                    Adjust Sehri and Iftar times to add a safety margin. Sehri time will be moved earlier and Iftar time will be moved later by the specified minutes.
                </div>
                <div class="bk-field-row">
                    <div class="bk-field">
                        <label for="barakah_sehri_caution_minutes">Sehri time adjustment (minutes)</label>
                        <input
                            type="number"
                            id="barakah_sehri_caution_minutes"
                            name="barakah_sehri_caution_minutes"
                            value="<?php echo esc_attr( $sehri_caution ); ?>"
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
                            value="<?php echo esc_attr( $iftar_caution ); ?>"
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

            <!-- Cache Settings -->
            <div class="bk-card-wrap">
                <h2><span class="dashicons dashicons-performance"></span> Cache Settings</h2>
                <div class="bk-field">
                    <label for="barakah_cache_hours">Cache Duration (hours)</label>
                    <input
                        type="number"
                        id="barakah_cache_hours"
                        name="barakah_cache_hours"
                        value="<?php echo esc_attr( $cache_hours ); ?>"
                        min="1"
                        max="168"
                        style="max-width: 160px;"
                    />
                    <div class="description">
                        Prayer times are cached server-side to reduce API calls.
                        Range: 1–168 hours (1 hour to 1 week). Default: 6 hours.
                        Cache is automatically cleared when settings are saved.
                    </div>
                </div>
            </div>

            <!-- Layout Settings -->
            <div class="bk-card-wrap">
                <h2><span class="dashicons dashicons-layout"></span> Layout Settings</h2>
                <div class="bk-field">
                    <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="barakah_two_column" value="1" <?php checked( $two_column, '1' ); ?> />
                        Show 2 Column Layout
                    </label>
                    <div class="description">
                        On large screens (&ge;768px), prayer times and duas display side by side.
                        Mobile devices always use single column.
                    </div>
                </div>
                <div class="bk-field" style="margin-top: 12px;">
                    <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="barakah_allow_location_change" value="1" <?php checked( $allow_location_change, '1' ); ?> />
                        Allow Location Change
                    </label>
                    <div class="description">
                        When enabled, visitors can click the location bar to change city and country.
                        Prayer times will be re-fetched for the new location.
                    </div>
                </div>
            </div>

            <!-- Greeting Messages -->
            <div class="bk-card-wrap">
                <h2><span class="dashicons dashicons-format-quote"></span> Greeting Messages</h2>
                <div class="bk-field">
                    <label for="barakah_header_greeting">Header Greeting</label>
                    <input
                        type="text"
                        id="barakah_header_greeting"
                        name="barakah_header_greeting"
                        value="<?php echo esc_attr( $header_greeting ); ?>"
                        placeholder="e.g. Welcome to Our Mosque - Ramadan Mubarak!"
                    />
                    <div class="description">
                        Shown below the Hijri date in the widget header. Leave empty to hide.
                    </div>
                </div>
                <div class="bk-field" style="margin-top: 12px;">
                    <label for="barakah_greeting">Footer Greeting</label>
                    <input
                        type="text"
                        id="barakah_greeting"
                        name="barakah_greeting"
                        value="<?php echo esc_attr( $greeting ); ?>"
                        placeholder="e.g. Ramadan Mubarak from Our Mosque!"
                    />
                    <div class="description">
                        Shown below "Ramadan Kareem" in the widget footer. Leave empty to hide.
                    </div>
                </div>
            </div>

            <!-- Greeting Popup -->
            <div class="bk-card-wrap">
                <h2 style="justify-content:space-between;">
                    <span style="display:flex;align-items:center;gap:6px;"><span class="dashicons dashicons-megaphone"></span> Greeting Popup</span>
                    <button type="button" class="bk-btn-preview" onclick="bkAdminPreviewPopup()">🌙 Preview Popup</button>
                </h2>
                <div class="bk-field bk-field-full">
                    <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" id="barakah_greeting_popup_enable" name="barakah_greeting_popup" value="1" <?php checked( $greeting_popup, '1' ); ?> onchange="bkTogglePopupSettings(this.checked)" />
                        Enable Greeting Popup
                    </label>
                    <div class="description">
                        Shows a Ramadan celebration popup — once per hour per visitor. No shortcode needed.
                    </div>
                </div>
                <div id="bk-popup-settings" style="grid-column:span 2;display:grid;grid-template-columns:1fr 1fr;gap:4px 16px;align-items:start;<?php echo $greeting_popup !== '1' ? 'display:none;' : ''; ?>">
                <div class="bk-field bk-field-full" style="margin-top:0;">
                    <label>Show popup on</label>
                    <div style="display:flex;flex-direction:column;gap:6px;margin-top:4px;">
                        <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-weight:400;">
                            <input type="radio" name="barakah_greeting_popup_scope" value="all" <?php checked( $greeting_popup_scope, 'all' ); ?> onchange="bkTogglePagePicker(this.value)" />
                            All pages
                        </label>
                        <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-weight:400;">
                            <input type="radio" name="barakah_greeting_popup_scope" value="specific" <?php checked( $greeting_popup_scope, 'specific' ); ?> onchange="bkTogglePagePicker(this.value)" />
                            Specific pages only
                        </label>
                    </div>
                </div>
                <div class="bk-field bk-field-full" id="bk-page-picker" style="margin-top:10px;<?php echo $greeting_popup_scope === 'all' ? 'display:none;' : ''; ?>">
                    <label for="barakah_greeting_popup_page_ids">Select pages</label>
                    <select
                        name="barakah_greeting_popup_page_ids[]"
                        id="barakah_greeting_popup_page_ids"
                        multiple
                        size="5"
                        style="width:100%;max-width:380px;border:1px solid #ddd;border-radius:6px;font-size:0.84rem;background:#fafafa;padding:4px;"
                    >
                        <?php foreach ( $all_pages as $page ) : ?>
                        <option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( in_array( $page->ID, $selected_page_ids, true ) ); ?>>
                            <?php echo esc_html( $page->post_title ); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="description">Hold Ctrl / Cmd to select multiple pages.</div>
                </div>
                <div class="bk-field" style="margin-top:12px;">
                    <label for="barakah_greeting_popup_title">Popup Title</label>
                    <input
                        type="text"
                        id="barakah_greeting_popup_title"
                        name="barakah_greeting_popup_title"
                        value="<?php echo esc_attr( $greeting_popup_title ); ?>"
                        placeholder="رمضان مبارك · Ramadan Mubarak 🌙"
                    />
                    <div class="description">Main heading shown in the popup. Supports Arabic and emoji.</div>
                </div>
                <div class="bk-field" style="margin-top:12px;">
                    <label for="barakah_greeting_popup_msg">Popup Message</label>
                    <textarea
                        id="barakah_greeting_popup_msg"
                        name="barakah_greeting_popup_msg"
                        rows="3"
                        style="width:100%;max-width:420px;padding:6px 10px;border:1px solid #ddd;border-radius:6px;font-size:0.84rem;background:#fafafa;font-family:inherit;resize:vertical;"
                        placeholder="Wishing you and your family a blessed month of Ramadan!"
                    ><?php echo esc_textarea( $greeting_popup_msg ); ?></textarea>
                    <div class="description">Supporting message below the title. Leave empty to hide.</div>
                </div>
                </div><!-- /#bk-popup-settings -->
            </div>
            <script>
            function bkTogglePopupSettings(enabled) {
                var el = document.getElementById('bk-popup-settings');
                if (!el) return;
                el.style.display = enabled ? 'grid' : 'none';
            }
            function bkTogglePagePicker(val) {
                var el = document.getElementById('bk-page-picker');
                if (el) el.style.display = (val === 'specific') ? '' : 'none';
            }

            function bkAdminPreviewPopup() {
                var existing = document.getElementById('bk-admin-popup-overlay');
                if (existing) existing.remove();
                document.querySelectorAll('.bk-admin-confetti').forEach(function(el){ el.remove(); });

                var title = (document.getElementById('barakah_greeting_popup_title') || {}).value || 'رمضان كريم';
                var msg   = (document.getElementById('barakah_greeting_popup_msg')   || {}).value || '';

                function esc(s) {
                    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
                }

                var overlay = document.createElement('div');
                overlay.id = 'bk-admin-popup-overlay';
                overlay.innerHTML =
                    '<div class="bk-greeting-panel">' +
                        '<button class="bk-popup-close" onclick="bkAdminClosePopup()">&#x2715;</button>' +
                        '<div class="bk-greeting-deco"><div class="bk-greeting-moon">🌙</div></div>' +
                        '<div class="bk-greeting-bismillah">بِسمِ اللّهِ الرَّحمٰنِ الرَّحِيمِ</div>' +
                        '<div class="bk-greeting-kareem">رمضان كريم</div>' +
                        '<div class="bk-greeting-title">' + esc(title) + '</div>' +
                        (msg ? '<div class="bk-greeting-msg">' + esc(msg) + '</div>' : '') +
                        '<div class="bk-greeting-border-art"></div>' +
                    '</div>';

                overlay.addEventListener('click', function(e){ if (e.target === overlay) bkAdminClosePopup(); });
                document.body.appendChild(overlay);

                /* Confetti */
                var shapes = ['🌙','⭐','✨','✦'], colors = ['#FFD700','#F8F8FF','#E6E6FA','#20B2AA'];
                var confettiInterval = setInterval(function() {
                    var el = document.createElement('div');
                    el.className = 'bk-admin-confetti';
                    el.textContent = shapes[Math.floor(Math.random() * shapes.length)];
                    el.style.color = colors[Math.floor(Math.random() * colors.length)];
                    el.style.left = (Math.random() * 100) + 'vw';
                    el.style.fontSize = (Math.random() * 18 + 14) + 'px';
                    var dur = (Math.random() * 3 + 3);
                    el.style.animationDuration = dur + 's';
                    document.body.appendChild(el);
                    setTimeout(function(){ if (el.parentNode) el.parentNode.removeChild(el); }, dur * 1000 + 200);
                }, 150);

                overlay._confettiInterval = confettiInterval;
                setTimeout(function(){ bkAdminClosePopup(); }, 6000);
            }

            function bkAdminClosePopup() {
                var overlay = document.getElementById('bk-admin-popup-overlay');
                if (overlay) {
                    clearInterval(overlay._confettiInterval);
                    overlay.remove();
                }
                document.querySelectorAll('.bk-admin-confetti').forEach(function(el){ el.remove(); });
            }
            </script>

            <!-- Shortcode Usage -->
            <div class="bk-card-wrap">
                <h2><span class="dashicons dashicons-editor-code"></span> Shortcode Usage</h2>
                <p style="color:#555;font-size:0.88rem;margin:0 0 8px">You can use the global settings above, or override per-instance with inline attributes:</p>
                <div class="bk-preview-code">
                    <span class="comment">&lt;!-- Global settings from admin --&gt;</span><br>
                    [barakah]<br><br>
                    <span class="comment">&lt;!-- Override city &amp; country --&gt;</span><br>
                    [barakah city="London" country="UK"]<br><br>
                    <span class="comment">&lt;!-- Override everything --&gt;</span><br>
                    [barakah city="Istanbul" country="Turkey" method="13"]
                </div>
            </div>

            <!-- Save -->
            <button type="submit" name="barakah_save" class="bk-save-btn">
                🌙 Save Settings
            </button>
        </form>

    </div>
    <?php
}
