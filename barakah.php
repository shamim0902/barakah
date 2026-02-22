<?php
/**
 * Plugin Name:  Barakah – Ramadan Prayer Times
 * Plugin URI:   https://github.com/your-repo/barakah
 * Description:  A beautiful Ramadan prayer-times widget. Use [barakah] on any page or post.
 * Version:      1.0.5
 * Author:       Barakah Team
 * License:      GPL-2.0+
 * Text Domain:  barakah
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BARAKAH_VERSION',     '1.0.5' );
define( 'BARAKAH_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'BARAKAH_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/* ── Includes ────────────────────────────────────────────────────────────── */

require_once BARAKAH_PLUGIN_PATH . 'includes/class-barakah-api.php';
require_once BARAKAH_PLUGIN_PATH . 'includes/admin.php';
require_once BARAKAH_PLUGIN_PATH . 'includes/shortcode.php';

/* ── Assets ──────────────────────────────────────────────────────────────── */

function barakah_enqueue_frontend_assets() {
    wp_enqueue_style(
        'barakah-style',
        BARAKAH_PLUGIN_URL . 'assets/css/barakah.css',
        [],
        BARAKAH_VERSION
    );

    wp_enqueue_script(
        'barakah-script',
        BARAKAH_PLUGIN_URL . 'assets/js/barakah.js',
        [],
        BARAKAH_VERSION,
        true   // load in footer
    );

    // Global greeting popup config — available on every page
    wp_localize_script( 'barakah-script', 'barakahGreetingConfig', [
        'enabled' => get_option( 'barakah_greeting_popup', '0' ),
        'title'   => get_option( 'barakah_greeting_popup_title', 'رمضان مبارك · Ramadan Mubarak 🌙' ),
        'msg'     => get_option( 'barakah_greeting_popup_msg',   'Wishing you and your family a blessed month of Ramadan!' ),
    ] );
}
add_action( 'wp_enqueue_scripts', 'barakah_enqueue_frontend_assets' );

/* ── Activation defaults ─────────────────────────────────────────────────── */

register_activation_hook( __FILE__, 'barakah_activate' );
function barakah_activate() {
    $defaults = [
        'barakah_city'                 => 'Dhaka',
        'barakah_country'              => 'Bangladesh',
        'barakah_method'               => '1',
        'barakah_cache_hours'          => 6,
        'barakah_hijri_adjust_direction'  => 'none',
        'barakah_hijri_adjust_days'      => 0,
        'barakah_sehri_caution_minutes'  => 0,
        'barakah_iftar_caution_minutes'  => 0,
    ];
    foreach ( $defaults as $key => $value ) {
        if ( get_option( $key ) === false ) {
            add_option( $key, $value );
        }
    }
}

/* ── Deactivation: flush cached data ────────────────────────────────────── */

register_deactivation_hook( __FILE__, 'barakah_deactivate' );
function barakah_deactivate() {
    Barakah_API::flush_cache();
}

/* ── Flush stale cache on version upgrade ──────────────────────────────── */

function barakah_maybe_flush_on_upgrade() {
    if ( get_option( 'barakah_db_version' ) !== BARAKAH_VERSION ) {
        Barakah_API::flush_cache();
        update_option( 'barakah_db_version', BARAKAH_VERSION );
    }
}
add_action( 'init', 'barakah_maybe_flush_on_upgrade' );

/* ── Flush cache when settings change ───────────────────────────────────── */

function barakah_flush_cache_on_save() {
    Barakah_API::flush_cache();
}
add_action( 'update_option_barakah_city',        'barakah_flush_cache_on_save' );
add_action( 'update_option_barakah_country',     'barakah_flush_cache_on_save' );
add_action( 'update_option_barakah_method',      'barakah_flush_cache_on_save' );
add_action( 'update_option_barakah_cache_hours', 'barakah_flush_cache_on_save' );
