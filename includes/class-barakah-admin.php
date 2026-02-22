<?php
/**
 * Barakah admin settings page.
 *
 * @package Barakah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Barakah_Admin
 */
class Barakah_Admin {

	/**
	 * Singleton instance.
	 *
	 * @var Barakah_Admin
	 */
	private static $instance = null;

	/**
	 * Option group.
	 */
	const OPTION_GROUP = 'barakah_settings';

	/**
	 * Get singleton instance.
	 *
	 * @return Barakah_Admin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register settings and fields.
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			'barakah_city',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'Dhaka',
			)
		);
		register_setting(
			self::OPTION_GROUP,
			'barakah_country',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'Bangladesh',
			)
		);
		register_setting(
			self::OPTION_GROUP,
			'barakah_method',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 1,
			)
		);
		register_setting(
			self::OPTION_GROUP,
			'barakah_show_ramadan',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => function ( $v ) { return filter_var( $v, FILTER_VALIDATE_BOOLEAN ); },
				'default'           => true,
			)
		);
		register_setting(
			self::OPTION_GROUP,
			'barakah_cache_hours',
			array(
				'type'              => 'integer',
				'sanitize_callback' => function ( $v ) { return max( 1, min( 168, (int) $v ) ); },
				'default'           => 6,
			)
		);
		register_setting(
			self::OPTION_GROUP,
			'barakah_show_dua',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => function ( $v ) { return filter_var( $v, FILTER_VALIDATE_BOOLEAN ); },
				'default'           => true,
			)
		);
		register_setting(
			self::OPTION_GROUP,
			'barakah_show_ayah',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => function ( $v ) { return filter_var( $v, FILTER_VALIDATE_BOOLEAN ); },
				'default'           => true,
			)
		);

		add_settings_section(
			'barakah_section_main',
			__( 'Location & Calculation', 'barakah' ),
			array( $this, 'section_main_cb' ),
			'barakah'
		);

		add_settings_field(
			'barakah_city',
			__( 'Default City', 'barakah' ),
			array( $this, 'field_text' ),
			'barakah',
			'barakah_section_main',
			array( 'label_for' => 'barakah_city', 'option' => 'barakah_city', 'placeholder' => 'Dhaka' )
		);
		add_settings_field(
			'barakah_country',
			__( 'Default Country', 'barakah' ),
			array( $this, 'field_text' ),
			'barakah',
			'barakah_section_main',
			array( 'label_for' => 'barakah_country', 'option' => 'barakah_country', 'placeholder' => 'Bangladesh' )
		);
		add_settings_field(
			'barakah_method',
			__( 'Calculation Method', 'barakah' ),
			array( $this, 'field_method' ),
			'barakah',
			'barakah_section_main',
			array( 'label_for' => 'barakah_method' )
		);

		add_settings_section(
			'barakah_section_display',
			__( 'Display Options', 'barakah' ),
			array( $this, 'section_display_cb' ),
			'barakah'
		);

		add_settings_field(
			'barakah_show_ramadan',
			__( 'Show Ramadan section', 'barakah' ),
			array( $this, 'field_checkbox' ),
			'barakah',
			'barakah_section_display',
			array( 'label_for' => 'barakah_show_ramadan', 'option' => 'barakah_show_ramadan' )
		);
		add_settings_field(
			'barakah_show_ayah',
			__( 'Show daily Quran verse', 'barakah' ),
			array( $this, 'field_checkbox' ),
			'barakah',
			'barakah_section_display',
			array( 'label_for' => 'barakah_show_ayah', 'option' => 'barakah_show_ayah' )
		);
		add_settings_field(
			'barakah_show_dua',
			__( 'Show daily Dua', 'barakah' ),
			array( $this, 'field_checkbox' ),
			'barakah',
			'barakah_section_display',
			array( 'label_for' => 'barakah_show_dua', 'option' => 'barakah_show_dua' )
		);

		add_settings_section(
			'barakah_section_cache',
			__( 'Cache', 'barakah' ),
			array( $this, 'section_cache_cb' ),
			'barakah'
		);

		add_settings_field(
			'barakah_cache_hours',
			__( 'Cache duration (hours)', 'barakah' ),
			array( $this, 'field_number' ),
			'barakah',
			'barakah_section_cache',
			array( 'label_for' => 'barakah_cache_hours', 'option' => 'barakah_cache_hours', 'min' => 1, 'max' => 168 )
		);
	}

	/**
	 * Section callback for main.
	 */
	public function section_main_cb() {
		echo '<p>' . esc_html__( 'Set the default location used when shortcodes do not specify city/country.', 'barakah' ) . '</p>';
	}

	/**
	 * Section callback for display.
	 */
	public function section_display_cb() {
		echo '<p>' . esc_html__( 'Control which sections appear in the full [barakah] shortcode.', 'barakah' ) . '</p>';
	}

	/**
	 * Section callback for cache.
	 */
	public function section_cache_cb() {
		echo '<p>' . esc_html__( 'Prayer times are cached to reduce API calls. Clear cache by deactivating and reactivating the plugin.', 'barakah' ) . '</p>';
	}

	/**
	 * Text field.
	 *
	 * @param array $args Field args.
	 */
	public function field_text( $args ) {
		$option = get_option( $args['option'], '' );
		$placeholder = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
		echo '<input type="text" id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['option'] ) . '" value="' . esc_attr( $option ) . '" class="regular-text" placeholder="' . esc_attr( $placeholder ) . '">';
	}

	/**
	 * Number field.
	 *
	 * @param array $args Field args.
	 */
	public function field_number( $args ) {
		$option = get_option( $args['option'], 6 );
		$min = isset( $args['min'] ) ? (int) $args['min'] : 0;
		$max = isset( $args['max'] ) ? (int) $args['max'] : 24;
		echo '<input type="number" id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['option'] ) . '" value="' . esc_attr( $option ) . '" min="' . esc_attr( $min ) . '" max="' . esc_attr( $max ) . '" class="small-text">';
	}

	/**
	 * Checkbox field.
	 *
	 * @param array $args Field args.
	 */
	public function field_checkbox( $args ) {
		$option = get_option( $args['option'], true );
		$checked = $option ? ' checked="checked"' : '';
		echo '<input type="checkbox" id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['option'] ) . '" value="1"' . $checked . '>';
	}

	/**
	 * Calculation method dropdown.
	 *
	 * @param array $args Field args.
	 */
	public function field_method( $args ) {
		$methods = array(
			1 => 'University of Islamic Sciences, Karachi',
			2 => 'Islamic Society of North America (ISNA)',
			3 => 'Muslim World League (MWL)',
			4 => 'Umm Al-Qura University, Makkah',
			5 => 'Egyptian General Authority of Survey',
			7 => 'Institute of Geophysics, University of Tehran',
			8 => 'Gulf Region',
			9 => 'Kuwait',
			10 => 'Qatar',
			11 => 'Singapore',
			12 => 'Union Organization islamic de France',
			13 => 'Diyanet İşleri Başkanlığı, Turkey',
			14 => 'Spiritual Administration of Muslims of Russia',
		);
		$current = (int) get_option( 'barakah_method', 1 );
		echo '<select id="' . esc_attr( $args['label_for'] ) . '" name="barakah_method">';
		foreach ( $methods as $id => $name ) {
			echo '<option value="' . esc_attr( $id ) . '"' . selected( $current, $id, false ) . '>' . esc_html( $name ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( 'barakah' );
				submit_button( __( 'Save Settings', 'barakah' ) );
				?>
			</form>
			<p class="description">
				<?php esc_html_e( 'Shortcodes: [barakah], [barakah_prayer_times], [barakah_ramadan], [barakah_ayah], [barakah_dua]. Use attributes city="Sylhet" country="Bangladesh" to override defaults.', 'barakah' ); ?>
			</p>
		</div>
		<?php
	}
}
