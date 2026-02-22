<?php
/**
 * Barakah shortcodes – full widget, prayer times, Ramadan, Ayah, Dua.
 *
 * @package Barakah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Barakah_Shortcodes
 */
class Barakah_Shortcodes {

	/**
	 * Singleton instance.
	 *
	 * @var Barakah_Shortcodes
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Barakah_Shortcodes
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Parse shortcode attributes with defaults from options.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return array
	 */
	private function parse_atts( $atts = array() ) {
		return shortcode_atts(
			array(
				'city'       => get_option( 'barakah_city', 'Dhaka' ),
				'country'    => get_option( 'barakah_country', 'Bangladesh' ),
				'method'     => (int) get_option( 'barakah_method', 1 ),
				'show_dua'   => get_option( 'barakah_show_dua', true ),
				'show_ayah'  => get_option( 'barakah_show_ayah', true ),
				'show_ramadan' => get_option( 'barakah_show_ramadan', true ),
			),
			$atts,
			'barakah'
		);
	}

	/**
	 * Full widget shortcode [barakah].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function full_widget( $atts = array() ) {
		$a     = $this->parse_atts( $atts );
		$date  = gmdate( 'd-m-Y' );
		$api   = Barakah_API::get_instance();
		$data  = $api->get_prayer_times( $a['city'], $a['country'], $date, $a['method'] );
		$ayah  = $api->get_daily_ayah( 0 );
		$dua   = $api->get_random_dua( '' );

		ob_start();
		?>
		<div class="barakah-widget barakah-widget--full" data-city="<?php echo esc_attr( $a['city'] ); ?>" data-country="<?php echo esc_attr( $a['country'] ); ?>" data-method="<?php echo (int) $a['method']; ?>">
			<?php echo $this->render_header( $data, $a['city'], $a['country'] ); ?>
			<?php if ( $a['show_ramadan'] ) : ?>
				<?php echo $this->render_ramadan_cards( $data ); ?>
				<?php echo $this->render_countdown( $data ); ?>
			<?php endif; ?>
			<?php echo $this->render_prayer_times_grid( $data, $a['city'] ); ?>
			<?php echo $this->render_date_browser( $date, $a['city'], $a['country'], $a['method'] ); ?>
			<?php if ( $a['show_ramadan'] ) : ?>
				<?php echo $this->render_ramadan_month_plan( $a['city'], $a['country'], $a['method'] ); ?>
			<?php endif; ?>
			<?php if ( $a['show_ayah'] && ( ! empty( $ayah['arabic'] ) || ! empty( $ayah['bangla'] ) ) ) : ?>
				<?php echo $this->render_ayah_card( $ayah ); ?>
			<?php endif; ?>
			<?php if ( $a['show_dua'] && ! empty( $dua['arabic'] ) ) : ?>
				<?php echo $this->render_dua_card( $dua ); ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Prayer times + date browser shortcode [barakah_prayer_times].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function prayer_times( $atts = array() ) {
		$a    = $this->parse_atts( $atts );
		$date = gmdate( 'd-m-Y' );
		$data = Barakah_API::get_instance()->get_prayer_times( $a['city'], $a['country'], $date, $a['method'] );

		ob_start();
		?>
		<div class="barakah-widget barakah-widget--prayer-times" data-city="<?php echo esc_attr( $a['city'] ); ?>" data-country="<?php echo esc_attr( $a['country'] ); ?>" data-method="<?php echo (int) $a['method']; ?>">
			<?php echo $this->render_header( $data, $a['city'], $a['country'] ); ?>
			<?php echo $this->render_prayer_times_grid( $data, $a['city'] ); ?>
			<?php echo $this->render_date_browser( $date, $a['city'], $a['country'], $a['method'] ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Ramadan (Sehri/Iftar + countdown) shortcode [barakah_ramadan].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function ramadan( $atts = array() ) {
		$a    = $this->parse_atts( $atts );
		$date = gmdate( 'd-m-Y' );
		$data = Barakah_API::get_instance()->get_prayer_times( $a['city'], $a['country'], $date, $a['method'] );

		ob_start();
		?>
		<div class="barakah-widget barakah-widget--ramadan" data-city="<?php echo esc_attr( $a['city'] ); ?>" data-country="<?php echo esc_attr( $a['country'] ); ?>" data-method="<?php echo (int) $a['method']; ?>">
			<?php echo $this->render_header( $data, $a['city'], $a['country'] ); ?>
			<?php echo $this->render_ramadan_cards( $data ); ?>
			<?php echo $this->render_countdown( $data ); ?>
			<?php echo $this->render_ramadan_month_plan( $a['city'], $a['country'], $a['method'] ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Daily Ayah shortcode [barakah_ayah].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function ayah( $atts = array() ) {
		$ayah = Barakah_API::get_instance()->get_daily_ayah( 0 );
		if ( empty( $ayah['arabic'] ) && empty( $ayah['bangla'] ) ) {
			return '';
		}
		return '<div class="barakah-widget barakah-widget--ayah">' . $this->render_ayah_card( $ayah ) . '</div>';
	}

	/**
	 * Daily Dua shortcode [barakah_dua].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function dua( $atts = array() ) {
		$atts = shortcode_atts( array( 'category' => '' ), $atts, 'barakah_dua' );
		$dua  = Barakah_API::get_instance()->get_random_dua( $atts['category'] );
		if ( empty( $dua['arabic'] ) ) {
			return '';
		}
		return '<div class="barakah-widget barakah-widget--dua">' . $this->render_dua_card( $dua ) . '</div>';
	}

	/**
	 * Render header (title, location, dates).
	 *
	 * @param array  $data   Prayer data with date.
	 * @param string $city   City name.
	 * @param string $country Country.
	 * @return string
	 */
	private function render_header( $data, $city, $country ) {
		$date     = isset( $data['date'] ) ? $data['date'] : array();
		$readable = isset( $date['readable'] ) ? $date['readable'] : gmdate( 'd M Y' );
		$hijri    = '';
		$is_ramadan = false;
		if ( ! empty( $date['hijri'] ) ) {
			$day   = isset( $date['hijri']['day'] ) ? $date['hijri']['day'] : '';
			$month = isset( $date['hijri']['month']['en'] ) ? $date['hijri']['month']['en'] : '';
			$year  = isset( $date['hijri']['year'] ) ? $date['hijri']['year'] : '';
			$hijri = trim( $day . ' ' . $month . ', ' . $year );
			$is_ramadan = isset( $date['hijri']['month']['number'] ) && (int) $date['hijri']['month']['number'] === 9;
		}
		$location = esc_html( $city . ', ' . strtoupper( $country ) );
		ob_start();
		?>
		<div class="barakah-widget__header<?php echo $is_ramadan ? ' barakah-widget__header--ramadan' : ''; ?>">
			<h2 class="barakah-widget__title">
				<span class="barakah-widget__title-icon barakah-widget__title-icon--crescent" aria-hidden="true"></span>
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
	 * Render Sehri and Iftar cards.
	 *
	 * @param array $data Prayer data.
	 * @return string
	 */
	private function render_ramadan_cards( $data ) {
		$timings = isset( $data['timings'] ) ? $data['timings'] : array();
		$imsak   = isset( $timings['Imsak'] ) ? $timings['Imsak'] : '--:--';
		$maghrib = isset( $timings['Maghrib'] ) ? $timings['Maghrib'] : '--:--';
		$city    = get_option( 'barakah_city', 'Dhaka' );
		ob_start();
		?>
		<div class="barakah-widget__ramadan-cards">
			<div class="barakah-card barakah-card--sehri">
				<h3 class="barakah-card__title"><?php echo esc_html( strtoupper( $city ) ); ?> <?php esc_html_e( 'Sehri Time Today', 'barakah' ); ?></h3>
				<div class="barakah-card__time" data-timing="Imsak"><?php echo esc_html( $this->format_time_12( $imsak ) ); ?></div>
			</div>
			<div class="barakah-card barakah-card--iftar">
				<h3 class="barakah-card__title"><?php echo esc_html( strtoupper( $city ) ); ?> <?php esc_html_e( 'Iftar Time Today', 'barakah' ); ?></h3>
				<div class="barakah-card__time" data-timing="Maghrib"><?php echo esc_html( $this->format_time_12( $maghrib ) ); ?></div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render countdown and progress bar block.
	 *
	 * @param array $data Prayer data.
	 * @return string
	 */
	private function render_countdown( $data ) {
		$timings = isset( $data['timings'] ) ? $data['timings'] : array();
		$imsak   = isset( $timings['Imsak'] ) ? $timings['Imsak'] : '';
		$maghrib = isset( $timings['Maghrib'] ) ? $timings['Maghrib'] : '';
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
					<span class="barakah-widget__progress-start"><?php echo esc_html( $this->format_time_12( $imsak ) ); ?></span>
					<span class="barakah-widget__progress-pct">0% <?php esc_html_e( 'fasting completed', 'barakah' ); ?></span>
					<span class="barakah-widget__progress-end"><?php echo esc_html( $this->format_time_12( $maghrib ) ); ?></span>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render prayer times grid (Fajr, Sunrise, Dhuhr, Asr, Maghrib, Isha).
	 *
	 * @param array  $data Prayer data.
	 * @param string $city City name for CTA button.
	 * @return string
	 */
	private function render_prayer_times_grid( $data, $city = '' ) {
		$timings = isset( $data['timings'] ) ? $data['timings'] : array();
		$names   = array(
			'Fajr'    => __( 'Fajr', 'barakah' ),
			'Sunrise' => __( 'Sunrise', 'barakah' ),
			'Dhuhr'   => __( 'Dhuhr', 'barakah' ),
			'Asr'     => __( 'Asr', 'barakah' ),
			'Maghrib' => __( 'Maghrib', 'barakah' ),
			'Isha'    => __( 'Isha', 'barakah' ),
		);
		ob_start();
		?>
		<div class="barakah-widget__prayer-grid" data-timings="<?php echo esc_attr( wp_json_encode( $timings ) ); ?>">
			<?php foreach ( $names as $key => $label ) : ?>
				<div class="barakah-widget__prayer-item" data-prayer="<?php echo esc_attr( $key ); ?>">
					<span class="barakah-widget__prayer-name"><?php echo esc_html( $label ); ?></span>
					<span class="barakah-widget__prayer-time"><?php echo esc_html( $this->format_time_12( isset( $timings[ $key ] ) ? $timings[ $key ] : '--:--' ) ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="barakah-widget__prayer-cta">
			<a href="#" class="barakah-widget__prayer-btn barakah-widget__prayer-btn--full"><?php esc_html_e( 'Prayer Time', 'barakah' ); ?> <?php echo esc_html( $city ); ?></a>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render date browser (prev/next + date input).
	 *
	 * @param string $date    Current date d-m-Y.
	 * @param string $city    City.
	 * @param string $country Country.
	 * @param int    $method  Method.
	 * @return string
	 */
	private function render_date_browser( $date, $city, $country, $method ) {
		ob_start();
		?>
		<div class="barakah-widget__date-browser">
			<button type="button" class="barakah-widget__date-prev" aria-label="<?php esc_attr_e( 'Previous day', 'barakah' ); ?>">&larr;</button>
			<input type="date" class="barakah-widget__date-input" value="<?php echo esc_attr( $this->date_dmy_to_iso( $date ) ); ?>" aria-label="<?php esc_attr_e( 'Select date', 'barakah' ); ?>">
			<button type="button" class="barakah-widget__date-next" aria-label="<?php esc_attr_e( 'Next day', 'barakah' ); ?>">&rarr;</button>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Ramadan full month plan (button + modal placeholder; content loaded via JS).
	 *
	 * @param string $city    City.
	 * @param string $country Country.
	 * @param int    $method  Method.
	 * @return string
	 */
	private function render_ramadan_month_plan( $city, $country, $method ) {
		$current_month = (int) gmdate( 'n' );
		$current_year  = (int) gmdate( 'Y' );
		$modal_id     = 'barakah-ramadan-modal-' . wp_unique_id();
		$title_id     = 'barakah-ramadan-modal-title-' . wp_unique_id();
		ob_start();
		?>
		<div class="barakah-widget__ramadan-plan">
			<button type="button" class="barakah-widget__ramadan-plan-btn" aria-expanded="false" aria-controls="<?php echo esc_attr( $modal_id ); ?>" data-city="<?php echo esc_attr( $city ); ?>" data-country="<?php echo esc_attr( $country ); ?>" data-method="<?php echo (int) $method; ?>">
				<span class="barakah-widget__ramadan-plan-btn-icon" aria-hidden="true"></span>
				<?php esc_html_e( 'View full month Ramadan plan', 'barakah' ); ?>
			</button>
			<div id="<?php echo esc_attr( $modal_id ); ?>" class="barakah-widget__ramadan-modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $title_id ); ?>" aria-hidden="true">
				<div class="barakah-widget__ramadan-modal-backdrop"></div>
				<div class="barakah-widget__ramadan-modal-content">
					<div class="barakah-widget__ramadan-modal-header">
						<h3 id="<?php echo esc_attr( $title_id ); ?>" class="barakah-widget__ramadan-modal-title"><?php esc_html_e( 'Ramadan Sehri & Iftar – Full Month', 'barakah' ); ?></h3>
						<button type="button" class="barakah-widget__ramadan-modal-close" aria-label="<?php esc_attr_e( 'Close', 'barakah' ); ?>">&times;</button>
					</div>
					<div class="barakah-widget__ramadan-modal-body">
						<div class="barakah-widget__ramadan-month-picker">
							<label for="<?php echo esc_attr( $modal_id ); ?>-month"><?php esc_html_e( 'Month', 'barakah' ); ?></label>
							<select id="<?php echo esc_attr( $modal_id ); ?>-month" class="barakah-widget__ramadan-month-select">
								<?php for ( $m = 1; $m <= 12; $m++ ) : ?>
									<option value="<?php echo (int) $m; ?>" <?php selected( $m, $current_month ); ?>><?php echo esc_html( gmdate( 'F', mktime( 0, 0, 0, $m, 1 ) ) ); ?></option>
								<?php endfor; ?>
							</select>
							<label for="<?php echo esc_attr( $modal_id ); ?>-year"><?php esc_html_e( 'Year', 'barakah' ); ?></label>
							<select id="<?php echo esc_attr( $modal_id ); ?>-year" class="barakah-widget__ramadan-year-select">
								<?php for ( $y = $current_year - 1; $y <= $current_year + 2; $y++ ) : ?>
									<option value="<?php echo (int) $y; ?>" <?php selected( $y, $current_year ); ?>><?php echo (int) $y; ?></option>
								<?php endfor; ?>
							</select>
							<button type="button" class="barakah-widget__ramadan-load-btn"><?php esc_html_e( 'Load', 'barakah' ); ?></button>
						</div>
						<div class="barakah-widget__ramadan-table-wrap">
							<p class="barakah-widget__ramadan-table-placeholder"><?php esc_html_e( 'Select month & year and click Load to see Sehri & Iftar for each day.', 'barakah' ); ?></p>
							<div class="barakah-widget__ramadan-table-container" aria-live="polite"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Ayah card.
	 *
	 * @param array $ayah Ayah data.
	 * @return string
	 */
	private function render_ayah_card( $ayah ) {
		$surah_name = isset( $ayah['surah']['englishName'] ) ? $ayah['surah']['englishName'] : '';
		$num        = isset( $ayah['numberInSurah'] ) ? $ayah['numberInSurah'] : ( isset( $ayah['number'] ) ? $ayah['number'] : '' );
		ob_start();
		?>
		<div class="barakah-widget__insight barakah-widget__insight--ayah">
			<h3 class="barakah-widget__insight-title"><?php esc_html_e( 'Verse of the Day', 'barakah' ); ?></h3>
			<?php if ( ! empty( $ayah['arabic'] ) ) : ?>
				<p class="barakah-widget__insight-arabic" dir="rtl"><?php echo esc_html( $ayah['arabic'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $ayah['bangla'] ) ) : ?>
				<p class="barakah-widget__insight-bangla"><?php echo esc_html( $ayah['bangla'] ); ?></p>
			<?php endif; ?>
			<?php if ( $surah_name || $num ) : ?>
				<p class="barakah-widget__insight-ref"><?php echo esc_html( $surah_name . ( $num ? ' ' . $num : '' ) ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Dua card.
	 *
	 * @param array $dua Dua data.
	 * @return string
	 */
	private function render_dua_card( $dua ) {
		ob_start();
		?>
		<div class="barakah-widget__insight barakah-widget__insight--dua">
			<h3 class="barakah-widget__insight-title"><?php echo esc_html( $dua['category'] ? $dua['category'] : __( 'Daily Dua', 'barakah' ) ); ?></h3>
			<?php if ( ! empty( $dua['arabic'] ) ) : ?>
				<p class="barakah-widget__insight-arabic" dir="rtl"><?php echo esc_html( $dua['arabic'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $dua['bangla_pronunciation'] ) ) : ?>
				<p class="barakah-widget__insight-bangla"><?php echo esc_html( $dua['bangla_pronunciation'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $dua['bangla_meaning'] ) ) : ?>
				<p class="barakah-widget__insight-meaning"><?php echo esc_html( $dua['bangla_meaning'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Format 24h time (HH:MM) to 12h (e.g. 05:11 AM).
	 *
	 * @param string $time Time string.
	 * @return string
	 */
	private function format_time_12( $time ) {
		if ( empty( $time ) || ! preg_match( '/^(\d{1,2}):(\d{2})$/', $time, $m ) ) {
			return $time;
		}
		$h = (int) $m[1];
		$m = (int) $m[2];
		$ampm = $h >= 12 ? 'PM' : 'AM';
		$h = $h % 12;
		if ( 0 === $h ) {
			$h = 12;
		}
		return sprintf( '%02d:%02d %s', $h, $m, $ampm );
	}

	/**
	 * Convert d-m-Y to Y-m-d for input[type=date].
	 *
	 * @param string $date Date in d-m-Y.
	 * @return string
	 */
	private function date_dmy_to_iso( $date ) {
		$d = DateTime::createFromFormat( 'd-m-Y', $date );
		return $d ? $d->format( 'Y-m-d' ) : gmdate( 'Y-m-d' );
	}
}
