<?php
/**
 * Plugin Name:  Barakah – Ramadan Prayer Times
 * Plugin URI:   https://github.com/your-repo/barakah
 * Description:  A beautiful Ramadan prayer-times widget. Use [barakah] on any page or post.
 * Version:      1.0.0
 * Author:       Barakah Team
 * License:      GPL-2.0+
 * Text Domain:  barakah
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BARAKAH_VERSION',     '1.0.0' );
define( 'BARAKAH_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'BARAKAH_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/* ── Includes ────────────────────────────────────────────────────────────── */

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
}
add_action( 'wp_enqueue_scripts', 'barakah_enqueue_frontend_assets' );

/* ── Activation defaults ─────────────────────────────────────────────────── */

register_activation_hook( __FILE__, 'barakah_activate' );
function barakah_activate() {
    $defaults = [
        'barakah_city'    => 'Dhaka',
        'barakah_country' => 'Bangladesh',
        'barakah_method'  => '1',
    ];
    foreach ( $defaults as $key => $value ) {
        if ( get_option( $key ) === false ) {
            add_option( $key, $value );
        }
    }
}
