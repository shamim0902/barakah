<?php
/**
 * Plugin Name: Barakah
 * Plugin URI: https://github.com/shamim0902/barakah
 * Description: Islamic Prayer Times & Insights - Sehri/Iftar times, Namaz times, daily Quran Ayah, Duas. for WordPress Shortcode-based, mobile-friendly.
 * Version: 1.0.0
 * Author: Barakah
 * Author URI: https://github.com/barakah-wp
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: barakah
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BARAKAH_VERSION', '1.0.0' );
define( 'BARAKAH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BARAKAH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Activation: set default options.
 */
function barakah_activate() {
	$defaults = array(
		'barakah_city'           => 'Dhaka',
		'barakah_country'        => 'Bangladesh',
		'barakah_method'         => 1,
		'barakah_show_ramadan'   => true,
		'barakah_cache_hours'    => 6,
		'barakah_show_dua'       => true,
		'barakah_show_ayah'      => true,
	);
	foreach ( $defaults as $key => $value ) {
		if ( get_option( $key ) === false ) {
			add_option( $key, $value );
		}
	}
}

/**
 * Deactivation: clean transients.
 */
function barakah_deactivate() {
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_barakah_%' OR option_name LIKE '_transient_timeout_barakah_%'" );
}

register_activation_hook( __FILE__, 'barakah_activate' );
register_deactivation_hook( __FILE__, 'barakah_deactivate' );

/**
 * Load plugin classes.
 */
function barakah_load_includes() {
	require_once BARAKAH_PLUGIN_DIR . 'includes/class-barakah-api.php';
	require_once BARAKAH_PLUGIN_DIR . 'includes/class-barakah-shortcodes.php';
	require_once BARAKAH_PLUGIN_DIR . 'includes/class-barakah-admin.php';
	require_once BARAKAH_PLUGIN_DIR . 'includes/class-barakah-ajax.php';
}

add_action( 'plugins_loaded', 'barakah_load_includes' );

/**
 * Register shortcodes.
 */
function barakah_register_shortcodes() {
	$shortcodes = Barakah_Shortcodes::get_instance();
	add_shortcode( 'barakah', array( $shortcodes, 'full_widget' ) );
	add_shortcode( 'barakah_prayer_times', array( $shortcodes, 'prayer_times' ) );
	add_shortcode( 'barakah_ramadan', array( $shortcodes, 'ramadan' ) );
	add_shortcode( 'barakah_ayah', array( $shortcodes, 'ayah' ) );
	add_shortcode( 'barakah_dua', array( $shortcodes, 'dua' ) );
}

add_action( 'init', 'barakah_register_shortcodes' );

/**
 * Enqueue frontend assets (only when shortcode is present).
 */
function barakah_enqueue_assets() {
	global $post;
	if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'barakah' ) && ! has_shortcode( $post->post_content, 'barakah_prayer_times' ) && ! has_shortcode( $post->post_content, 'barakah_ramadan' ) && ! has_shortcode( $post->post_content, 'barakah_ayah' ) && ! has_shortcode( $post->post_content, 'barakah_dua' ) ) {
		return;
	}

	wp_enqueue_style(
		'barakah-style',
		BARAKAH_PLUGIN_URL . 'assets/css/barakah-style.css',
		array(),
		BARAKAH_VERSION
	);

	wp_enqueue_script(
		'barakah-script',
		BARAKAH_PLUGIN_URL . 'assets/js/barakah-script.js',
		array(),
		BARAKAH_VERSION,
		true
	);

	$api   = Barakah_API::get_instance();
	$city  = get_option( 'barakah_city', 'Dhaka' );
	$country = get_option( 'barakah_country', 'Bangladesh' );
	$method = (int) get_option( 'barakah_method', 1 );
	$today = gmdate( 'd-m-Y' );
	$data  = $api->get_prayer_times( $city, $country, $today, $method );

	wp_localize_script( 'barakah-script', 'barakahConfig', array(
		'ajaxurl'   => admin_url( 'admin-ajax.php' ),
		'nonce'     => wp_create_nonce( 'barakah_ajax' ),
		'city'      => $city,
		'country'   => $country,
		'method'    => $method,
		'timings'   => isset( $data['timings'] ) ? $data['timings'] : array(),
		'date'      => isset( $data['date'] ) ? $data['date'] : array(),
		'today'     => $today,
		'timezone'  => wp_timezone_string(),
	) );
}

add_action( 'wp_enqueue_scripts', 'barakah_enqueue_assets' );

/**
 * Admin: register settings page.
 */
function barakah_admin_menu() {
	add_options_page(
		__( 'Barakah Settings', 'barakah' ),
		__( 'Barakah', 'barakah' ),
		'manage_options',
		'barakah',
		array( Barakah_Admin::get_instance(), 'render_settings_page' )
	);
}

add_action( 'admin_menu', 'barakah_admin_menu' );

/**
 * Admin: register settings.
 */
function barakah_register_settings() {
	Barakah_Admin::get_instance()->register_settings();
}

add_action( 'admin_init', 'barakah_register_settings' );

/**
 * Flush Barakah cache when any location/cache setting is updated.
 */
function barakah_flush_cache_on_save() {
	if ( class_exists( 'Barakah_API' ) ) {
		Barakah_API::flush_cache();
	}
}
add_action( 'update_option_barakah_city', 'barakah_flush_cache_on_save' );
add_action( 'update_option_barakah_country', 'barakah_flush_cache_on_save' );
add_action( 'update_option_barakah_method', 'barakah_flush_cache_on_save' );
add_action( 'update_option_barakah_cache_hours', 'barakah_flush_cache_on_save' );

/**
 * AJAX handlers (registered inside Barakah_Ajax).
 */
add_action( 'init', function() {
	if ( class_exists( 'Barakah_Ajax' ) ) {
		Barakah_Ajax::get_instance()->register();
	}
}, 20 );
