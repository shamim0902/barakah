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

		$cache_hours = (int) get_option( 'barakah_cache_hours', 2 );
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
	 * Fetch a random hadith from public APIs with local fallback.
	 *
	 * @return array{ text: string, source: string, narrator: string, reference: string }
	 */
	public function get_random_hadith() {
		$cache_key = 'barakah_hadith_' . gmdate( 'YmdH' );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) && ! empty( $cached['text'] ) ) {
			return $cached;
		}

		$endpoints = array(
			'https://hadeethenc.com/api/v1/hadeeths/one/?language=en',
			'https://hadeethenc.com/api/v1/hadeeths/one/?language=bn',
		);

		foreach ( $endpoints as $url ) {
			$response = wp_remote_get(
				$url,
				array(
					'timeout'   => 12,
					'sslverify' => true,
				)
			);

			if ( is_wp_error( $response ) ) {
				continue;
			}

			$code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );
			$json = json_decode( $body, true );

			if ( 200 !== $code || ! is_array( $json ) ) {
				continue;
			}

			$hadith = $this->normalize_hadith_payload( $json );
			if ( ! empty( $hadith['text'] ) ) {
				set_transient( $cache_key, $hadith, HOUR_IN_SECONDS );
				return $hadith;
			}
		}

		$fallbacks = array(
			array(
				'text'      => 'Actions are judged by intentions, and every person will get the reward according to what he intended.',
				'source'    => 'Sahih al-Bukhari',
				'narrator'  => 'Umar ibn Al-Khattab (RA)',
				'reference' => 'Hadith 1',
			),
			array(
				'text'      => 'The best among you are those who learn the Quran and teach it.',
				'source'    => 'Sahih al-Bukhari',
				'narrator'  => 'Uthman ibn Affan (RA)',
				'reference' => 'Hadith 5027',
			),
			array(
				'text'      => 'Allah does not look at your appearance or wealth, but rather He looks at your hearts and deeds.',
				'source'    => 'Sahih Muslim',
				'narrator'  => 'Abu Hurairah (RA)',
				'reference' => 'Hadith 2564',
			),
		);

		$hadith = $fallbacks[ array_rand( $fallbacks ) ];
		set_transient( $cache_key, $hadith, HOUR_IN_SECONDS );
		return $hadith;
	}

	/**
	 * Normalize hadith payload from external APIs.
	 *
	 * @param array $payload Raw payload.
	 * @return array{ text: string, source: string, narrator: string, reference: string }
	 */
	private function normalize_hadith_payload( $payload ) {
		$text      = '';
		$source    = '';
		$narrator  = '';
		$reference = '';

		if ( isset( $payload['hadeeth'] ) ) {
			$text      = is_string( $payload['hadeeth'] ) ? $payload['hadeeth'] : '';
			$source    = isset( $payload['book'] ) && is_string( $payload['book'] ) ? $payload['book'] : '';
			$narrator  = isset( $payload['attribution'] ) && is_string( $payload['attribution'] ) ? $payload['attribution'] : '';
			$reference = isset( $payload['id'] ) ? (string) $payload['id'] : '';
		}

		if ( empty( $text ) && isset( $payload['data'] ) && is_array( $payload['data'] ) ) {
			$data = $payload['data'];
			$text = isset( $data['hadeeth'] ) && is_string( $data['hadeeth'] ) ? $data['hadeeth'] : $text;
			$text = isset( $data['hadith_english'] ) && is_string( $data['hadith_english'] ) ? $data['hadith_english'] : $text;

			$source = isset( $data['book'] ) && is_string( $data['book'] ) ? $data['book'] : $source;
			$source = isset( $data['source'] ) && is_string( $data['source'] ) ? $data['source'] : $source;

			$narrator = isset( $data['attribution'] ) && is_string( $data['attribution'] ) ? $data['attribution'] : $narrator;
			$narrator = isset( $data['narrator'] ) && is_string( $data['narrator'] ) ? $data['narrator'] : $narrator;

			$reference = isset( $data['id'] ) ? (string) $data['id'] : $reference;
			$reference = isset( $data['reference'] ) && is_string( $data['reference'] ) ? $data['reference'] : $reference;
		}

		return array(
			'text'      => trim( wp_strip_all_tags( (string) $text ) ),
			'source'    => trim( wp_strip_all_tags( (string) $source ) ),
			'narrator'  => trim( wp_strip_all_tags( (string) $narrator ) ),
			'reference' => trim( wp_strip_all_tags( (string) $reference ) ),
		);
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
