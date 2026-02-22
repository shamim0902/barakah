<?php
/**
 * Barakah AJAX handlers for date browsing.
 *
 * @package Barakah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Barakah_Ajax
 */
class Barakah_Ajax {

	/**
	 * Singleton instance.
	 *
	 * @var Barakah_Ajax
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Barakah_Ajax
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register AJAX actions.
	 */
	public function register() {
		add_action( 'wp_ajax_barakah_get_times', array( $this, 'get_times' ) );
		add_action( 'wp_ajax_nopriv_barakah_get_times', array( $this, 'get_times' ) );
	}

	/**
	 * AJAX handler: return prayer times HTML and data for a given date.
	 */
	public function get_times() {
		check_ajax_referer( 'barakah_ajax', 'nonce' );

		$date    = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
		$city    = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : get_option( 'barakah_city', 'Dhaka' );
		$country = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : get_option( 'barakah_country', 'Bangladesh' );
		$method  = isset( $_POST['method'] ) ? absint( $_POST['method'] ) : (int) get_option( 'barakah_method', 1 );

		if ( empty( $date ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid date.', 'barakah' ) ) );
		}

		// Accept Y-m-d or d-m-Y and normalize to d-m-Y for API
		$date_dmy = $date;
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			$d = DateTime::createFromFormat( 'Y-m-d', $date );
			if ( $d ) {
				$date_dmy = $d->format( 'd-m-Y' );
			}
		} elseif ( ! preg_match( '/^\d{1,2}-\d{1,2}-\d{4}$/', $date ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid date format.', 'barakah' ) ) );
		}

		$api  = Barakah_API::get_instance();
		$data = $api->get_prayer_times( $city, $country, $date_dmy, $method );

		if ( ! empty( $data['error'] ) ) {
			wp_send_json_error( array( 'message' => $data['error'], 'data' => $data ) );
		}

		$shortcodes = Barakah_Shortcodes::get_instance();
		$timings    = isset( $data['timings'] ) ? $data['timings'] : array();
		$date_info  = isset( $data['date'] ) ? $data['date'] : array();

		// Build HTML partials for header, ramadan cards, countdown, prayer grid (no date browser in partial)
		$header_html    = $this->render_header_html( $data, $city, $country );
		$ramadan_html   = $this->render_ramadan_html( $data, $city );
		$countdown_html = $this->render_countdown_html( $data );
		$grid_html      = $this->render_prayer_grid_html( $data, $city );

		wp_send_json_success( array(
			'timings'         => $timings,
			'date'           => $date_info,
			'date_dmy'       => $date_dmy,
			'header'         => $header_html,
			'ramadan_cards'  => $ramadan_html,
			'countdown'      => $countdown_html,
			'prayer_grid'    => $grid_html,
		) );
	}

	/**
	 * Render header HTML (mirrors shortcode helper).
	 */
	private function render_header_html( $data, $city, $country ) {
		$date    = isset( $data['date'] ) ? $data['date'] : array();
		$readable = isset( $date['readable'] ) ? $date['readable'] : gmdate( 'd M Y' );
		$hijri   = '';
		if ( ! empty( $date['hijri'] ) ) {
			$day   = isset( $date['hijri']['day'] ) ? $date['hijri']['day'] : '';
			$month = isset( $date['hijri']['month']['en'] ) ? $date['hijri']['month']['en'] : '';
			$year  = isset( $date['hijri']['year'] ) ? $date['hijri']['year'] : '';
			$hijri = trim( $day . ' ' . $month . ', ' . $year );
		}
		$location = esc_html( $city . ', ' . strtoupper( $country ) );
		ob_start();
		?>
		<div class="barakah-widget__header">
			<h2 class="barakah-widget__title">
				<span class="barakah-widget__title-icon" aria-hidden="true"></span>
				<?php esc_html_e( 'Today Sehri & Iftar Time', 'barakah' ); ?> <?php echo esc_html( $city ); ?>
			</h2>
			<div class="barakah-widget__meta">
				<span class="barakah-widget__location"><?php echo esc_html( $location ); ?></span>
				<span class="barakah-widget__sep">|</span>
				<span class="barakah-widget__date"><?php echo esc_html( $readable ); ?></span>
				<?php if ( $hijri ) : ?>
					<span class="barakah-widget__date-hijri"><?php echo esc_html( $hijri ); ?></span>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Ramadan cards HTML.
	 */
	private function render_ramadan_html( $data, $city ) {
		$timings = isset( $data['timings'] ) ? $data['timings'] : array();
		$imsak   = isset( $timings['Imsak'] ) ? $timings['Imsak'] : '--:--';
		$maghrib = isset( $timings['Maghrib'] ) ? $timings['Maghrib'] : '--:--';
		$format  = function ( $t ) {
			if ( empty( $t ) || ! preg_match( '/^(\d{1,2}):(\d{2})$/', $t, $m ) ) {
				return $t;
			}
			$h = (int) $m[1];
			$m = (int) $m[2];
			$ampm = $h >= 12 ? 'PM' : 'AM';
			$h = $h % 12;
			if ( 0 === $h ) {
				$h = 12;
			}
			return sprintf( '%02d:%02d %s', $h, $m, $ampm );
		};
		ob_start();
		?>
		<div class="barakah-widget__ramadan-cards">
			<div class="barakah-card barakah-card--sehri">
				<h3 class="barakah-card__title"><?php echo esc_html( strtoupper( $city ) ); ?> <?php esc_html_e( 'Sehri Time Today', 'barakah' ); ?></h3>
				<div class="barakah-card__time" data-timing="Imsak"><?php echo esc_html( $format( $imsak ) ); ?></div>
			</div>
			<div class="barakah-card barakah-card--iftar">
				<h3 class="barakah-card__title"><?php echo esc_html( strtoupper( $city ) ); ?> <?php esc_html_e( 'Iftar Time Today', 'barakah' ); ?></h3>
				<div class="barakah-card__time" data-timing="Maghrib"><?php echo esc_html( $format( $maghrib ) ); ?></div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render countdown block HTML.
	 */
	private function render_countdown_html( $data ) {
		$timings = isset( $data['timings'] ) ? $data['timings'] : array();
		$imsak   = isset( $timings['Imsak'] ) ? $timings['Imsak'] : '';
		$maghrib = isset( $timings['Maghrib'] ) ? $timings['Maghrib'] : '';
		$format  = function ( $t ) {
			if ( empty( $t ) || ! preg_match( '/^(\d{1,2}):(\d{2})$/', $t, $m ) ) {
				return $t;
			}
			$h = (int) $m[1];
			$m = (int) $m[2];
			return sprintf( '%02d:%02d %s', $h >= 12 ? $h - 12 : ( 0 === $h ? 12 : $h ), $m, $h >= 12 ? 'PM' : 'AM' );
		};
		ob_start();
		?>
		<div class="barakah-widget__countdown" data-imsak="<?php echo esc_attr( $imsak ); ?>" data-maghrib="<?php echo esc_attr( $maghrib ); ?>">
			<h3 class="barakah-widget__countdown-title"><?php esc_html_e( 'Iftar Remaining Time', 'barakah' ); ?></h3>
			<div class="barakah-widget__countdown-timer">
				<span class="barakah-widget__countdown-hours">00</span>
				<span class="barakah-widget__countdown-sep">:</span>
				<span class="barakah-widget__countdown-mins">00</span>
				<span class="barakah-widget__countdown-sep">:</span>
				<span class="barakah-widget__countdown-secs">00</span>
			</div>
			<div class="barakah-widget__countdown-labels">
				<span><?php esc_html_e( 'HOURS', 'barakah' ); ?></span>
				<span><?php esc_html_e( 'MINUTES', 'barakah' ); ?></span>
				<span><?php esc_html_e( 'SECONDS', 'barakah' ); ?></span>
			</div>
			<div class="barakah-widget__progress">
				<div class="barakah-widget__progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
				<div class="barakah-widget__progress-labels">
					<span class="barakah-widget__progress-start"><?php echo esc_html( $format( $imsak ) ); ?></span>
					<span class="barakah-widget__progress-pct">0% <?php esc_html_e( 'fasting completed', 'barakah' ); ?></span>
					<span class="barakah-widget__progress-end"><?php echo esc_html( $format( $maghrib ) ); ?></span>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render prayer grid HTML.
	 */
	private function render_prayer_grid_html( $data, $city ) {
		$timings = isset( $data['timings'] ) ? $data['timings'] : array();
		$names   = array(
			'Fajr'    => __( 'Fajr', 'barakah' ),
			'Sunrise' => __( 'Sunrise', 'barakah' ),
			'Dhuhr'   => __( 'Dhuhr', 'barakah' ),
			'Asr'     => __( 'Asr', 'barakah' ),
			'Maghrib' => __( 'Maghrib', 'barakah' ),
			'Isha'    => __( 'Isha', 'barakah' ),
		);
		$format = function ( $t ) {
			if ( empty( $t ) || ! preg_match( '/^(\d{1,2}):(\d{2})$/', $t, $m ) ) {
				return $t;
			}
			$h = (int) $m[1];
			$m = (int) $m[2];
			return sprintf( '%02d:%02d %s', $h >= 12 ? ( $h === 12 ? 12 : $h - 12 ) : ( 0 === $h ? 12 : $h ), $m, $h >= 12 ? 'PM' : 'AM' );
		};
		ob_start();
		?>
		<div class="barakah-widget__prayer-grid" data-timings="<?php echo esc_attr( wp_json_encode( $timings ) ); ?>">
			<?php foreach ( $names as $key => $label ) : ?>
				<div class="barakah-widget__prayer-item" data-prayer="<?php echo esc_attr( $key ); ?>">
					<span class="barakah-widget__prayer-name"><?php echo esc_html( $label ); ?></span>
					<span class="barakah-widget__prayer-time"><?php echo esc_html( $format( isset( $timings[ $key ] ) ? $timings[ $key ] : '--:--' ) ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="barakah-widget__prayer-cta">
			<a href="#" class="barakah-widget__prayer-btn barakah-widget__prayer-btn--full"><?php esc_html_e( 'Prayer Time', 'barakah' ); ?> <?php echo esc_html( $city ); ?></a>
		</div>
		<?php
		return ob_get_clean();
	}
}
