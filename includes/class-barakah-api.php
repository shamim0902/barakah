<?php
/**
 * Barakah API handler – AlAdhan, Al Quran Cloud, local Duas.
 *
 * @package Barakah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Barakah_API
 */
class Barakah_API {

	/**
	 * Singleton instance.
	 *
	 * @var Barakah_API
	 */
	private static $instance = null;

	/**
	 * AlAdhan base URL.
	 */
	const ALADHAN_BASE = 'https://api.aladhan.com/v1';

	/**
	 * Al Quran Cloud base URL.
	 */
	const ALQURAN_BASE = 'https://api.alquran.cloud/v1';

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
	 * Get prayer times (and date info) for a city and date.
	 *
	 * @param string $city   City name.
	 * @param string $country Country code or name.
	 * @param string $date   Date in d-m-Y (e.g. 22-02-2026). Empty for today.
	 * @param int    $method Calculation method (1 = Karachi, etc.).
	 * @return array{ timings: array, date: array, error?: string }
	 */
	public function get_prayer_times( $city, $country, $date = '', $method = 1 ) {
		if ( empty( $date ) ) {
			$date = gmdate( 'd-m-Y' );
		}
		$cache_hours = (int) get_option( 'barakah_cache_hours', 6 );
		$cache_key   = 'barakah_times_' . sanitize_key( $city . '_' . $country . '_' . $date . '_' . $method );
		$cached      = get_transient( $cache_key );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$url = add_query_arg(
			array(
				'city'    => $city,
				'country' => $country,
				'method'  => $method,
			),
			self::ALADHAN_BASE . '/timingsByCity/' . $date
		);

		$response = wp_remote_get( $url, array(
			'timeout' => 15,
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
				'error'   => __( 'Could not fetch prayer times.', 'barakah' ),
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
	 * Strip timezone suffix from timing strings (e.g. "05:22 (+06)" -> "05:22").
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
			$out[ $key ] = is_string( $value ) ? preg_replace( '/\s*\([^)]*\)\s*$/', '', trim( $value ) ) : $value;
		}
		return $out;
	}

	/**
	 * Get full calendar month (prayer times for every day).
	 *
	 * @param string $city   City name.
	 * @param string $country Country code or name.
	 * @param int    $month  Gregorian month (1-12).
	 * @param int    $year   Gregorian year (e.g. 2026).
	 * @param int    $method Calculation method.
	 * @return array{ days: array, error?: string }
	 */
	public function get_calendar_month( $city, $country, $month, $year, $method = 1 ) {
		$month = max( 1, min( 12, (int) $month ) );
		$year  = (int) $year;
		$cache_hours = (int) get_option( 'barakah_cache_hours', 6 );
		$cache_key   = 'barakah_cal_' . sanitize_key( $city . '_' . $country . '_' . $month . '_' . $year . '_' . $method );
		$cached      = get_transient( $cache_key );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$url = add_query_arg(
			array(
				'city'    => $city,
				'country' => $country,
				'month'   => $month,
				'year'    => $year,
				'method'  => $method,
			),
			self::ALADHAN_BASE . '/calendarByCity'
		);

		$response = wp_remote_get( $url, array(
			'timeout'   => 20,
			'sslverify' => true,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'days' => array(), 'error' => $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$json = json_decode( $body, true );

		if ( 200 !== $code || empty( $json['data'] ) || ! is_array( $json['data'] ) ) {
			return array( 'days' => array(), 'error' => __( 'Could not fetch calendar.', 'barakah' ) );
		}

		$days = array();
		foreach ( $json['data'] as $day ) {
			$timings = isset( $day['timings'] ) ? $this->normalize_timings( $day['timings'] ) : array();
			$date   = isset( $day['date'] ) ? $day['date'] : array();
			$days[] = array(
				'timings' => $timings,
				'date'    => $date,
				'readable' => isset( $date['readable'] ) ? $date['readable'] : '',
				'gregorian' => isset( $date['gregorian'] ) ? $date['gregorian'] : array(),
				'hijri'   => isset( $date['hijri'] ) ? $date['hijri'] : array(),
			);
		}

		$result = array( 'days' => $days );
		set_transient( $cache_key, $result, $cache_hours * HOUR_IN_SECONDS );
		return $result;
	}

	/**
	 * Get a single Quran Ayah by number (1–6236) with Arabic and Bangla.
	 *
	 * @param int $ayah_number Ayah number (global index).
	 * @return array{ arabic: string, bangla: string, surah: array, number: int, error?: string }
	 */
	public function get_daily_ayah( $ayah_number = 0 ) {
		if ( $ayah_number < 1 || $ayah_number > 6236 ) {
			$ayah_number = ( (int) gmdate( 'z' ) % 6236 ) + 1;
		}
		$cache_key = 'barakah_ayah_' . $ayah_number;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$arabic_url = self::ALQURAN_BASE . '/ayah/' . $ayah_number;
		$bangla_url = self::ALQURAN_BASE . '/ayah/' . $ayah_number . '/bn.bengali';

		$responses = array(
			'ar' => wp_remote_get( $arabic_url, array( 'timeout' => 15, 'sslverify' => true ) ),
			'bn' => wp_remote_get( $bangla_url, array( 'timeout' => 15, 'sslverify' => true ) ),
		);

		$result = array(
			'arabic'  => '',
			'bangla'  => '',
			'surah'   => array(),
			'number'  => $ayah_number,
		);

		if ( ! is_wp_error( $responses['ar'] ) && 200 === wp_remote_retrieve_response_code( $responses['ar'] ) ) {
			$ar_json = json_decode( wp_remote_retrieve_body( $responses['ar'] ), true );
			if ( ! empty( $ar_json['data']['text'] ) ) {
				$result['arabic'] = $ar_json['data']['text'];
			}
			if ( ! empty( $ar_json['data']['surah'] ) ) {
				$result['surah'] = $ar_json['data']['surah'];
			}
			if ( ! empty( $ar_json['data']['numberInSurah'] ) ) {
				$result['numberInSurah'] = $ar_json['data']['numberInSurah'];
			}
		}

		if ( ! is_wp_error( $responses['bn'] ) && 200 === wp_remote_retrieve_response_code( $responses['bn'] ) ) {
			$bn_json = json_decode( wp_remote_retrieve_body( $responses['bn'] ), true );
			if ( ! empty( $bn_json['data']['text'] ) ) {
				$result['bangla'] = $bn_json['data']['text'];
			}
		}

		if ( '' === $result['arabic'] && '' === $result['bangla'] ) {
			$result['error'] = __( 'Could not fetch verse.', 'barakah' );
			return $result;
		}

		set_transient( $cache_key, $result, 24 * HOUR_IN_SECONDS );
		return $result;
	}

	/**
	 * Get a daily Dua from local JSON (by day of year).
	 *
	 * @param string $category Optional category filter (e.g. Iftar, Morning).
	 * @return array{ id: int, category: string, arabic: string, bangla_pronunciation: string, bangla_meaning: string }
	 */
	public function get_random_dua( $category = '' ) {
		$file = BARAKAH_PLUGIN_DIR . 'data/bangla_duas.json';
		if ( ! is_readable( $file ) ) {
			return array(
				'id'                  => 0,
				'category'            => '',
				'arabic'              => '',
				'bangla_pronunciation' => '',
				'bangla_meaning'      => __( 'Dua file not found.', 'barakah' ),
			);
		}

		$json = file_get_contents( $file );
		$duas = json_decode( $json, true );
		if ( ! is_array( $duas ) || empty( $duas ) ) {
			return array(
				'id'                  => 0,
				'category'            => '',
				'arabic'              => '',
				'bangla_pronunciation' => '',
				'bangla_meaning'      => '',
			);
		}

		if ( $category ) {
			$duas = array_values( array_filter( $duas, function ( $d ) use ( $category ) {
				return isset( $d['category'] ) && 0 === strcasecmp( $d['category'], $category );
			} ) );
		}
		if ( empty( $duas ) ) {
			$duas = json_decode( file_get_contents( $file ), true );
		}

		$index = ( (int) gmdate( 'z' ) ) % max( 1, count( $duas ) );
		$dua   = $duas[ $index ];
		return array(
			'id'                   => isset( $dua['id'] ) ? (int) $dua['id'] : 0,
			'category'             => isset( $dua['category'] ) ? $dua['category'] : '',
			'arabic'               => isset( $dua['arabic'] ) ? $dua['arabic'] : '',
			'bangla_pronunciation' => isset( $dua['bangla_pronunciation'] ) ? $dua['bangla_pronunciation'] : '',
			'bangla_meaning'       => isset( $dua['bangla_meaning'] ) ? $dua['bangla_meaning'] : '',
		);
	}

	/**
	 * Flush all Barakah transients (e.g. on settings save).
	 */
	public static function flush_cache() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_barakah_%' OR option_name LIKE '_transient_timeout_barakah_%'" );
	}
}
