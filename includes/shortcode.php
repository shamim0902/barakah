<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'barakah', 'barakah_render_shortcode' );

function barakah_render_shortcode( $atts ) {
    // Merge shortcode attributes with saved global settings
    $atts = shortcode_atts(
        [
            'city'    => get_option( 'barakah_city',    'Dhaka' ),
            'country' => get_option( 'barakah_country', 'Bangladesh' ),
            'method'  => get_option( 'barakah_method',  '1' ),
            'mode'    => 'dark',
        ],
        $atts,
        'barakah'
    );

    ob_start();
    ?>
    <div
        id="barakah-widget"
        data-city="<?php echo esc_attr( $atts['city'] ); ?>"
        data-country="<?php echo esc_attr( $atts['country'] ); ?>"
        data-method="<?php echo esc_attr( $atts['method'] ); ?>"
        data-mode="<?php echo esc_attr( $atts['mode'] ); ?>"
    >
        <!-- Loading state -->
        <div class="bk-loading">
            <div class="bk-loading-moon">🌙</div>
            <div class="bk-loading-spinner"></div>
            <p>Loading prayer times for <?php echo esc_html( $atts['city'] ); ?>…</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
