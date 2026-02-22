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
                $saved = true;
            }
        }
    }

    $city    = get_option( 'barakah_city',    'Dhaka' );
    $country = get_option( 'barakah_country', 'Bangladesh' );
    $method  = get_option( 'barakah_method',  '1' );
    $methods = barakah_get_methods();
    ?>
    <style>
        /* ── Barakah Admin Styles ── */
        .bk-admin-wrap {
            max-width: 820px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .bk-admin-hero {
            background: linear-gradient(135deg, #0d0d2b 0%, #1a0a3e 100%);
            border-radius: 14px;
            padding: 28px 32px;
            margin: 20px 0 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            border: 1px solid rgba(245,200,66,0.25);
            box-shadow: 0 4px 32px rgba(0,0,0,0.35);
        }
        .bk-admin-hero-icon { font-size: 3rem; line-height: 1; }
        .bk-admin-hero h1 {
            color: #F5C842;
            font-size: 1.6rem;
            font-weight: 700;
            margin: 0 0 4px;
            padding: 0;
        }
        .bk-admin-hero p {
            color: rgba(255,235,180,0.65);
            margin: 0;
            font-size: 0.9rem;
        }
        .bk-shortcode-box {
            background: rgba(245,200,66,0.12);
            border: 1px solid rgba(245,200,66,0.35);
            border-radius: 8px;
            padding: 3px 12px;
            color: #F5C842;
            font-family: monospace;
            font-size: 1rem;
            display: inline-block;
            margin-top: 8px;
        }
        .bk-card-wrap {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 24px 28px;
            margin-bottom: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .bk-card-wrap h2 {
            font-size: 1rem;
            font-weight: 600;
            color: #1e1e3a;
            margin: 0 0 18px;
            padding: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 2px solid #f0eefc;
            padding-bottom: 10px;
        }
        .bk-card-wrap h2 .dashicons { color: #6B8CDE; }
        .bk-field { margin-bottom: 18px; }
        .bk-field label {
            display: block;
            font-weight: 600;
            font-size: 0.85rem;
            color: #333;
            margin-bottom: 6px;
        }
        .bk-field input[type="text"],
        .bk-field select {
            width: 100%;
            max-width: 420px;
            padding: 9px 13px;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.2s;
            outline: none;
            background: #fafafa;
        }
        .bk-field input[type="text"]:focus,
        .bk-field select:focus {
            border-color: #6B8CDE;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(107,140,222,0.12);
        }
        .bk-field .description {
            font-size: 0.78rem;
            color: #888;
            margin-top: 5px;
        }
        .bk-field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 600px) { .bk-field-row { grid-template-columns: 1fr; } }
        .bk-save-btn {
            background: linear-gradient(135deg, #2c1a6e, #1a0a3e);
            color: #F5C842;
            border: 1px solid rgba(245,200,66,0.4);
            border-radius: 8px;
            padding: 10px 28px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .bk-save-btn:hover {
            background: linear-gradient(135deg, #3a2480, #250f52);
            box-shadow: 0 4px 16px rgba(107,140,222,0.3);
        }
        .bk-notice {
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            font-weight: 600;
            font-size: 0.88rem;
        }
        .bk-notice-success { background: #edfaf1; border-left: 4px solid #2ecc71; color: #1a6e3a; }
        .bk-notice-error   { background: #fef0f0; border-left: 4px solid #e74c3c; color: #7a1a1a; }
        .bk-methods-info {
            background: #f8f7ff;
            border: 1px solid #e0dcff;
            border-radius: 8px;
            padding: 12px 16px;
            margin-top: 8px;
        }
        .bk-methods-info p { margin: 0 0 6px; font-size: 0.8rem; color: #555; }
        .bk-method-tag {
            display: inline-block;
            background: rgba(107,140,222,0.12);
            border: 1px solid rgba(107,140,222,0.25);
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 0.72rem;
            color: #4a6abf;
            margin: 2px;
        }
        .bk-preview-code {
            background: #1e1e3a;
            color: #F5C842;
            border-radius: 8px;
            padding: 14px 18px;
            font-family: monospace;
            font-size: 0.85rem;
            line-height: 1.7;
            margin-top: 8px;
        }
        .bk-preview-code .comment { color: rgba(255,255,255,0.35); }
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
