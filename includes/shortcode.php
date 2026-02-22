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

    // Server-side API fetch with transient caching
    $api  = Barakah_API::get_instance();
    $data = $api->get_prayer_times(
        $atts['city'],
        $atts['country'],
        '',
        (int) $atts['method']
    );

    $has_server_data = ! empty( $data['timings'] ) && empty( $data['error'] );

    // Load Bangla duas
    $bangla_duas = $api->get_bangla_duas();

    // Pass data to JS via wp_localize_script (works because script is enqueued in footer)
    wp_localize_script( 'barakah-script', 'barakahData', [
        'timings'    => isset( $data['timings'] ) ? $data['timings'] : [],
        'date'       => isset( $data['date'] ) ? $data['date'] : [],
        'banglaDuas' => $bangla_duas,
        'hasData'    => $has_server_data,
    ] );

    ob_start();
    ?>
    <div
        id="barakah-widget"
        data-city="<?php echo esc_attr( $atts['city'] ); ?>"
        data-country="<?php echo esc_attr( $atts['country'] ); ?>"
        data-method="<?php echo esc_attr( $atts['method'] ); ?>"
        data-mode="<?php echo esc_attr( $atts['mode'] ); ?>"
    >
        <?php if ( ! $has_server_data ) : ?>
        <!-- Loading state (fallback when server-side fetch failed) -->
        <div class="bk-loading">
            <div class="bk-loading-moon">🌙</div>
            <div class="bk-loading-spinner"></div>
            <p>Loading prayer times for <?php echo esc_html( $atts['city'] ); ?>…</p>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
