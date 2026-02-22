<?php
/**
 * Barakah API handler – AlAdhan prayer times with transient caching.
 *
 * @package Barakah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Barakah_API {

	/**
	 * Singleton instance.
	 *
	 * @var Barakah_API|null
	 */
	private static $instance = null;

	/**
	 * AlAdhan base URL.
	 */
	const ALADHAN_BASE = 'https://api.aladhan.com/v1';

	/**
	 * Get singleton instance.
	 *
	 * @return Barakah_API
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get prayer times for a city and date (cached via transients).
	 *
	 * @param string $city    City name.
	 * @param string $country Country code or name.
	 * @param string $date    Date in d-m-Y format. Empty for today.
	 * @param int    $method  Calculation method (1–15).
	 * @return array{ timings: array, date: array, error?: string }
	 */
	public function get_prayer_times( $city, $country, $date = '', $method = 1 ) {
		$today = gmdate( 'Y-m-d' );

		$cache_hours = (int) get_option( 'barakah_cache_hours', 6 );
		$cache_key   = 'barakah_times_v2_' . sanitize_key( $city . '_' . $country . '_' . $today . '_' . $method );
		$cached      = get_transient( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		/* No date in path — Aladhan uses the server's current date automatically */
		$url = add_query_arg(
			array(
				'city'    => $city,
				'country' => $country,
				'method'  => $method,
			),
			self::ALADHAN_BASE . '/timingsByCity'
		);

		$response = wp_remote_get( $url, array(
			'timeout'   => 15,
			'sslverify' => true,
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'timings' => array(),
				'date'    => array(),
				'error'   => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$json = json_decode( $body, true );

		if ( 200 !== $code || empty( $json['data'] ) ) {
			return array(
				'timings' => array(),
				'date'    => array(),
				'error'   => 'Could not fetch prayer times.',
			);
		}

		$timings_raw = isset( $json['data']['timings'] ) ? $json['data']['timings'] : array();
		$data = array(
			'timings' => $this->normalize_timings( $timings_raw ),
			'date'    => isset( $json['data']['date'] ) ? $json['data']['date'] : array(),
		);

		set_transient( $cache_key, $data, $cache_hours * HOUR_IN_SECONDS );

		return $data;
	}

	/**
	 * Strip timezone suffix from timing strings.
	 * e.g. "05:22 (+06)" → "05:22"
	 *
	 * @param array $timings Associative array of prayer => time string.
	 * @return array
	 */
	private function normalize_timings( $timings ) {
		if ( ! is_array( $timings ) ) {
			return array();
		}
		$out = array();
		foreach ( $timings as $key => $value ) {
			$out[ $key ] = is_string( $value )
				? preg_replace( '/\s*\([^)]*\)\s*$/', '', trim( $value ) )
				: $value;
		}
		return $out;
	}

	/**
	 * Load Bangla duas from local JSON file.
	 *
	 * @return array List of dua objects.
	 */
	public function get_bangla_duas() {
		$file = BARAKAH_PLUGIN_PATH . 'data/bangla_duas.json';
		if ( ! is_readable( $file ) ) {
			return array();
		}
		$json = file_get_contents( $file );
		$duas = json_decode( $json, true );
		return is_array( $duas ) ? $duas : array();
	}

	/**
	 * Flush all Barakah transients (on settings change or deactivation).
	 */
	public static function flush_cache() {
		global $wpdb;
		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			 WHERE option_name LIKE '_transient_barakah_%'
			    OR option_name LIKE '_transient_timeout_barakah_%'"
		);
	}
}
