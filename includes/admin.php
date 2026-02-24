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

add_action( 'admin_enqueue_scripts', 'barakah_admin_enqueue_assets' );
function barakah_admin_enqueue_assets( $hook_suffix ) {
    if ( 'toplevel_page_barakah-settings' !== $hook_suffix ) {
        return;
    }

    wp_enqueue_style(
        'barakah-admin-style',
        BARAKAH_PLUGIN_URL . 'assets/css/barakah-admin.css',
        [],
        BARAKAH_VERSION
    );

    wp_enqueue_script(
        'barakah-admin-script',
        BARAKAH_PLUGIN_URL . 'assets/js/barakah-admin.js',
        [],
        BARAKAH_VERSION,
        true
    );

    wp_localize_script(
        'barakah-admin-script',
        'barakahAdmin',
        [
            'settingsUrl' => admin_url( 'admin.php?page=barakah-settings' ),
        ]
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

function barakah_admin_tabs() {
    return [
        'general'   => __( 'General', 'barakah' ),
        'calendar'  => __( 'Calendar & Timings', 'barakah' ),
        'widget'    => __( 'Widget Display', 'barakah' ),
        'popup'     => __( 'Greeting Popup', 'barakah' ),
        'sticky'    => __( 'Sticky Bar', 'barakah' ),
        'shortcode' => __( 'Shortcode', 'barakah' ),
    ];
}

function barakah_get_active_tab() {
    $tabs = barakah_admin_tabs();

    $raw_tab = '';
    if ( isset( $_POST['barakah_tab'] ) ) {
        $raw_tab = wp_unslash( $_POST['barakah_tab'] );
    } elseif ( isset( $_GET['tab'] ) ) {
        $raw_tab = wp_unslash( $_GET['tab'] );
    }

    $tab = sanitize_key( $raw_tab );

    if ( empty( $tab ) || ! isset( $tabs[ $tab ] ) ) {
        return 'general';
    }

    return $tab;
}

function barakah_tab_url( $tab ) {
    return add_query_arg(
        [
            'page' => 'barakah-settings',
            'tab'  => $tab,
        ],
        admin_url( 'admin.php' )
    );
}

/* ── Settings page ───────────────────────────────────────────────────────── */

function barakah_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $active_tab = barakah_get_active_tab();
    $saved      = false;
    $error      = '';

    if ( isset( $_POST['barakah_save'] ) ) {
        if ( ! check_admin_referer( 'barakah_settings_nonce', 'barakah_nonce' ) ) {
            $error = 'Security check failed.';
        } else {
            $city    = sanitize_text_field( wp_unslash( $_POST['barakah_city'] ?? '' ) );
            $country = sanitize_text_field( wp_unslash( $_POST['barakah_country'] ?? '' ) );
            $method  = sanitize_text_field( wp_unslash( $_POST['barakah_method'] ?? '1' ) );

            if ( empty( $city ) || empty( $country ) ) {
                $error = 'City and Country cannot be empty.';
            } else {
                update_option( 'barakah_city', $city );
                update_option( 'barakah_country', $country );
                update_option( 'barakah_method', $method );

                $cache_hours = (int) sanitize_text_field( wp_unslash( $_POST['barakah_cache_hours'] ?? '2' ) );
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

                $hijri_adjust_dir = sanitize_text_field( wp_unslash( $_POST['barakah_hijri_adjust_direction'] ?? 'none' ) );
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

                $sticky_bar = isset( $_POST['barakah_sticky_bar'] ) ? '1' : '0';
                update_option( 'barakah_sticky_bar', $sticky_bar );

                $sticky_scope = sanitize_text_field( wp_unslash( $_POST['barakah_sticky_scope'] ?? 'all' ) );
                if ( ! in_array( $sticky_scope, [ 'all', 'specific' ], true ) ) {
                    $sticky_scope = 'all';
                }
                update_option( 'barakah_sticky_scope', $sticky_scope );

                $raw_sticky_ids = isset( $_POST['barakah_sticky_page_ids'] ) && is_array( $_POST['barakah_sticky_page_ids'] )
                    ? $_POST['barakah_sticky_page_ids']
                    : [];
                $clean_sticky_ids = array_map( 'absint', $raw_sticky_ids );
                $clean_sticky_ids = array_filter( $clean_sticky_ids );
                update_option( 'barakah_sticky_page_ids', implode( ',', $clean_sticky_ids ) );

                $sticky_pos = sanitize_text_field( wp_unslash( $_POST['barakah_sticky_position'] ?? 'footer' ) );
                if ( ! in_array( $sticky_pos, [ 'header', 'footer' ], true ) ) {
                    $sticky_pos = 'footer';
                }
                update_option( 'barakah_sticky_position', $sticky_pos );

                $sticky_greeting = sanitize_text_field( wp_unslash( $_POST['barakah_sticky_greeting'] ?? '' ) );
                update_option( 'barakah_sticky_greeting', $sticky_greeting );

                $sticky_theme = sanitize_text_field( wp_unslash( $_POST['barakah_sticky_theme'] ?? 'dark' ) );
                if ( ! in_array( $sticky_theme, [ 'dark', 'light' ], true ) ) {
                    $sticky_theme = 'dark';
                }
                update_option( 'barakah_sticky_theme', $sticky_theme );

                Barakah_API::flush_cache();
                $saved = true;
            }
        }
    }

    $tabs = barakah_admin_tabs();

    $city                  = get_option( 'barakah_city', 'Dhaka' );
    $country               = get_option( 'barakah_country', 'Bangladesh' );
    $method                = get_option( 'barakah_method', '1' );
    $cache_hours           = (int) get_option( 'barakah_cache_hours', 2 );
    $two_column            = get_option( 'barakah_two_column', '0' );
    $allow_location_change = get_option( 'barakah_allow_location_change', '0' );
    $header_greeting       = get_option( 'barakah_header_greeting', '' );
    $greeting              = get_option( 'barakah_greeting', '' );

    $greeting_popup          = get_option( 'barakah_greeting_popup', '0' );
    $greeting_popup_title    = get_option( 'barakah_greeting_popup_title', 'رمضان مبارك · Ramadan Mubarak 🌙' );
    $greeting_popup_msg      = get_option( 'barakah_greeting_popup_msg', 'Wishing you and your family a blessed month of Ramadan!' );
    $greeting_popup_scope    = get_option( 'barakah_greeting_popup_scope', 'all' );
    $greeting_popup_page_ids = get_option( 'barakah_greeting_popup_page_ids', '' );
    $selected_page_ids       = array_filter( array_map( 'absint', explode( ',', $greeting_popup_page_ids ) ) );
    $all_pages               = get_pages( [ 'sort_column' => 'post_title', 'sort_order' => 'ASC' ] );

    $hijri_adjust_dir  = get_option( 'barakah_hijri_adjust_direction', 'none' );
    $hijri_adjust_days = (int) get_option( 'barakah_hijri_adjust_days', 0 );
    $sehri_caution     = (int) get_option( 'barakah_sehri_caution_minutes', 0 );
    $iftar_caution     = (int) get_option( 'barakah_iftar_caution_minutes', 0 );

    $sticky_bar      = get_option( 'barakah_sticky_bar', '0' );
    $sticky_scope    = get_option( 'barakah_sticky_scope', 'all' );
    $sticky_page_ids = get_option( 'barakah_sticky_page_ids', '' );
    $selected_sticky_page_ids = array_filter( array_map( 'absint', explode( ',', $sticky_page_ids ) ) );
    $sticky_pos      = get_option( 'barakah_sticky_position', 'footer' );
    $sticky_greeting = get_option( 'barakah_sticky_greeting', 'Ramadan Mubarak! 🌙' );
    $sticky_theme    = get_option( 'barakah_sticky_theme', 'dark' );

    $methods = barakah_get_methods();
    ?>
    <div class="wrap bk-admin-wrap" data-active-tab="<?php echo esc_attr( $active_tab ); ?>">

        <div class="bk-admin-hero">
            <div class="bk-admin-hero-icon" aria-hidden="true">🌙</div>
            <div>
                <h1><?php esc_html_e( 'Barakah Settings', 'barakah' ); ?></h1>
                <p><?php esc_html_e( 'Configure prayer times, widget behavior, greeting popup, and sticky bar from one organized screen.', 'barakah' ); ?></p>
                <div class="bk-hero-shortcodes">
                    <code>[barakah]</code>
                    <code>[barakah city="London" country="UK"]</code>
                </div>
            </div>
        </div>

        <div class="bk-notice bk-notice-info">
            <strong><?php esc_html_e( 'Setup Wizard:', 'barakah' ); ?></strong>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=barakah-onboarding' ) ); ?>">
                <?php esc_html_e( 'Run the setup wizard', 'barakah' ); ?>
            </a>
            <?php esc_html_e( 'for guided first-time configuration.', 'barakah' ); ?>
        </div>

        <?php if ( $saved ) : ?>
            <div class="bk-notice bk-notice-success">
                <?php esc_html_e( 'Settings saved successfully.', 'barakah' ); ?>
            </div>
        <?php endif; ?>

        <?php if ( $error ) : ?>
            <div class="bk-notice bk-notice-error"><?php echo esc_html( $error ); ?></div>
        <?php endif; ?>

        <nav class="bk-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Barakah settings sections', 'barakah' ); ?>">
            <?php foreach ( $tabs as $tab_slug => $tab_label ) : ?>
                <?php $is_active = ( $tab_slug === $active_tab ); ?>
                <a
                    id="bk-tab-<?php echo esc_attr( $tab_slug ); ?>"
                    class="bk-tab<?php echo $is_active ? ' is-active' : ''; ?>"
                    href="<?php echo esc_url( barakah_tab_url( $tab_slug ) ); ?>"
                    role="tab"
                    aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                    aria-controls="bk-panel-<?php echo esc_attr( $tab_slug ); ?>"
                    tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
                    data-tab="<?php echo esc_attr( $tab_slug ); ?>"
                >
                    <?php echo esc_html( $tab_label ); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <form method="post" action="<?php echo esc_url( barakah_tab_url( $active_tab ) ); ?>" id="bk-admin-settings-form">
            <?php wp_nonce_field( 'barakah_settings_nonce', 'barakah_nonce' ); ?>
            <input type="hidden" name="barakah_tab" id="barakah_tab" value="<?php echo esc_attr( $active_tab ); ?>">

            <section
                id="bk-panel-general"
                class="bk-tab-panel<?php echo 'general' === $active_tab ? ' is-active' : ''; ?>"
                role="tabpanel"
                aria-labelledby="bk-tab-general"
                <?php echo 'general' === $active_tab ? '' : 'hidden'; ?>
            >
                <div class="bk-card-wrap">
                    <h2><span class="dashicons dashicons-location" aria-hidden="true"></span><?php esc_html_e( 'Location Settings', 'barakah' ); ?></h2>
                    <div class="bk-field-row">
                        <div class="bk-field">
                            <label for="barakah_city"><?php esc_html_e( 'City', 'barakah' ); ?></label>
                            <input type="text" id="barakah_city" name="barakah_city" value="<?php echo esc_attr( $city ); ?>" placeholder="e.g. London, New York" required>
                            <p class="description"><?php esc_html_e( 'City where prayer times should be calculated.', 'barakah' ); ?></p>
                        </div>
                        <div class="bk-field">
                            <label for="barakah_country"><?php esc_html_e( 'Country', 'barakah' ); ?></label>
                            <input type="text" id="barakah_country" name="barakah_country" value="<?php echo esc_attr( $country ); ?>" placeholder="e.g. UK, USA" required>
                            <p class="description"><?php esc_html_e( 'Country used to resolve the city in API requests.', 'barakah' ); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bk-card-wrap">
                    <h2><span class="dashicons dashicons-calculator" aria-hidden="true"></span><?php esc_html_e( 'Calculation Method', 'barakah' ); ?></h2>
                    <div class="bk-field">
                        <label for="barakah_method"><?php esc_html_e( 'Prayer Time Calculation Method', 'barakah' ); ?></label>
                        <select id="barakah_method" name="barakah_method">
                            <?php foreach ( $methods as $val => $label ) : ?>
                                <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $method, $val ); ?>><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="bk-methods-info">
                        <span class="bk-method-tag">South Asia -> Method 1 or 5</span>
                        <span class="bk-method-tag">Middle East -> Method 4 or 8</span>
                        <span class="bk-method-tag">North America -> Method 2</span>
                        <span class="bk-method-tag">Europe -> Method 1 or 12</span>
                        <span class="bk-method-tag">Turkey -> Method 13</span>
                        <span class="bk-method-tag">Egypt -> Method 3</span>
                    </div>
                </div>

                <div class="bk-card-wrap">
                    <h2><span class="dashicons dashicons-performance" aria-hidden="true"></span><?php esc_html_e( 'Cache Settings', 'barakah' ); ?></h2>
                    <div class="bk-field">
                        <label for="barakah_cache_hours"><?php esc_html_e( 'Cache Duration (hours)', 'barakah' ); ?></label>
                        <input type="number" id="barakah_cache_hours" name="barakah_cache_hours" value="<?php echo esc_attr( $cache_hours ); ?>" min="1" max="168">
                        <p class="description"><?php esc_html_e( 'Range: 1-168 hours. Cache clears automatically when settings are saved.', 'barakah' ); ?></p>
                    </div>
                </div>
            </section>

            <section
                id="bk-panel-calendar"
                class="bk-tab-panel<?php echo 'calendar' === $active_tab ? ' is-active' : ''; ?>"
                role="tabpanel"
                aria-labelledby="bk-tab-calendar"
                <?php echo 'calendar' === $active_tab ? '' : 'hidden'; ?>
            >
                <div class="bk-card-wrap">
                    <h2><span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span><?php esc_html_e( 'Hijri Calendar Adjustment', 'barakah' ); ?></h2>
                    <div class="bk-field">
                        <label><?php esc_html_e( 'Adjust Arabic calendar for my location', 'barakah' ); ?></label>
                        <label class="bk-inline-choice"><input type="radio" name="barakah_hijri_adjust_direction" value="none" <?php checked( $hijri_adjust_dir, 'none' ); ?>> <?php esc_html_e( 'No adjustment (standard calendar)', 'barakah' ); ?></label>
                        <label class="bk-inline-choice"><input type="radio" name="barakah_hijri_adjust_direction" value="before" <?php checked( $hijri_adjust_dir, 'before' ); ?>> <?php esc_html_e( 'Day(s) before', 'barakah' ); ?></label>
                        <label class="bk-inline-choice"><input type="radio" name="barakah_hijri_adjust_direction" value="after" <?php checked( $hijri_adjust_dir, 'after' ); ?>> <?php esc_html_e( 'Day(s) after', 'barakah' ); ?></label>
                    </div>
                    <div class="bk-field">
                        <label for="barakah_hijri_adjust_days"><?php esc_html_e( 'Number of days to adjust', 'barakah' ); ?></label>
                        <input type="number" id="barakah_hijri_adjust_days" name="barakah_hijri_adjust_days" value="<?php echo esc_attr( $hijri_adjust_days ); ?>" min="0" max="3">
                        <p class="description"><?php esc_html_e( 'Range: 0-3 days.', 'barakah' ); ?></p>
                    </div>
                </div>

                <div class="bk-card-wrap">
                    <h2><span class="dashicons dashicons-warning" aria-hidden="true"></span><?php esc_html_e( 'Caution Time Adjustment', 'barakah' ); ?></h2>
                    <div class="bk-field-row">
                        <div class="bk-field">
                            <label for="barakah_sehri_caution_minutes"><?php esc_html_e( 'Sehri adjustment (minutes)', 'barakah' ); ?></label>
                            <input type="number" id="barakah_sehri_caution_minutes" name="barakah_sehri_caution_minutes" value="<?php echo esc_attr( $sehri_caution ); ?>" min="0" max="60">
                            <p class="description"><?php esc_html_e( 'Subtracts from Fajr/Sehri time.', 'barakah' ); ?></p>
                        </div>
                        <div class="bk-field">
                            <label for="barakah_iftar_caution_minutes"><?php esc_html_e( 'Iftar adjustment (minutes)', 'barakah' ); ?></label>
                            <input type="number" id="barakah_iftar_caution_minutes" name="barakah_iftar_caution_minutes" value="<?php echo esc_attr( $iftar_caution ); ?>" min="0" max="60">
                            <p class="description"><?php esc_html_e( 'Adds to Maghrib/Iftar time.', 'barakah' ); ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <section
                id="bk-panel-widget"
                class="bk-tab-panel<?php echo 'widget' === $active_tab ? ' is-active' : ''; ?>"
                role="tabpanel"
                aria-labelledby="bk-tab-widget"
                <?php echo 'widget' === $active_tab ? '' : 'hidden'; ?>
            >
                <div class="bk-card-wrap">
                    <h2><span class="dashicons dashicons-layout" aria-hidden="true"></span><?php esc_html_e( 'Layout Settings', 'barakah' ); ?></h2>
                    <div class="bk-field">
                        <label class="bk-inline-check"><input type="checkbox" name="barakah_two_column" value="1" <?php checked( $two_column, '1' ); ?>> <?php esc_html_e( 'Show two-column layout on large screens', 'barakah' ); ?></label>
                    </div>
                    <div class="bk-field">
                        <label class="bk-inline-check"><input type="checkbox" name="barakah_allow_location_change" value="1" <?php checked( $allow_location_change, '1' ); ?>> <?php esc_html_e( 'Allow visitors to change location', 'barakah' ); ?></label>
                    </div>
                </div>

                <div class="bk-card-wrap">
                    <h2><span class="dashicons dashicons-format-quote" aria-hidden="true"></span><?php esc_html_e( 'Greeting Messages', 'barakah' ); ?></h2>
                    <div class="bk-field">
                        <label for="barakah_header_greeting"><?php esc_html_e( 'Header Greeting', 'barakah' ); ?></label>
                        <input type="text" id="barakah_header_greeting" name="barakah_header_greeting" value="<?php echo esc_attr( $header_greeting ); ?>" placeholder="e.g. Ramadan Mubarak">
                    </div>
                    <div class="bk-field">
                        <label for="barakah_greeting"><?php esc_html_e( 'Footer Greeting', 'barakah' ); ?></label>
                        <input type="text" id="barakah_greeting" name="barakah_greeting" value="<?php echo esc_attr( $greeting ); ?>" placeholder="e.g. Ramadan Kareem">
                    </div>
                </div>
            </section>

            <section
                id="bk-panel-popup"
                class="bk-tab-panel<?php echo 'popup' === $active_tab ? ' is-active' : ''; ?>"
                role="tabpanel"
                aria-labelledby="bk-tab-popup"
                <?php echo 'popup' === $active_tab ? '' : 'hidden'; ?>
            >
                <div class="bk-card-wrap">
                    <div class="bk-card-head-row">
                        <h2><span class="dashicons dashicons-megaphone" aria-hidden="true"></span><?php esc_html_e( 'Greeting Popup', 'barakah' ); ?></h2>
                        <button type="button" class="button button-secondary" id="bk-preview-popup-btn"><?php esc_html_e( 'Preview Popup', 'barakah' ); ?></button>
                    </div>

                    <div class="bk-field">
                        <label class="bk-inline-check"><input type="checkbox" id="barakah_greeting_popup_enable" name="barakah_greeting_popup" value="1" <?php checked( $greeting_popup, '1' ); ?>> <?php esc_html_e( 'Enable Greeting Popup', 'barakah' ); ?></label>
                    </div>

                    <div id="bk-popup-settings" <?php echo '1' === $greeting_popup ? '' : 'hidden'; ?>>
                        <div class="bk-field">
                            <label><?php esc_html_e( 'Show popup on', 'barakah' ); ?></label>
                            <label class="bk-inline-choice"><input type="radio" name="barakah_greeting_popup_scope" value="all" <?php checked( $greeting_popup_scope, 'all' ); ?>> <?php esc_html_e( 'All pages', 'barakah' ); ?></label>
                            <label class="bk-inline-choice"><input type="radio" name="barakah_greeting_popup_scope" value="specific" <?php checked( $greeting_popup_scope, 'specific' ); ?>> <?php esc_html_e( 'Specific pages only', 'barakah' ); ?></label>
                        </div>

                        <div class="bk-field" id="bk-page-picker" <?php echo 'specific' === $greeting_popup_scope ? '' : 'hidden'; ?>>
                            <label for="barakah_greeting_popup_page_ids"><?php esc_html_e( 'Select pages', 'barakah' ); ?></label>
                            <select name="barakah_greeting_popup_page_ids[]" id="barakah_greeting_popup_page_ids" multiple size="8">
                                <?php foreach ( $all_pages as $page ) : ?>
                                    <option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( in_array( $page->ID, $selected_page_ids, true ) ); ?>>
                                        <?php echo esc_html( $page->post_title ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e( 'Hold Ctrl (Windows) or Cmd (Mac) to select multiple pages.', 'barakah' ); ?></p>
                        </div>

                        <div class="bk-field">
                            <label for="barakah_greeting_popup_title"><?php esc_html_e( 'Popup Title', 'barakah' ); ?></label>
                            <input type="text" id="barakah_greeting_popup_title" name="barakah_greeting_popup_title" value="<?php echo esc_attr( $greeting_popup_title ); ?>" placeholder="Ramadan Mubarak">
                        </div>

                        <div class="bk-field">
                            <label for="barakah_greeting_popup_msg"><?php esc_html_e( 'Popup Message', 'barakah' ); ?></label>
                            <textarea id="barakah_greeting_popup_msg" name="barakah_greeting_popup_msg" rows="4" placeholder="Wishing you and your family a blessed Ramadan."><?php echo esc_textarea( $greeting_popup_msg ); ?></textarea>
                        </div>
                    </div>
                </div>
            </section>

            <section
                id="bk-panel-sticky"
                class="bk-tab-panel<?php echo 'sticky' === $active_tab ? ' is-active' : ''; ?>"
                role="tabpanel"
                aria-labelledby="bk-tab-sticky"
                <?php echo 'sticky' === $active_tab ? '' : 'hidden'; ?>
            >
                <div class="bk-card-wrap">
                    <h2><span class="dashicons dashicons-minus" aria-hidden="true"></span><?php esc_html_e( 'Sticky Prayer Bar', 'barakah' ); ?></h2>
                    <div class="bk-field">
                        <label class="bk-inline-check"><input type="checkbox" id="barakah_sticky_bar_enable" name="barakah_sticky_bar" value="1" <?php checked( $sticky_bar, '1' ); ?>> <?php esc_html_e( 'Enable Sticky Prayer Bar', 'barakah' ); ?></label>
                    </div>

                    <div id="bk-sticky-settings" <?php echo '1' === $sticky_bar ? '' : 'hidden'; ?>>
                        <div class="bk-field">
                            <label><?php esc_html_e( 'Show sticky bar on', 'barakah' ); ?></label>
                            <label class="bk-inline-choice"><input type="radio" name="barakah_sticky_scope" value="all" <?php checked( $sticky_scope, 'all' ); ?>> <?php esc_html_e( 'All pages', 'barakah' ); ?></label>
                            <label class="bk-inline-choice"><input type="radio" name="barakah_sticky_scope" value="specific" <?php checked( $sticky_scope, 'specific' ); ?>> <?php esc_html_e( 'Specific pages only', 'barakah' ); ?></label>
                        </div>

                        <div class="bk-field" id="bk-sticky-page-picker" <?php echo 'specific' === $sticky_scope ? '' : 'hidden'; ?>>
                            <label for="barakah_sticky_page_ids"><?php esc_html_e( 'Select pages', 'barakah' ); ?></label>
                            <select name="barakah_sticky_page_ids[]" id="barakah_sticky_page_ids" multiple size="8">
                                <?php foreach ( $all_pages as $page ) : ?>
                                    <option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( in_array( $page->ID, $selected_sticky_page_ids, true ) ); ?>>
                                        <?php echo esc_html( $page->post_title ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e( 'Hold Ctrl (Windows) or Cmd (Mac) to select multiple pages.', 'barakah' ); ?></p>
                        </div>

                        <div class="bk-field">
                            <label><?php esc_html_e( 'Bar Position', 'barakah' ); ?></label>
                            <label class="bk-inline-choice"><input type="radio" name="barakah_sticky_position" value="footer" <?php checked( $sticky_pos, 'footer' ); ?>> <?php esc_html_e( 'Footer', 'barakah' ); ?></label>
                            <label class="bk-inline-choice"><input type="radio" name="barakah_sticky_position" value="header" <?php checked( $sticky_pos, 'header' ); ?>> <?php esc_html_e( 'Header', 'barakah' ); ?></label>
                        </div>

                        <div class="bk-field">
                            <label for="barakah_sticky_greeting"><?php esc_html_e( 'Greeting Text', 'barakah' ); ?></label>
                            <input type="text" id="barakah_sticky_greeting" name="barakah_sticky_greeting" value="<?php echo esc_attr( $sticky_greeting ); ?>" placeholder="Ramadan Mubarak!">
                        </div>

                        <div class="bk-field">
                            <label><?php esc_html_e( 'Bar Design', 'barakah' ); ?></label>
                            <label class="bk-inline-choice"><input type="radio" name="barakah_sticky_theme" value="dark" <?php checked( $sticky_theme, 'dark' ); ?>> <?php esc_html_e( 'Dark', 'barakah' ); ?></label>
                            <label class="bk-inline-choice"><input type="radio" name="barakah_sticky_theme" value="light" <?php checked( $sticky_theme, 'light' ); ?>> <?php esc_html_e( 'Light', 'barakah' ); ?></label>
                        </div>
                    </div>
                </div>
            </section>

            <section
                id="bk-panel-shortcode"
                class="bk-tab-panel<?php echo 'shortcode' === $active_tab ? ' is-active' : ''; ?>"
                role="tabpanel"
                aria-labelledby="bk-tab-shortcode"
                <?php echo 'shortcode' === $active_tab ? '' : 'hidden'; ?>
            >
                <div class="bk-card-wrap">
                    <h2><span class="dashicons dashicons-editor-code" aria-hidden="true"></span><?php esc_html_e( 'Shortcode Usage', 'barakah' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Use global settings or override per shortcode instance.', 'barakah' ); ?></p>
                    <pre class="bk-preview-code">[barakah]

[barakah city="London" country="UK"]

[barakah city="Istanbul" country="Turkey" method="13"]</pre>
                </div>
            </section>

            <div class="bk-submit-row">
                <button type="submit" name="barakah_save" class="button button-primary bk-save-btn"><?php esc_html_e( 'Save Settings', 'barakah' ); ?></button>
            </div>
        </form>

    </div>
    <?php
}
